<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Redirect;

class EditAppointmentTransaction extends EditRecord
{
    public function mount(int|string $record): void
    {
        Redirect::to($this->getResource()::getUrl('index'));

        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    protected static string $resource = AppointmentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
