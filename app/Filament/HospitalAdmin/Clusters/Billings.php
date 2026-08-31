<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;
use Illuminate\Contracts\Support\Htmlable;

class Billings extends Cluster
{
    protected static ?int $navigationSort = 3;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Doctor')) {
            abort(404);
        } elseif (empty($this->getCachedSubNavigation())) {
            abort(404);
        }
        foreach ($this->getCachedSubNavigation() as $navigationGroup) {
            foreach ($navigationGroup->getItems() as $navigationItem) {
                redirect($navigationItem->getUrl());

                return;
            }
        }
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Patient', 'Receptionist']) && ! getModuleAccess('Bills')) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Accountant', 'Admin']) && ! getModuleAccess('Accounts') && ! getModuleAccess('Invoices') && ! getModuleAccess('Bills') && ! getModuleAccess('Employee Payrolls') && ! getModuleAccess('Payments') && ! getModuleAccess('Payment Reports') && ! getModuleAccess('Advance Payments')) {
            return false;
        }

        return true;
    }

    public static function getNavigationIcon(): string|Htmlable|null
    {
        if (auth()->user()->hasRole('Accountant')) {
            return 'fab-adn';
        }

        return 'fas-file-invoice-dollar';
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()->hasRole('Patient')) {
            return __('messages.bills');
        } elseif (auth()->user()->hasRole('Accountant')) {
            return __('messages.account_manager');
        }

        return __('messages.billings');
    }
}
