@if ($getRecord()->issue_date === null)
    N/A
@else
    <x-filament::badge>
        <div>
            <div class="text-center">{{ \Carbon\Carbon::parse($getRecord()->issue_date)->format('h:i A') }}</div>
            <div>{{ \Carbon\Carbon::parse($getRecord()->issue_date)->translatedFormat('jS M, Y') }}</div>
        </div>
    </x-filament::badge>
@endif
