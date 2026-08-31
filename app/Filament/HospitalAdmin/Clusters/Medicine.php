<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Medicine extends Cluster
{
    protected static ?string $navigationIcon = 'fas-capsules';

    protected static ?int $navigationSort = 19;

    public function mount(): void
    {
        if (auth()->user()->hasRole(['Pharmacist', 'Lab Technician']) && ! getModuleAccess('Medicines') && ! getModuleAccess('Medicine Categories') && ! getModuleAccess('Medicine Brands')) {
            abort(404);
        }
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
        return __('messages.medicines');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Receptionist', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && ! getModuleAccess('Medicines') && ! getModuleAccess('Medicine Categories') && ! getModuleAccess('Medicine Brands')) {
            return false;
        }

        return true;
    }
}
