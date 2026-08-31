<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
