@if ($getRecord()->created_at === null)
    {{ __('messages.common.n/a') }}
@else
    <x-filament::badge>
        <div class="text-sm">{{ \Carbon\Carbon::parse($getRecord()->created_at)->isoFormat('LT') }}
            <div class="text-sm">
                {{ \Carbon\Carbon::parse($getRecord()->created_at)->translatedFormat('jS M, Y') }}
            </div>
        </div>
    </x-filament::badge>
@endif
