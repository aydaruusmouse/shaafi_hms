<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section>
            <x-slot name="heading">
                {{ __('messages.appointment.ticket_board') }}
            </x-slot>

            @include('forms.components.appointment-ticket-board', [
                'tickets' => $this->getTickets(),
            ])
        </x-filament::section>
    </div>
</x-filament-panels::page>
