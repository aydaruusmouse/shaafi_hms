<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'fas-gears';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('messages.settings');
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Super Admin')) {
            return true;
        }

        return false;
    }
}
