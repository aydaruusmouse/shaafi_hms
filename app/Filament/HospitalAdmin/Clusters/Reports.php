<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Reports extends Cluster
{
    protected static ?int $navigationSort = 22;

    protected static ?string $navigationIcon = 'fas-file-medical';

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
        return __('messages.reports');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && ! getModuleAccess('Operation Reports') && ! getModuleAccess('Investigation Reports') && ! getModuleAccess('Death Reports') && ! getModuleAccess('Birth Reports')) {
            return false;
        }

        return true;
    }
}
