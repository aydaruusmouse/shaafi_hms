<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyReportPaymentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyReportPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListRadiologyReportPayments extends ListRecords
{
    protected static string $resource = RadiologyReportPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
