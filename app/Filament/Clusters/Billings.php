<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Billings extends Cluster
{
    protected static ?string $navigationIcon = 'fas-file-invoice-dollar';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('messages.billings');
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }
}
