<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Radiology extends Cluster
{
    protected static ?string $navigationIcon = 'fas-x-ray';

    protected static ?int $navigationSort = 23;

    public static function getNavigationLabel(): string
    {
        return __('messages.radiologies');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Pharmacist', 'Lab Technician']) && ! getModuleAccess('Radiology Tests')) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Radiology Tests') && ! getModuleAccess('Radiology Categories')) {
            return false;
        }

        return true;
    }
}
