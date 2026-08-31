<div>
    <x-filament-panels::form wire:submit="resetPassword">
        <div class="w-full pb-2 text-center relative flex flex-col items-center">
            <a href="" data-turbo="false" class="text-decoration-none sidebar-logo flex items-center" target="_blank" title="hms-saas-filament">
                <div class="image image-mini">
                    <img src="{{ App\Models\Setting::where('key', '=', 'app_logo')->first()->value ?? '' }}" class="me-4" alt="logo" width="40px" height="30px">
                </div>
            </a>
            <h1
                class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                {{ __('messages.reset') . ' ' . __('messages.staff.password') }}
            </h1>
        </div>
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</div>
