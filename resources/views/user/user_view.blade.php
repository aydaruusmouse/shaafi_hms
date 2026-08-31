
<x-filament::badge size="xs" wire:click="mountTableAction('view', '{{ $record->id }} ')" color="" style="cursor: pointer;font-size: 0.9rem;">
    {{ $record->full_name }}
</x-filament::badge>