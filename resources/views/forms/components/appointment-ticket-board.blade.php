@include('forms.components.partials.appointment-ticket-styles')

@if ($tickets->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ __('messages.appointment.select_doctor_and_date_for_tickets') }}
    </p>
@else
    <div class="appointment-ticket-row">
        @foreach ($tickets as $ticket)
            @include('forms.components.partials.appointment-ticket-card', [
                'ticket' => $ticket,
                'interactive' => false,
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
