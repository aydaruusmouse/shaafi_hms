<?php

namespace App\Filament\HospitalAdmin\Clusters;

use Filament\Clusters\Cluster;

class Transactions extends Cluster
{
    protected static ?string $navigationIcon = 'fas-money-bill-wave';

    protected static ?int $navigationSort = 27;

    public static function getNavigationLabel(): string
    {
        return __('messages.subscription_plans.transactions');
    }
}
