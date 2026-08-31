<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBreaks extends ListRecords
{
    protected static string $resource = BreaksResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
