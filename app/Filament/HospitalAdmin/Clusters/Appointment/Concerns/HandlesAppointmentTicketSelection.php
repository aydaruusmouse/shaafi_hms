<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Concerns;

use App\Support\AppointmentTickets;
use Filament\Notifications\Notification;

trait HandlesAppointmentTicketSelection
{
    public function selectAppointmentTicket(int $ticketNumber): void
    {
        $state = $this->resolveAppointmentFormState();
        $doctorId = (int) ($state['doctor_id'] ?? 0);
        $opdDate = $state['opd_date'] ?? null;
        $exceptId = $this->resolveExceptAppointmentId();

        if (! $doctorId || ! $opdDate) {
            return;
        }

        if ($exceptId && AppointmentTickets::isOwnedByAppointment($doctorId, $opdDate, $ticketNumber, $exceptId)) {
            $this->assignAppointmentFormState(['ticket_number' => $ticketNumber]);

            return;
        }

        if (! AppointmentTickets::isSelectable($doctorId, $opdDate, $ticketNumber, $exceptId)) {
            Notification::make()
                ->danger()
                ->title(__('messages.appointment.ticket_taken'))
                ->send();
            $this->assignAppointmentFormState(['ticket_number' => null]);

            return;
        }

        $this->assignAppointmentFormState(['ticket_number' => $ticketNumber]);
    }

    public function releaseFormTicketReservation(): void
    {
        $this->assignAppointmentFormState(['ticket_number' => null]);
    }

    protected function resolveExceptAppointmentId(): ?int
    {
        if (property_exists($this, 'record') && $this->record?->exists) {
            return (int) $this->record->id;
        }

        $recordId = request()->route('record');

        return $recordId ? (int) $recordId : null;
    }

    protected function resolveAppointmentFormState(): array
    {
        if (is_array($this->data ?? null) && (isset($this->data['doctor_id']) || isset($this->data['opd_date']))) {
            return $this->data;
        }

        foreach ([
            'mountedActionsData.0.data',
            'mountedActionsData.0',
            'mountedActionsData.data',
            'mountedActionsData',
        ] as $path) {
            $state = data_get($this, $path);

            if (is_array($state) && (isset($state['doctor_id']) || isset($state['opd_date']))) {
                return $state;
            }
        }

        if (method_exists($this, 'form') && isset($this->form)) {
            try {
                return $this->form->getState();
            } catch (\Throwable) {
                //
            }
        }

        return is_array($this->data ?? null) ? $this->data : [];
    }

    protected function assignAppointmentFormState(array $patch): void
    {
        if (is_array($this->data ?? null)) {
            foreach ($patch as $key => $value) {
                $this->data[$key] = $value;
            }

            return;
        }

        foreach ([
            'mountedActionsData.0.data',
            'mountedActionsData.0',
        ] as $path) {
            $state = data_get($this, $path);

            if (is_array($state)) {
                foreach ($patch as $key => $value) {
                    data_set($this, $path.'.'.$key, $value);
                }

                return;
            }
        }

        if (method_exists($this, 'form') && isset($this->form)) {
            try {
                $this->form->fill(array_merge($this->resolveAppointmentFormState(), $patch));
            } catch (\Throwable) {
                //
            }
        }
    }
}
