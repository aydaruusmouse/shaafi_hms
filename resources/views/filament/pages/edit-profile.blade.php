<x-filament-panels::page>
    <x-filament-panels::form wire:submit.prevent="save">
        {{ $this->form }}
        <div>
            <x-filament::button type="submit" class="px-6 mx-3" wire:loading.attr="disabled">Save</x-filament::button>
            {{-- <x-filament-panels::form.actions :actions="$this->getFormActions()" . /> --}}
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
