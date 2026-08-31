@if ($getRecord()->starts_at === null)
    N/A
@else
    <x-filament::badge>
        <div>{{ \Carbon\Carbon::parse($getRecord()->starts_at)->format('h:i A') }}
            <div>
                {{ \Carbon\Carbon::parse($getRecord()->starts_at)->translatedFormat('jS M, Y') }}
            </div>
        </div>
    </x-filament::badge>
@endif
