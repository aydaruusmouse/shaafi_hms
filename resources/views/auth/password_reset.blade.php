<div>
    <x-filament-panels::form wire:submit="request">
        <div class="w-full pb-3 text-center relative flex flex-col items-center">
            <a href="" data-turbo="false" class="text-decoration-none sidebar-logo flex items-center" target="_blank"
                title="hms-saas-filament">
                <div class="image image-mini">
                    <img src="{{ App\Models\Setting::where('key', '=', 'app_logo')->first()->value ?? '' }}"
                        class="me-4" alt="Logo" width="40px" height="30px">
                </div>
            </a>
            <br>
            <h1
                class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                {{ __('auth.login.forgot_password') }}
            </h1>

            <p class="fi-simple-header-subheading mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('auth.forgot_password.title') }}
            </p>
        </div>
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />

    </x-filament-panels::form>
</div>
