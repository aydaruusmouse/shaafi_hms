<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicineBrands extends ListRecords
{
    protected static string $resource = MedicineBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('messages.medicine.new_medicine_brand')),
        ];
    }
}
