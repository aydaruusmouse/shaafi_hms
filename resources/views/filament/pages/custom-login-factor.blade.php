<div>
    @vite('resources/css/login.css')
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
                class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white mb-6">

                {{ __('Authenticate with your code') }}
            </h1>
            @if ($errors->any())
        <div class="mt-4 bg-red-50 text-red-700 p-4 rounded-lg">
            <p class="font-medium">{{ __('Invalid authentication code.') }}</p>
        </div>
    @endif

    @if ($twoFactorType === 'email' || $twoFactorType === 'phone')
        <div wire:poll.5s>
            {{ $this->resend }}
        </div>
    @endif
    <form method="POST" action="{{ route('two-factor.login') }}" class="w-full mt-10 space-y-8">
        @csrf

        <div style="display: none">
            <input type="text" id="recovery_code" name="recovery_code" value="" color="default">
        </div>

        {{ $this->form }}

        <div class="flex items-center justify-between mt-6">
            <x-filament::button type="submit" class="w-full" color="default">
                {{ __('auth.sign_in') }}
            </x-filament::button>
        </div>
    </form>
        </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        @this.on('resent', () => {
            // Immediately disable the button
            Livewire.dispatch('$refresh');
        });
    });
</script>
