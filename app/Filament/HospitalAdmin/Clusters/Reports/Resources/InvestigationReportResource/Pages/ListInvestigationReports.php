<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestigationReports extends ListRecords
{
    protected static string $resource = InvestigationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
