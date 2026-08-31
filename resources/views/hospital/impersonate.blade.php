@if ($getRecord())
    @if (auth()->user()->hasRole('Super Admin') && $getRecord()->email_verified_at != null)
        <x-filament::button x-on:click="$wire.call('impersonate', {{ $getRecord()->id }})" color="primary" size="sm">
            {{ __('messages.impersonate') }}
        </x-filament::button>
    @else
        <x-filament::button color="secondary" size="sm" disabled>
            {{ __('messages.impersonate') }}
        </x-filament::button>
    @endif
@endif
