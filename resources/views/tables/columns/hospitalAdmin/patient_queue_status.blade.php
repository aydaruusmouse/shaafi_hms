@if ($getRecord())
    <x-filament::input.wrapper>
        {{-- <x-filament::input.select
            x-on:change="$wire.call('changeStatus', {{ $getRecord()->id }}, $el.value)">
            <option value="0" {{ $getRecord()->appointment->is_completed == 0 ? 'Selected' : '' }}>{{ __('messages.appointment.pending') }}</option>
            <option value="4" {{ $getRecord()->appointment->is_completed == 4 ? 'Selected' : '' }}>In Queue</option>
            <option value="5" {{ $getRecord()->appointment->is_completed == 5 ? 'Selected' : '' }}>Check In</option>
            <option value="6" {{ $getRecord()->appointment->is_completed == 1 ? 'Selected' : '' }}>Check Out</option>
        </x-filament::input.select> --}}
        @if($getRecord()->appointment->is_completed == 4)
            <x-filament::badge
            color="info">{{ __('In Queue') }}</x-filament::badge>
        @elseif ($getRecord()->appointment->is_completed == 5)
            <x-filament::badge
            color="success">{{ __('Check In') }}</x-filament::badge>
        @endif
    </x-filament::input.wrapper>
@endif
