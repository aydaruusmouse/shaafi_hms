<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManualBillingPayments extends EditRecord
{
    protected static string $resource = ManualBillingPaymentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
