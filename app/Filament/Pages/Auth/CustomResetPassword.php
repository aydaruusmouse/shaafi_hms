<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Form;
use Filament\Pages\Auth\PasswordReset\ResetPassword;

class CustomResetPassword extends ResetPassword
{
    protected static string $view = 'auth.reset_password';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getEmailFormComponent()->label(__('messages.mails.email_address').':')->placeholder(__('messages.mails.email_address')),
                $this->getPasswordFormComponent()->label(__('messages.staff.password').':')->placeholder(__('messages.staff.password')),
                $this->getPasswordConfirmationFormComponent()->label(__('messages.staff.confirm_password').':')->placeholder(__('messages.staff.confirm_password')),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getResetPasswordFormAction()
                ->extraAttributes(['class' => 'w-full flex items-center justify-center space-x-3'])
                ->label(__('messages.reset').' '.__('messages.staff.password')),
        ];
    }
}
