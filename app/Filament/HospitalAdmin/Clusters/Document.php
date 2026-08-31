<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Document extends Cluster
{
    protected static ?string $navigationIcon = 'fas-file';

    protected static ?int $navigationSort = 6;

    public function mount(): void
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Documents') && ! getModuleAccess('Document Types')) {
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
        return __('messages.documents');
    }

    public static function getLabel(): string
    {
        return __('messages.documents');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse'])) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Patient']) && ! getModuleAccess('Documents')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Documents') && ! getModuleAccess('Document Types')) {
            return false;
        }

        return true;
    }
}
