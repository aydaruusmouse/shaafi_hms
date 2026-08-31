<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class FrontOffice extends Cluster
{
    protected static ?string $navigationIcon = 'fas-dungeon';

    protected static ?int $navigationSort = 13;

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
        return __('messages.front_office');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Doctor', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Visitor') && ! getModuleAccess('Call Logs')) {
            return false;
        }

        return true;
    }
}
