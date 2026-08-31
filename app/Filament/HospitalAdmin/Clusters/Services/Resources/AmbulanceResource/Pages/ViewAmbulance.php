<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAmbulance extends ViewRecord
{
    protected static string $resource = AmbulanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
