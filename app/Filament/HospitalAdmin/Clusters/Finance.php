<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Finance extends Cluster
{
    protected static ?string $navigationIcon = 'fas-money-bill';

    protected static ?int $navigationSort = 12;

    public function mount(): void
    {
        if (empty($this->getCachedSubNavigation())) {
            abort(404);
        }
        foreach ($this->getCachedSubNavigation() as $navigationGroup) {
            foreach ($navigationGroup->getItems() as $navigationItem) {
                redirect($navigationItem->getUrl());

                return;
            }
        }
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.finance');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Accountant']) && ! getModuleAccess('Income') && ! getModuleAccess('Expense')) {
            return false;
        }

        return true;
    }
}
