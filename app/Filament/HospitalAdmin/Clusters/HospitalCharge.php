<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class HospitalCharge extends Cluster
{
    protected static ?string $navigationIcon = 'fas-coins';

    protected static ?int $navigationSort = 15;

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
        return __('messages.hospital_charges');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Doctor', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Doctor OPD Charges') && ! getModuleAccess('Charges') && ! getModuleAccess('Charge Categories')) {
            return false;
        }

        return true;
    }
}
