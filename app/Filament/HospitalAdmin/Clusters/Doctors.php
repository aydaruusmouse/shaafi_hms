<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Doctors extends Cluster
{
    protected static ?string $navigationIcon = 'fas-user-doctor';

    protected static ?int $navigationSort = 7;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Doctors') && ! getModuleAccess('Doctor Departments') && ! getModuleAccess('Schedules')) {
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
        return __('messages.doctors');
    }

    public static function getLabel(): string
    {
        return __('messages.doctors');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician']) && ! getModuleAccess('Doctors')) {
            return false;
        } elseif (auth()->user()->hasRole('Admin') && ! getModuleAccess('Doctors') && ! getModuleAccess('Doctor Departments') && ! getModuleAccess('Schedules')) {
            return false;
        }

        return true;
    }
}
