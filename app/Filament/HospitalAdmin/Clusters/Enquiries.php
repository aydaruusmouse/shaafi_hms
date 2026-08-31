<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Enquiries extends Cluster
{
    protected static ?string $navigationIcon = 'fas-circle-question';

    protected static ?int $navigationSort = 11;

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
        return __('messages.enquiries');
    }

    public static function getLabel(): string
    {
        return __('messages.enquiries');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient'])) {
            return false;
        } elseif (auth()->user()->hasRole('Doctor')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Enquires')) {
            return false;
        }

        return true;
    }
}
