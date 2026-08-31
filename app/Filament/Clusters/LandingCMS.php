<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class LandingCMS extends Cluster
{
    protected static ?string $navigationIcon = 'fas-gear';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('messages.landing_cms.landing_cms');
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Super Admin')) {
            return true;
        }

        return false;
    }
}
