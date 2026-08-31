<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPatientQueue extends EditRecord
{
    protected static string $resource = PatientQueueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
