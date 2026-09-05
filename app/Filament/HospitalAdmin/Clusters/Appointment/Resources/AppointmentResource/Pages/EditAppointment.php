<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Repositories\AppointmentTransactionRepository;
use App\Filament\HospitalAdmin\Clusters\Appointment\Concerns\HandlesAppointmentTicketSelection;
use App\Support\AppointmentTickets;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAppointment extends EditRecord
{
    use HandlesAppointmentTicketSelection;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),

        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($error = AppointmentTickets::validateSelection($data, $record->id)) {
            Notification::make()
                ->danger()
                ->title($error)
                ->send();
            $this->halt();
        }

        $data = AppointmentTickets::normalize($data);

        $oldTicket = $record->ticket_number;
        $oldDate = $record->opd_date;

        $record = parent::handleRecordUpdate($record, $data);

        AppointmentTickets::handleAppointmentUpdate($record, $oldTicket ? (int) $oldTicket : null, $oldDate);

        if (in_array((int) $record->payment_type, [
            \App\Models\Appointment::TYPE_CASH,
            \App\Models\Appointment::CHEQUE,
        ], true)) {
            app(AppointmentTransactionRepository::class)->store($record);
        }

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.appointment_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
