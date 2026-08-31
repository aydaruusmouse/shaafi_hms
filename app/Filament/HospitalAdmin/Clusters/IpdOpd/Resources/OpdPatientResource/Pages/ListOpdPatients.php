<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOpdPatients extends ListRecords
{
    protected static string $resource = OpdPatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
