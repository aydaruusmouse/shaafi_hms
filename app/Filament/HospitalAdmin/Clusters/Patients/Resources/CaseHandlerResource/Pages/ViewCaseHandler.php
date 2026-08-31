<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseHandlerResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseHandlerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCaseHandler extends ViewRecord
{
    protected static string $resource = CaseHandlerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
