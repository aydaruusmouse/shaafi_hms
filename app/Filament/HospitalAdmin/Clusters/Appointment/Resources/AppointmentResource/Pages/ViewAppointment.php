<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('')
                ->schema([
                    TextEntry::make('patient_id')
                        ->badge()
                        ->prefix('#')
                        ->label(__('messages.purchase_medicine.purchase_number').':'),
                    TextEntry::make('doctor_id')
                        ->label(__('messages.purchase_medicine.total').':'),
                    TextEntry::make('department_id')
                        ->label(__('messages.purchase_medicine.tax_amount').':'),
                    TextEntry::make('opd_date')
                        ->label(__('messages.purchase_medicine.discount').':'),
                    TextEntry::make('is_completed')
                        ->label(__('messages.purchase_medicine.net_amount').':'),
                    TextEntry::make('problem')
                        ->label(__('messages.purchase_medicine.note').':')
                        ->formatStateUsing(fn ($state) => $state ?: __('messages.common.n/a')),
                ])->columns(3),
        ]);
    }
}
