<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisResource;
use Filament\Resources\Pages\ListRecords;

class ListDiagnoses extends ListRecords
{
    protected static string $resource = DiagnosisResource::class;
}
