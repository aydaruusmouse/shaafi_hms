<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\ServiceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewService extends ViewRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
