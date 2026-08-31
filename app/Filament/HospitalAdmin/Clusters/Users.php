<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Users extends Cluster
{
    protected static ?string $navigationIcon = 'fas-user-group';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.users');
    }
}
