<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Services extends Cluster
{
    protected static ?string $navigationIcon = 'fas-box';

    protected static ?int $navigationSort = 24;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Insurances') && ! getModuleAccess('Packages') && ! getModuleAccess('Ambulances Calls') && ! getModuleAccess('Ambulances') && ! getModuleAccess('Services')) {
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

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Case Manager']) && ! getModuleAccess('Ambulances Calls') && ! getModuleAccess('Ambulances')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Accountant', 'Case Manager', 'Receptionist'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Accountant']) && ! getModuleAccess('Services')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Insurances') && ! getModuleAccess('Packages') && ! getModuleAccess('Ambulances Calls') && ! getModuleAccess('Ambulances') && ! getModuleAccess('Services')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.services');
    }
}
