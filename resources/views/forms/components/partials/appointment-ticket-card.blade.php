@php
    use App\Models\DoctorDailyTicket;

    $statusClass = match ($ticket->status) {
        DoctorDailyTicket::STATUS_AVAILABLE, DoctorDailyTicket::STATUS_RESERVED => 'appointment-ticket-chip--available',
        DoctorDailyTicket::STATUS_TAKEN => 'appointment-ticket-chip--taken',
        DoctorDailyTicket::STATUS_CANCELLED => 'appointment-ticket-chip--cancelled',
        default => 'appointment-ticket-chip--default',
    };

    $isSelected = $isSelected ?? false;
    $isSelectable = $isSelectable ?? true;
    $interactive = $interactive ?? false;

    $chipClass = 'appointment-ticket-chip '.$statusClass;

    if ($isSelected) {
        $chipClass .= ' appointment-ticket-chip--selected';
    }

    if (! $isSelectable && ! $isSelected) {
        $chipClass .= ' appointment-ticket-chip--disabled';
    }

    $statusLabel = DoctorDailyTicket::statusOptions()[$ticket->status] ?? $ticket->status;
    $patientName = $ticket->patient?->patientUser?->full_name;
    $tooltip = $patientName
        ? __('messages.appointment.ticket_n', ['number' => $ticket->ticket_number]).' — '.$statusLabel.' ('.$patientName.')'
        : __('messages.appointment.ticket_n', ['number' => $ticket->ticket_number]).' — '.$statusLabel;
@endphp

@if ($interactive && ($isSelectable || $isSelected))
    <button
        type="button"
        wire:click="selectAppointmentTicket({{ (int) $ticket->ticket_number }})"
        wire:key="ticket-card-{{ $ticket->ticket_number }}"
        class="{{ $chipClass }}"
        title="{{ e($tooltip) }}"
    >
        {{ $ticket->ticket_number }}
    </button>
@else
    <span
        wire:key="ticket-card-{{ $ticket->ticket_number }}"
        class="{{ $chipClass }}"
        title="{{ e($tooltip) }}"
    >
        {{ $ticket->ticket_number }}
    </span>
@endif
