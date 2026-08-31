<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Prescription extends Cluster
{
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

    protected static ?string $navigationIcon = 'fas-file-prescription';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('messages.prescriptions');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Receptionist', 'Lab Technician', 'Nurse'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Patient']) && ! getModuleAccess('Prescriptions')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Pharmacist']) && ! getModuleAccess('Prescriptions')) {
            return false;
        }

        return true;
    }
}
