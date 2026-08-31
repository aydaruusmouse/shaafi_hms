<x-filament::button wire:click="mountTableAction('view', '{{ $record->id }} ')" size="xs" color="" outlined>
    <x-filament::badge color="info">
        {{ $record->patient_admission_id ?? __('messages.common.n/a') }}
    </x-filament::badge>
</x-filament::button>
