<x-filament::badge>
<div class="text-center">
    <div>{{ \Carbon\Carbon::parse($getRecord()->admission_date)->isoFormat('LT') }}</div>
    <div>{{ \Carbon\Carbon::parse($getRecord()->admission_date)->translatedFormat('jS M, Y') }}</div>
</div>
</x-filament::badge>
