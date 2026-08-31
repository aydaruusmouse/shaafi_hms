<div>

    <x-filament-panels::form wire:submit="authenticate">
        <div class="w-full pb-2 text-center relative flex flex-col items-center">
            <a href="/" data-turbo="false" class="text-decoration-none sidebar-logo flex items-center" target="_blank"
                title="hms-saas-filament">
                <div class="image image-mini">
                    <img src="{{ App\Models\SuperAdminSetting::where('key', '=', 'app_logo')->first()->value ?? '' }}"
                        class="me-4" alt="Logo" width="40px" height="30px">
                </div>
            </a>
            <br>
            <h1
                class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">

                {{ __('auth.sign_in') }}
            </h1>
            <p class="fi-simple-header-subheading mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('messages.new_change.not_have_account') }}?
                <a href="{{ route('filament.auth.auth.register') }}"
                    class="fi-link group/link relative inline-flex items-center justify-center outline-none fi-size-md fi-link-size-md gap-1.5 fi-color-custom fi-color-primary fi-ac-action fi-ac-link-action">
                    <span
                        class="font-semibold text-sm text-custom-600 dark:text-custom-400 group-hover/link:underline group-focus-visible/link:underline custom-signup-link"
                        style="--c-400:var(--primary-400);--c-600:var(--primary-600);">
                        {{ __('auth.create_an_account') }}
                    </span>
                </a>
            </p>
        </div>
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>
</div>
