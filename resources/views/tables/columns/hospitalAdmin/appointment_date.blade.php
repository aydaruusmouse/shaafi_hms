<x-filament::badge>
<div class="text-center">
    <div>{{ \Carbon\Carbon::parse($getRecord()->appointment_date)->isoFormat('LT') }}</div>
    <div>{{ \Carbon\Carbon::parse($getRecord()->appointment_date)->translatedFormat('jS M, Y') }}</div>
</div>
</x-filament::badge>
