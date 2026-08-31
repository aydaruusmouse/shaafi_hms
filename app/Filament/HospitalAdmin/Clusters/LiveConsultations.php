<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class LiveConsultations extends Cluster
{
    protected static ?string $navigationIcon = 'fas-video';

    protected static ?int $navigationSort = 18;

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

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Patient']) && ! getModuleAccess('Live Consultations')) {
            return false;
        } elseif (auth()->user()->hasRole(['Accountant', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse']) && ! getModuleAccess('Live Meetings')) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor']) && ! getModuleAccess('Live Meetings') && ! getModuleAccess('Live Consultations')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Live Meetings') && ! getModuleAccess('Live Consultations')) {
            return false;
        } elseif (auth()->user()->hasRole(['Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician', 'Nurse', 'Patient', 'Doctor']) && getModuleAccess('Live Meetings')) {
            return true;
        } elseif (! auth()->user()->hasRole(['Admin', 'Accountant']) && getModuleAccess('Live Consultations')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        if (! auth()->user()->hasRole(['Admin', 'Doctor', 'Patient'])) {
            return __('messages.live_meetings');
        }

        return __('messages.live_consultations');
    }

    public static function getLabel(): string
    {
        if (! auth()->user()->hasRole(['Admin', 'Doctor', 'Patient', 'Accountant'])) {
            return __('messages.live_meetings');
        }

        return __('messages.live_consultations');
    }
}
