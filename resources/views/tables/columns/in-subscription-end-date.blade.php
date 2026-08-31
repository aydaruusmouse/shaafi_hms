@if ($getRecord()->ends_at === null)
    N/A
@else
    <x-filament::badge>
        <div>{{ \Carbon\Carbon::parse($getRecord()->ends_at)->format('h:i A') }}
            <div>
                {{ \Carbon\Carbon::parse($getRecord()->ends_at)->translatedFormat('jS M, Y') }}
            </div>
        </div>
    </x-filament::badge>
@endif
