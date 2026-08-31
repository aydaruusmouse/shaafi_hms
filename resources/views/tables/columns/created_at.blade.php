@if ($getRecord()->created_at === null)
    {{ __('messages.common.n/a') }}
@else
    <x-filament::badge>
        <div class="text-center">{{ \Carbon\Carbon::parse($getRecord()->created_at)->format('h:i A') }}
            <div>
                {{ \Carbon\Carbon::parse($getRecord()->created_at)->translatedFormat('jS M, Y') }}
            </div>
        </div>
    </x-filament::badge>
@endif
