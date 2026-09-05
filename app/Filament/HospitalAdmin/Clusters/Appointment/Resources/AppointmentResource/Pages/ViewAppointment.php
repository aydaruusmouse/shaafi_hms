<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Support\AppointmentTickets;
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
            Section::make(__('messages.appointment.appointment_details'))
                ->schema([
                    TextEntry::make('patient.user.full_name')
                        ->label(__('messages.case.patient').':'),
                    TextEntry::make('doctor.user.full_name')
                        ->label(__('messages.case.doctor').':'),
                    TextEntry::make('department.title')
                        ->label(__('messages.appointment.doctor_department').':'),
                    TextEntry::make('opd_date')
                        ->label(__('messages.appointment.date').':')
                        ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('jS M, Y')),
                    TextEntry::make('ticket_number')
                        ->label(__('messages.appointment.ticket').':')
                        ->formatStateUsing(fn ($state) => $state ? __('messages.appointment.ticket_n', ['number' => $state]) : __('messages.common.n/a')),
                    TextEntry::make('is_completed')
                        ->label(__('messages.common.status').':')
                        ->formatStateUsing(fn ($state) => Appointment::STATUS_ARR[(string) $state] ?? $state),
                    TextEntry::make('problem')
                        ->label(__('messages.common.description').':')
                        ->formatStateUsing(fn ($state) => $state ?: __('messages.common.n/a')),
                ])->columns(2),
        ]);
    }
}
