<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class SmsMail extends Cluster
{
    protected static ?string $navigationIcon = 'fas-bell';

    protected static ?int $navigationSort = 25;

    public function mount(): void
    {
        if (auth()->user()->hasRole('Patient')) {
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
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return __('messages.sms.sms_mail');
        } else {
            return __('messages.sms.sms');
        }
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Case Manager', 'Receptionist']) && ! getModuleAccess('SMS') && ! getModuleAccess('Mail')) {
            return false;
        } elseif (auth()->user()->hasRole(['Doctor', 'Pharmacist', 'Accountant']) && ! getModuleAccess('SMS')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('SMS') && ! getModuleAccess('Mail')) {
            return false;
        }
        if (auth()->user()->hasRole(['Patient', 'Lab Technician', 'Nurse'])) {
            return false;
        }

        return true;
    }
}
