<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSmartPatientCards extends ListRecords
{
    protected static string $resource = SmartPatientCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
