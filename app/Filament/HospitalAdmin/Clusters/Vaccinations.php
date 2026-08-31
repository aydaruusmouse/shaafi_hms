<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Vaccinations extends Cluster
{
    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Vaccinations') && ! getModuleAccess('Vaccinated Patients')) {
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

    protected static ?string $navigationIcon = 'fas-syringe';

    protected static ?int $navigationSort = 28;

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Patient']) && ! getModuleAccess('Vaccinated Patients')) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse'])) {
            return false;
        } elseif (! auth()->user()->hasRole(['Accountant', 'Doctor', 'Patient', 'Nurse', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Case Manager']) && ! getModuleAccess('Vaccinated Patients') && ! getModuleAccess('Vaccinations')) {
            return false;
        }

        // if (!auth()->user()->hasRole(['Admin']) && !getModuleAccess('Vaccinated Patients') && !getModuleAccess('Vaccinations')) {
        //     return false;
        // }
        return true;
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()->hasRole('Patient')) {
            return __('messages.vaccinated_patients');
        }

        return __('messages.vaccinations');
    }

    public static function getLabel(): string
    {
        if (auth()->user()->hasRole('Patient')) {
            return __('messages.vaccinated_patients');
        }

        return __('messages.vaccinations');
    }
}
