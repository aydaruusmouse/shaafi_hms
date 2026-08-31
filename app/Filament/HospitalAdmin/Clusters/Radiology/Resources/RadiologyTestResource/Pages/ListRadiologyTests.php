<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRadiologyTests extends ListRecords
{
    protected static string $resource = RadiologyTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
