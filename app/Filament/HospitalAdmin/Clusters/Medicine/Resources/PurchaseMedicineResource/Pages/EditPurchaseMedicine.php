<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseMedicine extends EditRecord
{
    protected static string $resource = PurchaseMedicineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
