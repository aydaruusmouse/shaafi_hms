<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePatientQueue extends CreateRecord
{
    protected static string $resource = PatientQueueResource::class;
}
