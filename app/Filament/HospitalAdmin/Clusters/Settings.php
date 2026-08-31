<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static ?string $navigationIcon = 'fas-gears';

    protected static ?int $navigationSort = 26;

    public static function getNavigationLabel(): string
    {
        return __('messages.settings');
    }
}
