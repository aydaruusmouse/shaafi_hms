<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources\DoctorSuggestedRadiologyTestsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\DoctorSuggestedRadiologyTestsResource;
use Filament\Resources\Pages\ListRecords;

class ListDoctorSuggestedRadiologyTests extends ListRecords
{
    protected static string $resource = DoctorSuggestedRadiologyTestsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
