<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicineBills extends ListRecords
{
    protected static string $resource = MedicineBillsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
