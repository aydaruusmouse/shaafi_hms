<?php

namespace App\Filament\Pages;

use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Vormkracht10\TwoFactorAuth\Http\Livewire\Auth\LoginTwoFactor;

class CustomLoginFactor extends LoginTwoFactor implements HasForms, HasActions
{

    protected static string $view = 'filament.pages.custom-login-factor';

    protected function redirectTo(): string
    {
        // You can customize this route logic based on role
        $user = auth()->user();
        $role = $user?->roles()?->first()?->name;
        dd($user);

        return match ($role) {
            'Doctor' => route('filament.hospitalAdmin.doctors.resources.doctors.index'),
            'Accountant' => route('filament.hospitalAdmin.finance.resources.incomes.index'),
            'Super Admin' => route('filament.superAdmin.pages.dashboard'),
            'Admin' => route('filament.hospitalAdmin.pages.dashboard'),
            'Receptionist' => route('filament.hospitalAdmin.patients'),
            'Case Manager' => route('filament.hospitalAdmin.doctors'),
            'Pharmacist', 'Lab Technician' => route('filament.hospitalAdmin.medicine'),
            'Nurse' => route('filament.hospitalAdmin.bed-management'),
            'Patient' => route('filament.hospitalAdmin.pages.dashboard'),
            default => Filament::getCurrentPanel()?->getUrl() ?? route('filament.hospitalAdmin.pages.dashboard'),
        };
    }
}
