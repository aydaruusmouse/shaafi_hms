<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\UsedMedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\UsedMedicineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUsedMedicine extends EditRecord
{
    protected static string $resource = UsedMedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
