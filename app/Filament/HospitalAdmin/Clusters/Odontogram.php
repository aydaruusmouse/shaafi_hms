<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Odontogram extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?int $navigationSort = 16;

    public static function getNavigationLabel(): string
    {
        return __('messages.odontogram.odontogram');
    }

    public static function getLabel(): string
    {
        return __('messages.odontogram.odontogram');
    }

    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin', 'Doctor', 'Patient', 'Nurse', 'Receptionist') && !getModuleAccess('Odontograms')) {
            abort(404);
        } elseif (!auth()->user()->hasRole('Admin', 'Doctor', 'Patient', 'Nurse', 'Receptionist') && !getModuleAccess('Odontograms')) {
            abort(404);
        }
        foreach ($this->getCachedSubNavigation() as $navigationGroup) {
            foreach ($navigationGroup->getItems() as $navigationItem) {
                redirect($navigationItem->getUrl());
                return;
            }
        }
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient', 'Nurse', 'Receptionist']) && getModuleAccess('Odontograms')) {
            return true;
        } elseif (auth()->user()->hasRole(['Patient']) && getModuleAccess('Odontograms')) {
            return true;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient', 'Nurse', 'Receptionist']) && !getModuleAccess('Odontograms')) {
            return false;
        }
        return false;
    }
}