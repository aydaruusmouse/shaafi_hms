<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppointmentTransactions extends ListRecords
{
    protected static string $resource = AppointmentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
