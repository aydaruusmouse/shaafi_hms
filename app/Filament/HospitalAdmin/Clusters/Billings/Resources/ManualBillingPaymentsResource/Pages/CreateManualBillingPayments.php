<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManualBillingPayments extends CreateRecord
{
    protected static string $resource = ManualBillingPaymentsResource::class;
}
