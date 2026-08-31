<?php

namespace App\Http\Controllers;

use App\Actions\Subscription\CreateSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Repositories\StripeRepository;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class StripeController extends AppBaseController
{
    /**
     * @var StripeRepository
     */
    private $stripeRepository;

    public function __construct(StripeRepository $stripeRepository)
    {
        $this->stripeRepository = $stripeRepository;
        $stripeSecret = getSuperAdminPaymentCredentials('stripe_secret');
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * @return string[]
     */
    public static function zeroDecimalCurrencies(): array
    {
        return [
            'BIF',
            'CLP',
            'DJF',
            'GNF',
            'JPY',
            'KMF',
            'KRW',
            'MGA',
            'PYG',
            'RWF',
            'UGX',
            'VND',
            'VUV',
            'XAF',
            'XOF',
            'XPF',
        ];
    }

    /**
     * @return mixed|string|string[]
     */
    public static function removeCommaFromNumbers($number)
    {
        return (gettype($number) == 'string' && ! empty($number)) ? str_replace(',', '', $number) : $number;
    }

    public function purchase(Request $request)
    {
        try {
            $plan = json_decode($request->plan);
            $user = Auth::user();

            $data = [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ];

            $session = Session::create([
                'payment_method_types' => ['card'],
                'customer_email' => $user->email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => $plan->currency,
                        'unit_amount' => in_array(strtoupper(getCurrentCurrency()), zeroDecimalCurrencies()) ? $plan->payable_amount : $plan->payable_amount * 100,
                        'product_data' => [
                            'name' => $plan->name,
                        ],
                    ],
                    'quantity' => '1',
                ]],
                'mode' => 'payment',
                'client_reference_id' => json_encode($data),
                'success_url' => route('stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.failed').'?error=subscription_failed',
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();

            return redirect()->back();
        }
    }

    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');
        if (empty($sessionId)) {
            throw new UnprocessableEntityHttpException('session_id required');
        }

        try {
            $sessionData = Session::retrieve($sessionId);
            $sessionId = $sessionData->id;
            $data = json_decode($sessionData['client_reference_id'], true);
            $plan = SubscriptionPlan::find($data['plan_id']);

            DB::beginTransaction();

            $transaction = Transaction::create([
                'transaction_id' => $sessionData->payment_intent,
                'amount' => $sessionData->amount_total / 100,
                'payment_type' => Transaction::TYPE_STRIPE,
                'user_id' => $data['user_id'],
                'status' => Transaction::APPROVED,
            ]);

            $planData['plan'] = $plan->toArray();
            $planData['user_id'] = $data['user_id'];
            $planData['payment_type'] = Subscription::TYPE_STRIPE;
            $planData['transaction_id'] = $transaction->id;
            $subscription = CreateSubscription::run($planData);

            DB::commit();

            if ($subscription) {
                Notification::make()
                    ->success()
                    ->title(__('messages.flash.subscription_created'))
                    ->send();
                setPlanFeatures();

                return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function failed(Request $request)
    {
        Notification::make()
            ->danger()
            ->title(__('messages.new_change.payment_fail'))
            ->send();
        if ($request->error == 'subscription_failed') {
            return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
        } else {
            return redirect(route('landing-home'));
        }
    }
}
