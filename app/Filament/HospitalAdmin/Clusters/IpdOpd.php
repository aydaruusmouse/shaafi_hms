<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class IpdOpd extends Cluster
{
    protected static ?string $navigationIcon = 'fas-notes-medical';

    protected static ?int $navigationSort = 16;

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
        return __('messages.ipd_opd');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Pharmacist', 'Lab Technician'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse']) && ! getModuleAccess('IPD Patients') && ! getModuleAccess('OPD Patients')) {
            return false;
        }

        return true;
    }
}
