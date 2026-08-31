<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Vormkracht10\TwoFactorAuth\Http\Livewire\Auth\Login;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class CustomLogin extends Login
{
    protected static string $view = 'auth.login';

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent()
                            ->label(__('messages.user.email').':')
                            ->validationAttribute(__('messages.user.email'))
                            ->placeholder(__('messages.user.email')),
                        $this->getPasswordFormComponent()
                            ->label(__('messages.user.password').':')
                            ->validationAttribute(__('messages.user.password'))
                            ->placeholder(__('messages.user.password'))
                            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __("auth.login.forgot_password") }}?</x-filament::link>')) : null),
                        $this->getRememberFormComponent()
                            ->label(__('auth.remember_me')),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction()
                ->extraAttributes(['class' => 'w-full flex items-center justify-center space-x-3'])
                ->label(__('auth.sign_in')),
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (isset($data['email']) && ! empty($data['email'])) {
            $user = User::whereEmail($data['email'])->first();
            if ($user) {
                if ($user->status == false) {
                    Notification::make()
                        ->title(__('Your Account is currently disabled, please contact to administrator.'))
                        ->danger()
                        ->send();

                    return null;
                }
            }
        }

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
}