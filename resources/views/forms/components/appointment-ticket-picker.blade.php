@php
    use App\Support\AppointmentTickets;

    $exceptAppointmentId = $exceptAppointmentId ?? null;
    $selectedTicket = $selectedTicket ?? $getState();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @include('forms.components.partials.appointment-ticket-styles')

    @if ($tickets->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('messages.common.no_data_found') }}
        </p>
    @else
        <div class="appointment-ticket-row">
            @foreach ($tickets as $ticket)
                @include('forms.components.partials.appointment-ticket-card', [
                    'ticket' => $ticket,
                    'interactive' => true,
                    'isSelectable' => AppointmentTickets::isSelectable(
                        (int) $ticket->doctor_id,
                        $ticket->ticket_date,
                        (int) $ticket->ticket_number,
                        $exceptAppointmentId,
                    ),
                    'isSelected' => (int) $selectedTicket === (int) $ticket->ticket_number,
                ])
            @endforeach
        </div>

        <div class="appointment-ticket-legend">
            <span>
                <span class="appointment-ticket-legend-dot appointment-ticket-legend-dot--available"></span>
                {{ __('messages.appointment.ticket_available') }}
            </span>
            <span>
                <span class="appointment-ticket-legend-dot appointment-ticket-legend-dot--taken"></span>
                {{ __('messages.appointment.ticket_taken_status') }}
            </span>
        </div>
    @endif
</x-dynamic-component>
