<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources\IssuedItemResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\IssuedItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIssuedItems extends ListRecords
{
    protected static string $resource = IssuedItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
