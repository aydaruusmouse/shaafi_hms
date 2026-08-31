<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class BedManagement extends Cluster
{
    protected static ?string $navigationIcon = 'fas-bed';

    protected static ?int $navigationSort = 4;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Bed Types') && ! getModuleAccess('Beds') && ! getModuleAccess('Bed Assigns')) {
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
        return __('messages.bed_management');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Doctor']) && ! getModuleAccess('Bed Assigns')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Nurse']) && ! getModuleAccess('Bed Types') && ! getModuleAccess('Beds') && ! getModuleAccess('Bed Assigns')) {
            return false;
        }

        return ! auth()->user()->hasRole(['Patient', 'Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician']);
    }
}
