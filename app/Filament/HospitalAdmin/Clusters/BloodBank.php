<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class BloodBank extends Cluster
{
    protected static ?string $navigationIcon = 'fas-tint';

    protected static ?int $navigationSort = 5;

    public function mount(): void
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Blood Issues') && ! getModuleAccess('Blood Donations') && ! getModuleAccess('Blood Donors') && ! getModuleAccess('Blood Banks')) {
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
        return __('messages.blood_bank');
    }

    public static function getLabel(): string
    {
        return __('messages.blood_bank');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Lab Technician']) && ! getModuleAccess('Blood Issues') && ! getModuleAccess('Blood Donations') && ! getModuleAccess('Blood Donors') && ! getModuleAccess('Blood Banks')) {
            return false;
        }

        return true;
    }
}
