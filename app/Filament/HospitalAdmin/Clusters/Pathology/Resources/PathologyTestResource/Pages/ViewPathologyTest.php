<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPathologyTest extends ViewRecord
{
    protected static string $resource = PathologyTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
