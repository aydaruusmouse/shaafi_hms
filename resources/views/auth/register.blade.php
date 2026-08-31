<div>
    <x-filament-panels::form wire:submit="register" id="registerForm">
        <div class="w-full pb-2 text-center relative flex flex-col items-center">
            <a href="{{ route('landing-home') }}" class="text-decoration-none sidebar-logo flex items-center"
                target="_blank">
                <img src="{{ App\Models\Setting::where('key', '=', 'app_logo')->first()->value ?? '' }}" class="me-4"
                    alt="Logo" width="40px" height="30px">
            </a>
            <h1
                class="fi-simple-header-heading text-center text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                {{ __('auth.create_an_account') }}
            </h1>

            <p class="fi-simple-header-subheading mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('auth.already_user') }}
                <a href="{{ route('filament.auth.auth.login') }}"
                    class="fi-link group/link relative inline-flex items-center justify-center outline-none fi-size-md fi-link-size-md gap-1.5 fi-color-custom fi-color-primary fi-ac-action fi-ac-link-action">
                    <span
                        class="font-semibold text-sm text-custom-600 dark:text-custom-400 group-hover/link:underline group-focus-visible/link:underline custom-signup-link"
                        style="--c-400:var(--primary-400);--c-600:var(--primary-600);">
                        {{ __('auth.sign_in') }}
                    </span>
                </a>
            </p>
        </div>
        {{ $this->form }}
        @if (getSuperAdminSettingKeyValue('enable_google_recaptcha'))
            <div class="flex justify-center items-center">
                <div class="form-group">
                    <div class="g-recaptcha" style="padding-bottom: 10px;"
                        data-sitekey="{{ getSuperAdminSettingKeyValue('google_captcha_key') }}"></div>
                    <span id="g-recaptcha-error" class="gap-2 text-red-500"></span>
                </div>
                <input type="hidden" value="1">
            </div>
        @endif
        <x-filament-panels::form.actions :actions="$this->getFormActions()" />
    </x-filament-panels::form>

</div>

<script src="https://www.google.com/recaptcha/api.js"></script>
<script>
    document.getElementById("registerForm").addEventListener("submit", function(event) {
        event.preventDefault();

        let captcha = grecaptcha.getResponse();
        document.getElementById("g-recaptcha-error").innerHTML = "";

        if (captcha === "") {
            document.getElementById("g-recaptcha-error").innerHTML = "Google reCAPTCHA is required";
            return;
        } else {
            @this.call('registerForm');
        }
    });

    document.addEventListener('validationFailed', function() {
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
</script>
