<?php

namespace App\Filament\Pages\Auth;

use App\Http\Responses\RegistrationResponse;
use App\Mail\NotifyMailSuperAdminForRegisterHospital;
use App\Models\Department;
use App\Models\DoctorDepartment;
use App\Models\MultiTenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserTenant;
use Carbon\Carbon;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class Register extends BaseRegister
{
    /**
     * @var view-string
     */
    protected static string $view = 'auth.register';

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Group::make([
                            TextInput::make('hospital_name')
                                ->label(__('messages.hospitals_list.hospital_name').':')
                                ->validationAttribute(__('messages.hospitals_list.hospital_name'))
                                ->placeholder(__('messages.user.first_name'))
                                ->required()
                                ->maxLength(255)
                                ->autofocus(),
                            TextInput::make('hospital_slug')
                                ->label(__('messages.user.hospital_slug').':')
                                ->validationAttribute(__('messages.user.hospital_slug'))
                                ->placeholder(__('messages.user.hospital_slug'))
                                ->required()
                                ->maxLength(12)
                                ->autofocus(),
                            $this->getEmailFormComponent()->label(__('messages.user.email').':')->validationAttribute(__('messages.user.email'))->placeholder(__('messages.user.email')),
                            PhoneInput::make('phone')
                                ->label(__('messages.user.phone').':')
                                ->required()
                                ->defaultCountry('IN')
                                ->rules(function ($get) {
                                    return [
                                        'phone:AUTO,'.strtoupper($get('prefix_code')),
                                    ];
                                })
                                ->validationMessages([
                                    'phone' => __('messages.common.invalid_number'),
                                ])
                                ->validationAttribute(__('messages.user.phone'))
                                ->placeholder(__('messages.user.phone')),
                            $this->getPasswordFormComponent()->label(__('messages.user.password').':')->validationAttribute(__('messages.user.password'))->placeholder(__('messages.user.password')),
                            $this->getPasswordConfirmationFormComponent()->label(__('messages.change_password.confirm_password').':')->validationAttribute(__('messages.change_password.confirm_password'))->placeholder(__('messages.change_password.confirm_password')),
                        ])->columns(2),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction()
                ->extraAttributes(['class' => 'w-full flex items-center justify-center space-x-3'])
                ->label(__('auth.submit')),
        ];
    }

    public function register(): ?RegistrationResponse
    {

        $this->dispatch('validationFailed');

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function () {
            $data = $this->form->getState();

            $data['status'] = User::ACTIVE;
            $data['first_name'] = $data['hospital_name'];
            $data['username'] = $data['hospital_slug'];
            $data['gender'] = 0;
            $adminRole = Department::whereName('Admin')->first();
            $data['department_id'] = $adminRole->id;
            $data['status'] = User::ACTIVE;
            if (! empty(getSuperAdminSettingValue()['default_language']->value)) {
                $data['language'] = getSuperAdminSettingValue()['default_language']->value;
            }

            $data = $this->mutateFormDataBeforeRegister($data);

            $user = $this->handleRegistration($data);

            $user->assignRole($adminRole);

            $tenant = MultiTenant::create([
                'tenant_username' => $data['username'],
                'hospital_name' => $data['hospital_name'],
            ]);

            $user->update([
                'tenant_id' => $tenant->id,
            ]);

            UserTenant::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'last_login_at' => Carbon::now(),
            ]);

            DoctorDepartment::create([
                'tenant_id' => $tenant->id,
                'title' => 'Doctor',
            ]);

            /*
            $subscription = [
                'user_id'    => $user->id,
                'start_date' => Carbon::now(),
                'end_date'   => Carbon::now()->addDays(6),
                'status'     => 1,
            ];
            Subscription::create($subscription);
            */

            // creating settings and assigning the modules to the created user.
            session(['tenant_id' => $tenant->id]);
            Artisan::call('db:seed', ['--class' => 'SettingsTableSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'AddSocialSettingTableSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'DefaultModuleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'DefaultNewModuleSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'FrontSettingHomeTableSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'FrontSettingTableSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'AddAppointmentFrontSettingTableSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'AddHomePageBoxContentSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'AddDoctorFrontSettingTableSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'FrontServiceSeeder', '--force' => true]);
            Artisan::call('db:seed', ['--class' => 'GoogleRecaptchaSettingSeeder', '--force' => true]);

            // assign the default plan to the user when they registers.
            $subscriptionPlan = SubscriptionPlan::where('is_default', 1)->first();
            $trialDays = $subscriptionPlan->trial_days;
            $subscription = [
                'user_id' => $user->id,
                'subscription_plan_id' => $subscriptionPlan->id,
                'plan_amount' => $subscriptionPlan->price,
                'plan_frequency' => $subscriptionPlan->frequency,
                'starts_at' => Carbon::now(),
                'ends_at' => Carbon::now()->addDays($trialDays),
                'trial_ends_at' => Carbon::now()->addDays($trialDays),
                'status' => Subscription::ACTIVE,
                'sms_limit' => $subscriptionPlan->sms_limit,
            ];
            Subscription::create($subscription);

            $superAdmin = User::whereDepartmentId(10)->first();
            if (! empty($superAdmin)) {
                $superAdminEmail = $superAdmin->email;

                $mailData = [
                    'hospital_name' => $data['hospital_name'],
                    'hospital_email' => $user->email,
                    'hospital_phone' => $user->phone,
                ];

                Mail::to($superAdminEmail)
                    ->send(new NotifyMailSuperAdminForRegisterHospital(
                        'emails.hospital_register_mail',
                        __('messages.new_change.hospital_register'),
                        $mailData
                    ));
            }

            return $user;
        });

        $user->sendEmailVerificationNotification();
        Notification::make()
            ->success()
            ->title(__('auth.verify_email'))
            ->send();
        Filament::auth()->login($user, true);

        return app(RegistrationResponse::class);
    }
}