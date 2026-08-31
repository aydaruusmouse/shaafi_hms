<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIpdPatients extends ListRecords
{
    protected static string $resource = IpdPatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
