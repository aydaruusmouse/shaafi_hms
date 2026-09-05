<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Support\AppointmentTickets;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        if ($appointment->ticket_number) {
            AppointmentTickets::confirmTaken($appointment);
        }
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->wasChanged('is_completed') && (int) $appointment->is_completed === Appointment::STATUS_CANCELLED) {
            AppointmentTickets::releaseForAppointment($appointment, true);
        }
    }
}
