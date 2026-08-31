<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\UsedMedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\UsedMedicineResource;
use Filament\Resources\Pages\ListRecords;

class ListUsedMedicines extends ListRecords
{
    protected static string $resource = UsedMedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
