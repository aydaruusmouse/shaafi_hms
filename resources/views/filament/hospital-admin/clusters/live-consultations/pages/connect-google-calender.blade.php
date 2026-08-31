<x-filament-panels::page>
    @if ($integrationExists)
        <div class="card-header border-0">
            <div class="card-title m-0">
                <span class="fs-5 fw-bold mt-3">{{ __('messages.google_meet.select_google_calendar') }}.</span>
            </div>
        </div>
    @endif
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page>
