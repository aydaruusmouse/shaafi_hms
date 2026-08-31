@if ($getRecord()->bill_date === null)
    N/A
@else
    <x-filament::badge>
        <div>{{ \Carbon\Carbon::parse($getRecord()->bill_date)->format('h:i A') }}
            <div>
                {{ \Carbon\Carbon::parse($getRecord()->bill_date)->translatedFormat('jS M, Y') }}
            </div>
        </div>
    </x-filament::badge>
@endif
