<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBedAssigns extends ListRecords
{
    protected static string $resource = BedAssignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
