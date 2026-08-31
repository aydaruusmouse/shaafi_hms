<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\LabReportPaymentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\LabReportPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListLabReportPayments extends ListRecords
{
    protected static string $resource = LabReportPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
