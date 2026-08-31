<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Pathology extends Cluster
{
    protected static ?string $navigationIcon = 'fas-flask';

    protected static ?int $navigationSort = 21;

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
        return __('messages.pathologies');
    }

    public static function canAccessClusteredComponents(): bool
    {
        $hasPathologyModule = getModuleAccess('Pathology Tests')
            || getModuleAccess('Pathology Categories')
            || getModuleAccess('Doctor Suggested Tests')
            || getModuleAccess('Lab Report Payments');

        if (auth()->user()->hasRole(['Pharmacist', 'Lab Technician']) && ! $hasPathologyModule) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Accountant', 'Case Manager', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! $hasPathologyModule) {
            return false;
        }

        return true;
    }
}
