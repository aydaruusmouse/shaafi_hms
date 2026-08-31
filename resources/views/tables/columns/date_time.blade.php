@if ($getRecord()->created_at === null)
    N/A
@else
    <x-filament::badge>
    <div>{{ \Carbon\Carbon::parse($getRecord()->date)->format('h:i A') }}
        <div>
            {{ \Carbon\Carbon::parse($getRecord()->date)->translatedFormat('jS M, Y') }}
        </div>
    </div>
    </x-filament::badge>
@endif
