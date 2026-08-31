<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class FrontCms extends Cluster
{
    protected static ?string $navigationIcon = 'fas-cog';

    protected static ?int $navigationSort = 14;

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
        if (auth()->user()->hasRole(['Receptionist'])) {
            return __('messages.front_settings');
        }

        return __('messages.front_cms');
    }

    public static function getLabel(): string
    {
        return __('messages.front_cms');
    }

    public static function canAccessClusteredComponents(): bool
    {
        if (auth()->user()->hasRole(['Receptionist']) && ! getModuleAccess('Testimonial') && ! getModuleAccess('Notice Boards')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin'])) {
            return false;
        }

        return true;
    }
}
