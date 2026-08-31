<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\DoctorSuggestedTestsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\DoctorSuggestedTestsResource;
use Filament\Resources\Pages\ListRecords;

class ListDoctorSuggestedTests extends ListRecords
{
    protected static string $resource = DoctorSuggestedTestsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
