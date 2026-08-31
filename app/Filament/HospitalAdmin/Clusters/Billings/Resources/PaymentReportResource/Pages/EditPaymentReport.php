<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentReport extends EditRecord
{
    protected static string $resource = PaymentReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
