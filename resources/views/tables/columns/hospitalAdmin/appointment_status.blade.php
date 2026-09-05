@if ($getRecord())
    @if ($getRecord()->is_completed == 0)
        <x-filament::input.wrapper>
            <x-filament::input.select
                x-on:change="$wire.call('changeStatus', {{ $getRecord()->id }}, $el.value)">
                <option value="0">{{ __('messages.appointment.pending') }}</option>
                <option value="4">In Queue</option>
            </x-filament::input.select>
        </x-filament::input.wrapper>
    @else
        @if($getRecord()->is_completed == 1)
            <x-filament::badge
            color="success">{{ __('messages.appointment.completed') }}</x-filament::badge>
        @elseif ($getRecord()->is_completed == 3)
            <x-filament::badge
            color="danger">{{ __('messages.common.canceled') }}</x-filament::badge>
        @elseif ($getRecord()->is_completed == 4)
            <x-filament::badge
            color="info">{{ __('In Queue') }}</x-filament::badge>
        @elseif ($getRecord()->is_completed == 5)
            <x-filament::badge
            color="primary">{{ __('messages.appointment.check_in') }}</x-filament::badge>
        @elseif ($getRecord()->is_completed == 9)
            <x-filament::badge
            color="warning">{{ __('messages.appointment.in_vital') }}</x-filament::badge>
        @endif
    @endif
@endif
