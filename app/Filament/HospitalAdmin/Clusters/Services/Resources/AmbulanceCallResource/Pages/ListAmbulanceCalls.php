<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAmbulanceCalls extends ListRecords
{
    protected static string $resource = AmbulanceCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
