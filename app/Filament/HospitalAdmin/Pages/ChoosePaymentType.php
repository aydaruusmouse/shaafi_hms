<?php

namespace App\Filament\HospitalAdmin\Pages;

use App\Actions\Subscription\GetCurrentSubscription;
use App\Enums\PlanFrequency;
use App\Http\Controllers\PaystackController;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminCurrencySetting;
use App\Models\SuperAdminSetting;
use App\Models\Transaction;
use App\Repositories\SubscriptionRepository;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ChoosePaymentType extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'choose-payment-type/{plan}';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.hospital-admin.pages.choose-payment-type';

    public SubscriptionPlan $plan;

    public $paymentAmount = 0;

    public $paymentType = 0;

    public $disableButton = false;

    protected function getViewData(): array
    {
        // New Plan
        $plan = $this->plan;
        $plan->currency_icon = SuperAdminCurrencySetting::where('currency_code', $plan->currency)->first()->currency_icon;
        $plan->start_date = Carbon::now();
        $plan->end_date = Carbon::now()->addMonth()->endOfDay();
        $plan->total_days = 30;

        if ($plan->frequency == PlanFrequency::MONTHLY->value) {
            $plan->end_date = Carbon::now()->addMonth()->endOfDay();
        } elseif ($plan->frequency == PlanFrequency::YEARLY->value) {
            $plan->end_date = Carbon::now()->addYear()->endOfDay();
        }

        $plan->total_days = floor($plan->start_date->diffInDays($plan->end_date));
        $plan->payable_amount = $plan->price > 0 ? $plan->price : 0;
        $this->paymentAmount = $plan->price > 0 ? $plan->price : 0;

        $currentActivePlan = empty(GetCurrentSubscription::run()) ? null : GetCurrentSubscription::run();

        if ($currentActivePlan) {
            $price = $plan->price - $currentActivePlan['remaining_balance'];
            $plan->payable_amount = $price > 0 ? $price : 0;
            $this->paymentAmount = $price > 0 ? $price : 0;
        }
        $query = SuperAdminSetting::pluck('value', 'key')->toArray();
        $manualPaymentGuide = $query['manual_instruction'] ?? null;

        $transction = Transaction::where('user_id', getLoggedInUserId())
            ->where('payment_type', Transaction::TYPE_CASH)
            ->where('status', 0)
            ->where('is_manual_payment', 0)->latest()->exists();
        if ($transction) {
            Notification::make()
                ->warning()
                ->title(__('messages.flash.request_pending'))
                ->send();
            $this->disableButton = true;
        }
        if ($plan->price <= 0) {
            Notification::make()
                ->warning()
                ->title(__('messages.flash.cannot_switch'))
                ->send();
            $this->disableButton = true;
        }

        return compact('plan', 'currentActivePlan', 'manualPaymentGuide');
    }

    public static function getRelativeRouteName(): string
    {
        return (string) 'choose-payment-type';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('payment_type')
                    ->live()
                    ->options(getSuperAdminPaymentTypes())
                    ->default(Subscription::TYPE_STRIPE)
                    ->required()
                    ->id('paymentType')
                    ->extraAttributes(['class' => 'payment-type', 'data-turbo' => 'false'])
                    ->afterStateUpdated(function (Get $get) {
                        $this->paymentType = (int) $get('payment_type');
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.payment.payment').' '.__('messages.fields.required'),
                    ]),

                SpatieMediaLibraryFileUpload::make('attachment')
                    ->label(__('messages.email.attachment'))
                    ->disk(config('app.media_disk'))
                    // ->collection(Subscription::ATTACHMENT)
                    ->avatar()
                    ->visible(fn (Get $get) => $get('payment_type') == 4),
                Textarea::make('notes')
                    ->label(__('messages.document.notes'))
                    ->visible(fn (Get $get) => $get('payment_type') == 4),
            ])
            ->statePath('data');
    }

    public function save()
    {
        $data = app(SubscriptionRepository::class)->manageCashSubscription($this->plan->id);

        if (! isset($data['plan'])) { // 0 amount plan or try to switch the plan if it is in trial mode
            if (isset($data['status']) && $data['status'] == true) {
                Notification::make()
                    ->success()
                    ->title($data['subscriptionPlan']->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'))
                    ->send();

                setPlanFeatures();

                return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
            } else {
                if (isset($data['status']) && $data['status'] == false) {
                    Notification::make()
                        ->danger()
                        ->title(__('messages.flash.cannot_switch'))
                        ->send();
                }
            }
        }
        $subscriptionId = $data['subscription']->id;
        $subscriptionAmount = $data['amountToPay'];

        $transaction = Transaction::create([
            'payment_type' => Transaction::TYPE_CASH,
            'amount' => $subscriptionAmount,
            'user_id' => getLoggedInUserId(),
            'status' => Subscription::INACTIVE,
            'tenant_id' => getLoggedInUser()->tenant_id,
            'notes' => isset($input['notes']) ? $input['notes'] : null,
            'transaction_id' => $subscriptionId,
        ]);

        if (! empty($input['attachment'])) {
            $fileExtension = getFileName('Transaction', $input['attachment']);
            $transaction->addMedia($input['attachment'])->usingFileName($fileExtension)->toMediaCollection(
                Transaction::PATH,
                config('app.media_disc')
            );
        }
        $subscription = Subscription::with('subscriptionPlan')->findOrFail($subscriptionId);
        $subscription->update(['transaction_id' => $transaction->id]);

        Notification::make()
            ->success()
            ->title(__('messages.subscription.cash_payment_done'))
            ->send();

        setPlanFeatures();

        return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
    }

    public function purchaseSubscriptionRazorpay($id, $amount, $currency, $startDate, $endDate)
    {
        $data = app(SubscriptionRepository::class)->manageSubscription($id);
        if (! isset($data['plan'])) { // 0 amount plan or try to switch the plan if it is in trial mode
            // returning from here if the plan is free.
            if (isset($data['status']) && $data['status'] == true) {
                Notification::make()->title($data['subscriptionPlan']->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'))->success()->send();

                return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
            } else {
                if (isset($data['status']) && $data['status'] == false) {
                    Notification::make()->title(__('messages.flash.cannot_switch'))->danger()->send();

                    return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
                }
            }
        }

        $subscriptionRazorpayData = [
            'plan' => $id,
            'amount' => $data['amountToPay'],
            'currency' => $currency,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
        session(['subscriptionRazorpayData' => $subscriptionRazorpayData]);
        $currency = strtoupper($currency);

        return $this->js('razorPay(event'.','. 10 .', '.$id.', '.$data['amountToPay'].', '.'"'.$currency.'"'.')');
    }

    public function purchaseSubscriptionPayatck($id, $amount, $currency, $startDate, $endDate)
    {
        $subscriptionPaystackData = [
            'plan' => $id,
            'amount' => $amount,
            'currency' => $currency,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
        $paystackController = app(PaystackController::class)->redirectToGateway($subscriptionPaystackData);
    }

    public function phonePeinit($plan)
    {
        $input = $plan;
        $currency = ['INR'];

        if (! in_array(strtoupper(getCurrentCurrency()), $currency)) {
            return Notification::make()
                ->danger()
                ->title(__('messages.phonepe.currency_allowed'))
                ->send();
        }

        $result = app(SubscriptionRepository::class)->phonePePayment($input);

        return redirect($result);
    }
}
