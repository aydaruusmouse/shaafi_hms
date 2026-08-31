<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Diagnosis extends Cluster
{
    protected static ?string $navigationIcon = 'fas-person-dots-from-line';

    protected static ?int $navigationSort = 9;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Tests') && ! getModuleAccess('Diagnosis Categories')) {
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
        if (auth()->user()->hasRole('Patient')) {
            return __('messages.patient_diagnosis_test.diagnosis_test');
        }

        return __('messages.patient_diagnosis_test.diagnosis');
    }

    public static function getLabel(): string
    {
        return __('messages.patient_diagnosis_test.diagnosis');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Pharmacist', 'Nurse'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Patient']) && ! getModuleAccess('Diagnosis Tests')) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Receptionist', 'Lab Technician']) && ! getModuleAccess('Diagnosis Tests') && ! getModuleAccess('Diagnosis Categories')) {
            return false;
        }
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Tests') && ! getModuleAccess('Diagnosis Categories')) {
            return false;
        }

        return true;
    }
}
