<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Redirect;

class CreateAppointmentTransaction extends CreateRecord
{
    public function mount(): void
    {
        Redirect::to($this->getResource()::getUrl('index'));

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    protected static string $resource = AppointmentTransactionResource::class;
}
