<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Appointment extends Cluster
{
    protected static ?string $navigationIcon = 'fas-calendar-check';

    protected static ?int $navigationSort = 2;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Appointments')) {
            abort(404);
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Appointments')) {
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
        return __('messages.appointments');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist']) && getModuleAccess('Appointments')) {
            return true;
        } elseif (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Patient']) && getModuleAccess('Appointments')) {
            return true;
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Appointments')) {
            return false;
        }

        return false;
    }
}
