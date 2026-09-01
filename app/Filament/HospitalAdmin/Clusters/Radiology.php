<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Radiology extends Cluster
{
    protected static ?string $navigationIcon = 'fas-x-ray';

    protected static ?int $navigationSort = 23;

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
        return __('messages.radiologies');
    }

    public static function canAccessClusteredComponents(): bool
    {
        $hasRadiologyModule = getModuleAccess('Radiology Tests')
            || getModuleAccess('Radiology Categories')
            || getModuleAccess('Radiology Report Payments')
            || getModuleAccess('Doctor Suggested Radiology Tests');

        if (auth()->user()->hasRole(['Pharmacist', 'Lab Technician']) && ! $hasRadiologyModule) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! $hasRadiologyModule) {
            return false;
        }

        return true;
    }
}
