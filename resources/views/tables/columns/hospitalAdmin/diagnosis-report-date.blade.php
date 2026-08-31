@if ($getRecord()->report_date === null)
    N/A
@else
    <x-filament::badge class="text-sm" color="info">
        <div>{{ \Carbon\Carbon::parse($getRecord()->report_date)->format('h:i A') }}</div>
        <div>{{ \Carbon\Carbon::parse($getRecord()->report_date)->translatedFormat('jS M, Y') }}</div>
    </x-filament::badge>
@endif
