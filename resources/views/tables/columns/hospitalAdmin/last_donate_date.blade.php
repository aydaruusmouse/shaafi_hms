@if ($getRecord()->last_donate_date)
<x-filament::badge>
    <div>
        <div>{{ \Carbon\Carbon::parse($getRecord()->last_donate_date)->format('h:i A') }}</div>
        <div> {{ \Carbon\Carbon::parse($getRecord()->last_donate_date)->translatedFormat('jS M, Y') }}</div>
    </div>
</x-filament::badge>
@else
{{ __('messages.common.n/a') }}
@endif
