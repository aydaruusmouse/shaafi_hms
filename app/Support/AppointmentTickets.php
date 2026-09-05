<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorDailyTicket;
use Carbon\Carbon;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;
use Illuminate\Support\Facades\DB;

class AppointmentTickets
{
    public const DEFAULT_COUNT = 20;

    public static function count(?int $doctorId): int
    {
        if (! $doctorId) {
            return self::DEFAULT_COUNT;
        }

        $count = (int) Doctor::whereKey($doctorId)->value('ticket_count');

        return $count > 0 ? $count : self::DEFAULT_COUNT;
    }

    public static function dateValue($date): string
    {
        return Carbon::parse($date)->format('Y-m-d');
    }

    public static function ensureForDate(int $doctorId, $date): void
    {
        $ticketDate = self::dateValue($date);
        $capacity = self::count($doctorId);
        $tenantId = Doctor::whereKey($doctorId)->value('tenant_id');

        self::clearLegacyReservedTickets($doctorId, $ticketDate);

        $existing = DoctorDailyTicket::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', $ticketDate)
            ->pluck('ticket_number')
            ->map(fn ($n) => (int) $n)
            ->all();

        for ($ticket = 1; $ticket <= $capacity; $ticket++) {
            if (in_array($ticket, $existing, true)) {
                continue;
            }

            DoctorDailyTicket::create([
                'doctor_id' => $doctorId,
                'ticket_date' => $ticketDate,
                'ticket_number' => $ticket,
                'status' => DoctorDailyTicket::STATUS_AVAILABLE,
                'tenant_id' => $tenantId,
            ]);
        }
    }

    public static function syncDoctorCapacity(int $doctorId, int $newCount): void
    {
        self::ensureForDate($doctorId, now());

        DoctorDailyTicket::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', '>=', now()->toDateString())
            ->where('ticket_number', '>', $newCount)
            ->whereIn('status', [DoctorDailyTicket::STATUS_AVAILABLE, DoctorDailyTicket::STATUS_CANCELLED])
            ->delete();
    }

    public static function minAllowedCapacity(int $doctorId, $date = null): int
    {
        $date = self::dateValue($date ?? now());

        $maxTaken = DoctorDailyTicket::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', $date)
            ->where('status', DoctorDailyTicket::STATUS_TAKEN)
            ->max('ticket_number');

        return max(1, (int) $maxTaken);
    }

    public static function validateCapacityChange(int $doctorId, int $newCount, $date = null): ?string
    {
        $min = self::minAllowedCapacity($doctorId, $date);

        if ($newCount < $min) {
            return __('messages.appointment.ticket_capacity_below_booked', ['min' => $min]);
        }

        return null;
    }

    public static function clearLegacyReservedTickets(?int $doctorId = null, ?string $ticketDate = null): void
    {
        DoctorDailyTicket::query()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->when($ticketDate, fn ($q) => $q->whereDate('ticket_date', $ticketDate))
            ->where('status', DoctorDailyTicket::STATUS_RESERVED)
            ->update([
                'status' => DoctorDailyTicket::STATUS_AVAILABLE,
                'patient_id' => null,
                'appointment_id' => null,
                'reserved_until' => null,
            ]);
    }

    public static function ticketsForDate(int $doctorId, $date): \Illuminate\Support\Collection
    {
        self::ensureForDate($doctorId, $date);

        return DoctorDailyTicket::query()
            ->with('patient.patientUser')
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', self::dateValue($date))
            ->orderBy('ticket_number')
            ->get();
    }

    public static function options(?int $doctorId, $date = null): array
    {
        if (! $doctorId || ! $date) {
            return [];
        }

        return self::ticketsForDate($doctorId, $date)
            ->mapWithKeys(function (DoctorDailyTicket $ticket) {
                $label = __('messages.appointment.ticket_n', ['number' => $ticket->ticket_number]);
                $statusLabel = DoctorDailyTicket::statusOptions()[$ticket->status] ?? $ticket->status;

                if ($ticket->status === DoctorDailyTicket::STATUS_TAKEN && $ticket->patient?->patientUser) {
                    $label .= ' — '.$statusLabel.' ('.$ticket->patient->patientUser->full_name.')';
                } else {
                    $label .= ' — '.$statusLabel;
                }

                return [$ticket->ticket_number => $label];
            })
            ->all();
    }

    public static function isSelectable(int $doctorId, $date, int $ticketNumber, ?int $exceptAppointmentId = null): bool
    {
        self::ensureForDate($doctorId, $date);

        $ticket = DoctorDailyTicket::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', self::dateValue($date))
            ->where('ticket_number', $ticketNumber)
            ->first();

        if (! $ticket) {
            return false;
        }

        if ($exceptAppointmentId && (int) $ticket->appointment_id === $exceptAppointmentId) {
            return true;
        }

        if ($ticket->status === DoctorDailyTicket::STATUS_AVAILABLE) {
            return true;
        }

        return false;
    }

