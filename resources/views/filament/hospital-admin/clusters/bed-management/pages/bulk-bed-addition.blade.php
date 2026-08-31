<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}
        <div class="flex justify-end mt-4">
            <x-filament-panels::form.actions :actions="$this->getFormActions()" . />
        </div>
    </form>
</x-filament-panels::page>
