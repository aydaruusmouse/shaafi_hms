<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Pages;

use App\Filament\HospitalAdmin\Clusters\Settings;
use App\Models\Setting;
use App\Models\User;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\Arr;

class PaymentGateway extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.hospital-admin.clusters.settings.pages.payment-gateway';

    protected static ?string $cluster = Settings::class;

    public ?array $data = [];

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('messages.setting.payment_gateway');
    }

    public function getTitle(): string
    {
        return __('messages.setting.payment_gateway');
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public function mount()
    {
        $keys = [
            'stripe_key',
            'stripe_secret',
            'paypal_client_id',
            'paypal_secret',
            'paypal_mode',
            'razorpay_key',
            'razorpay_secret',
            'paystack_public_key',
            'paystack_secret_key',
            'phonepe_merchant_id',
            'phonepe_merchant_user_id',
            'phonepe_env',
            'phonepe_salt_key',
            'phonepe_salt_index',
            'phonepe_merchant_transaction_id',
            'flutterwave_public_key',
            'flutterwave_secret_key',
            'stripe_enable',
            'paypal_enable',
            'paypal_key',
            'razorpay_enable',
            'paystack_enable',
            'paystack_key',
            'paystack_secret',
            'phone_pe_enable',
            'flutterwave_enable',
            'flutterwave_key',
            'flutterwave_secret',
        ];
        $settingsData = Setting::select('key', 'value')->where('tenant_id', (getLoggedInUser()->tenant_id))->whereIn('key', $keys)->get()->keyBy('key')->toArray();

        $this->form->fill($settingsData);
        // dd($settingsData);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Toggle::make('stripe_enable.value')
                            ->live()
                            ->label(__('messages.setting.stripe').': '),
                        Group::make()
                            ->schema([
                                TextInput::make('stripe_key.value')
                                    ->placeholder(__('messages.setting.stripe_key'))
                                    ->required()
                                    ->validationAttribute(__('messages.setting.stripe_key'))
                                    ->label(__('messages.setting.stripe_key').':'),
                                TextInput::make('stripe_secret.value')
                                    ->required()
                                    ->validationAttribute(__('messages.setting.stripe_secret'))
                                    ->placeholder(__('messages.setting.stripe_secret'))
                                    ->label(__('messages.setting.stripe_secret').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('stripe_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),

                        Toggle::make('paypal_enable.value')
                            ->live()
                            ->label(__('messages.setting.paypal').': '),
                        Group::make()
                            ->schema([
                                TextInput::make('paypal_client_id.value')
                                    ->placeholder(__('messages.setting.paypal_client_id'))
                                    ->required()
                                    ->validationAttribute(__('messages.setting.paypal_client_id'))
                                    ->label(__('messages.setting.paypal_client_id').': '),
                                TextInput::make('paypal_secret.value')
                                    ->required()
                                    ->validationAttribute(__('messages.setting.paypal_secret'))
                                    ->placeholder(__('messages.setting.paypal_secret'))
                                    ->label(__('messages.setting.paypal_secret').':'),
                                TextInput::make('paypal_mode.value')
                                    ->required()
                                    ->validationAttribute(__('messages.setting.paypal_mode'))
                                    ->placeholder(__('messages.setting.paypal_mode'))
                                    ->label(__('messages.setting.paypal_mode').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('paypal_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),

                        Toggle::make('razorpay_enable.value')
                            ->live()
                            ->label(__('messages.setting.razorpay').':'),
                        Group::make()
                            ->schema([
                                TextInput::make('razorpay_key.value')
                                    ->placeholder(__('messages.setting.razorpay_key'))
                                    ->required()
                                    ->validationAttribute(__('messages.setting.razorpay_key'))
                                    ->label(__('messages.setting.razorpay_key').':'),
                                TextInput::make('razorpay_secret.value')
                                    ->required()
                                    ->validationAttribute(__('messages.setting.razorpay_secret'))
                                    ->placeholder(__('messages.setting.razorpay_secret'))
                                    ->label(__('messages.setting.razorpay_secret').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('razorpay_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),

                        Toggle::make('paystack_enable.value')
                            ->live()
                            ->label(__('messages.setting.paystack')),
                        Group::make()
                            ->schema([
                                TextInput::make('paystack_public_key.value')
                                    ->placeholder(__('messages.setting.paystack_public_key'))
                                    ->required()
                                    ->validationAttribute(__('messages.setting.paystack_public_key'))
                                    ->label(__('messages.setting.paystack_public_key').':'),
                                TextInput::make('paystack_secret_key.value')
                                    ->required()
                                    ->validationAttribute(__('messages.setting.paystack_secret_key'))
                                    ->placeholder(__('messages.setting.paystack_secret_key'))
                                    ->label(__('messages.setting.paystack_secret_key').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('paystack_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),

                        Toggle::make('phone_pe_enable.value')
                            ->live()
                            ->label(__('messages.phonepe.phonepe')),
                        Group::make()
                            ->schema([
                                TextInput::make('phonepe_merchant_id.value')
                                    ->placeholder(__('messages.phonepe.phonepe_merchant_id'))
                                    ->required()
                                    ->validationAttribute(__('messages.phonepe.phonepe_merchant_id'))
                                    ->label(__('messages.phonepe.phonepe_merchant_id').':'),
                                TextInput::make('phonepe_merchant_user_id.value')
                                    ->required()
                                    ->validationAttribute(__('messages.phonepe.phonepe_merchant_user_id'))
                                    ->placeholder(__('messages.phonepe.phonepe_merchant_user_id'))
                                    ->label(__('messages.phonepe.phonepe_merchant_user_id').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('phone_pe_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),
                        Group::make()
                            ->schema([
                                TextInput::make('phonepe_env.value')
                                    ->placeholder(__('messages.phonepe.phonepe_env'))
                                    ->required()
                                    ->validationAttribute(__('messages.phonepe.phonepe_env'))
                                    ->label(__('messages.phonepe.phonepe_env').':'),
                                TextInput::make('phonepe_salt_key.value')
                                    ->required()
                                    ->validationAttribute(__('messages.phonepe.phonepe_salt_key'))
                                    ->placeholder(__('messages.phonepe.phonepe_salt_key'))
                                    ->label(__('messages.phonepe.phonepe_salt_key').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('phone_pe_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),

                        Group::make()
                            ->schema([
                                TextInput::make('phonepe_salt_index.value')
                                    ->placeholder(__('messages.phonepe.phonepe_salt_index'))
                                    ->required()
                                    ->validationAttribute(__('messages.phonepe.phonepe_salt_index'))
                                    ->label(__('messages.phonepe.phonepe_salt_index').':'),
                                TextInput::make('phonepe_merchant_transaction_id.value')
                                    ->required()
                                    ->validationAttribute(__(__('messages.phonepe.phonepe_merchant_transaction_id')))
                                    ->placeholder(__('messages.phonepe.phonepe_merchant_transaction_id'))
                                    ->label(__('messages.phonepe.phonepe_merchant_transaction_id').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('phone_pe_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),

                        Toggle::make('flutterwave_enable.value')
                            ->live()
                            ->label(__('messages.flutterwave.flutterwave')),
                        Group::make()
                            ->schema([
                                TextInput::make('flutterwave_public_key.value')
                                    ->placeholder(__('messages.flutterwave.flutterwave_public_key'))
                                    ->required()
                                    ->validationAttribute(__('messages.flutterwave.flutterwave_public_key'))
                                    ->label(__('messages.flutterwave.flutterwave_public_key').':'),
                                TextInput::make('flutterwave_secret_key.value')
                                    ->required()
                                    ->validationAttribute(__('messages.flutterwave.flutterwave_secret_key'))
                                    ->placeholder(__('messages.flutterwave.flutterwave_secret_key'))
                                    ->label(__('messages.flutterwave.flutterwave_secret_key').':'),
                            ])->columns(2)->visible(function (callable $get) {
                                if ($get('flutterwave_enable.value')) {
                                    return true;
                                }

                                return false;
                            }),
                    ]),

            ])->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('messages.common.save'))
                ->submit('save'),
        ];
    }

    public function resetForm()
    {
        $this->js('window.location.reload()');
    }

    public function save()
    {
        $result = $this->data;
        foreach ($result as $key => $value) {
            if (is_array($value) && isset($value['value'])) {
                $input[$key] = $value['value'];
            }
        }
        $inputArr = Arr::except($input, ['_token']);

        if (! isset($inputArr['stripe_enable'])) {
            $inputArr = Arr::add($inputArr, 'stripe_enable', 0);
        }
        if (! isset($inputArr['paypal_enable'])) {
            $inputArr = Arr::add($inputArr, 'paypal_enable', 0);
        }
        if (! isset($inputArr['razorpay_enable'])) {
            $inputArr = Arr::add($inputArr, 'razorpay_enable', 0);
        }
        if (! isset($inputArr['paytm_enable'])) {
            $inputArr = Arr::add($inputArr, 'paytm_enable', 0);
        }
        if (! isset($inputArr['paystack_enable'])) {
            $inputArr = Arr::add($inputArr, 'paystack_enable', 0);
        }
        if (! isset($inputArr['phone_pe_enable'])) {
            $inputArr = Arr::add($inputArr, 'phone_pe_enable', 0);
        }
        if (! isset($inputArr['flutterwave_enable'])) {
            $inputArr = Arr::add($inputArr, 'flutterwave_enable', 0);
        }
        foreach ($inputArr as $key => $value) {
            /** @var UserSetting $UserSetting */
            $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
            $UserSetting = Setting::where('tenant_id', $tenantId)->where('key', '=', $key)->first();
            if (! $UserSetting) {
                Setting::create([
                    'tenant_id' => $tenantId,
                    'key' => $key,
                    'value' => $value,
                ]);
            } else {
                $UserSetting->update(['value' => $value]);
            }
        }

        Notification::make()
            ->success()
            ->title(__('messages.settings').' '.__('messages.common.updated_successfully'))
            ->send();
        $this->afterSave();
    }

    protected function afterSave()
    {
        $this->js('window.location.reload()');
    }
}
