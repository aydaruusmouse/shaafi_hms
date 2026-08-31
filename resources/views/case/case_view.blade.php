<x-filament::button wire:click="mountTableAction('view', '{{ $record->id }} ')" size="xs" color="" outlined>
    <x-filament::badge color="info">
        {{ $record->case_id }}
    </x-filament::badge>
</x-filament::button>