    public static function confirmTaken(Appointment $appointment): void
    {
        if (! $appointment->ticket_number) {
            return;
        }

        self::ensureForDate((int) $appointment->doctor_id, $appointment->opd_date);

        DB::transaction(function () use ($appointment) {
            $ticket = DoctorDailyTicket::query()
                ->where('doctor_id', $appointment->doctor_id)
                ->whereDate('ticket_date', self::dateValue($appointment->opd_date))
                ->where('ticket_number', $appointment->ticket_number)
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                throw new \RuntimeException(__('messages.appointment.ticket_taken'));
            }

            if (! in_array($ticket->status, [DoctorDailyTicket::STATUS_AVAILABLE, DoctorDailyTicket::STATUS_CANCELLED], true)
                && (int) $ticket->appointment_id !== (int) $appointment->id) {
                throw new \RuntimeException(__('messages.appointment.ticket_taken'));
            }

            $ticket->update([
                'status' => DoctorDailyTicket::STATUS_TAKEN,
                'patient_id' => $appointment->patient_id,
                'appointment_id' => $appointment->id,
                'reserved_until' => null,
            ]);
        });
    }

    public static function releaseTicket(int $doctorId, $date, int $ticketNumber, ?int $appointmentId = null, bool $makeAvailable = true): void
    {
        $status = $makeAvailable
            ? DoctorDailyTicket::STATUS_AVAILABLE
            : DoctorDailyTicket::STATUS_CANCELLED;

        DoctorDailyTicket::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', self::dateValue($date))
            ->where('ticket_number', $ticketNumber)
            ->when($appointmentId, fn ($query) => $query->where('appointment_id', $appointmentId))
            ->update([
                'status' => $status,
                'patient_id' => null,
                'appointment_id' => null,
                'reserved_until' => null,
            ]);
    }

    public static function releaseForAppointment(Appointment $appointment, bool $makeAvailable = true): void
    {
        if (! $appointment->ticket_number) {
            return;
        }

        self::releaseTicket(
            (int) $appointment->doctor_id,
            $appointment->opd_date,
            (int) $appointment->ticket_number,
            $appointment->id,
            $makeAvailable
        );
    }

    public static function handleAppointmentUpdate(Appointment $appointment, ?int $oldTicketNumber = null, $oldDate = null): void
    {
        if ($oldTicketNumber && ($oldTicketNumber !== (int) $appointment->ticket_number || self::dateValue($oldDate) !== self::dateValue($appointment->opd_date))) {
            self::releaseTicket((int) $appointment->doctor_id, $oldDate, $oldTicketNumber, $appointment->id);
        }

        if ($appointment->ticket_number) {
            self::confirmTaken($appointment);
        }
    }

    public static function label(?Appointment $appointment): string
    {
        if (! $appointment?->ticket_number) {
            return __('messages.common.n/a');
        }

        return __('messages.appointment.ticket_n', ['number' => $appointment->ticket_number]);
    }

    public static function bookingLabel($opdDate, ?int $ticketNumber): string
    {
        $date = Carbon::parse($opdDate)->translatedFormat('jS M, Y');

        if (! $ticketNumber) {
            return $date;
        }

        return __('messages.appointment.ticket_n', ['number' => $ticketNumber]).' · '.$date;
    }

    public static function validateSelection(array $input, ?int $exceptAppointmentId = null): ?string
    {
        if (empty($input['doctor_id']) || empty($input['opd_date']) || empty($input['ticket_number'])) {
            return __('messages.appointment.select_ticket');
        }

        if (! self::isSelectable((int) $input['doctor_id'], $input['opd_date'], (int) $input['ticket_number'], $exceptAppointmentId)) {
            return __('messages.appointment.ticket_taken');
        }

        return null;
    }

    public static function isOwnedByAppointment(int $doctorId, $date, int $ticketNumber, int $appointmentId): bool
    {
        $ticket = DoctorDailyTicket::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('ticket_date', self::dateValue($date))
            ->where('ticket_number', $ticketNumber)
            ->first();

        return $ticket
            && (int) $ticket->appointment_id === $appointmentId
            && $ticket->status === DoctorDailyTicket::STATUS_TAKEN;
    }

    public static function ticketPickerField(): ViewField
    {
        return ViewField::make('ticket_number')
            ->label(__('messages.appointment.available_tickets').':')
            ->view('forms.components.appointment-ticket-picker')
            ->viewData(fn (Get $get) => [
                'tickets' => filled($get('doctor_id')) && filled($get('opd_date'))
                    ? self::ticketsForDate((int) $get('doctor_id'), $get('opd_date'))
                    : collect(),
                'selectedTicket' => $get('ticket_number'),
                'exceptAppointmentId' => request()->route('record') ? (int) request()->route('record') : null,
            ])
            ->visible(fn (Get $get) => filled($get('doctor_id')) && filled($get('opd_date')))
            ->live()
            ->columnSpanFull()
            ->required()
            ->validationAttribute(__('messages.appointment.available_tickets'));
    }

    public static function clearTicketOnDoctorOrDateChange($livewire, callable $set): void
    {
        if (is_object($livewire) && method_exists($livewire, 'releaseFormTicketReservation')) {
            $livewire->releaseFormTicketReservation();

            return;
        }

        $set('ticket_number', null);
    }

    public static function normalize(array $input): array
    {
        if (! empty($input['opd_date'])) {
            $input['opd_date'] = Carbon::parse($input['opd_date'])->format('Y-m-d').' 00:00:00';
        }

        unset($input['time']);

        return $input;
    }
}
