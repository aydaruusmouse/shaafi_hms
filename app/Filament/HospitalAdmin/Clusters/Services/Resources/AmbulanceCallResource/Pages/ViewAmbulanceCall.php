<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmbulanceCall extends ViewRecord
{
    protected static string $resource = AmbulanceCallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
