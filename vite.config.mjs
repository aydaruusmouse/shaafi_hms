import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        cors: true
    },
    plugins: [
        laravel({
            input: [
                'resources/assets/js/turbo.js',
                'resources/assets/theme/js/plugins.js',
                'resources/css/app.css',
                'resources/css/landing_front.css',
                'resources/css/landing-pages.css',
                'resources/css/front-pages.css',
                'resources/assets/sass/selectize-input.scss',
                'resources/assets/theme/css/third-party.css',
                'resources/assets/landing-theme/css/landing-rtl.css',
                'resources/assets/hospital-front/css/hospital-front-rtl.css',
                'resources/assets/front/scss/main.scss',
                'resources/assets/hospital-front/scss/hospital-main.scss',
                'resources/js/app.js',
                'resources/js/landing_front.js',
                'resources/js/landing-front-pages.js',
                'resources/assets/js/custom/custom.js',
                'resources/assets/js/super_admin/contact_enquiry/web.js',
                'resources/assets/js/custom/phone-number-country-code.js',
                'resources/assets/js/subscriptions/free-subscription.js',
                'resources/assets/js/subscriptions/subscription-option.js',
                'resources/js/hospital_type/hospital_type.js',
                'resources/assets/js/subscriptions/subscription.js',
                'resources/assets/js/subscribe/create.js',
                'resources/assets/js/super_admin/contact_enquiry/contact_enquiry.js',
                'resources/assets/js/landing/languageChange/languageChange.js',
                'resources/assets/js/custom/front-side-phone-number-country-code.js',
                'resources/assets/js/web/appointment.js',
                'resources/assets/js/appointment_calendar/appointment_calendar.js',
                'resources/assets/js/web/contact_us.js',
                'resources/assets/js/web/web.js',
                'resources/assets/js/custom/helpers.js',
                'resources/assets/hospital-front/scss/hospital-bootstrap.scss',
                'resources/css/filament.css',
                'resources/css/login.css'
                // 'resources/assets/js/landing-third-party.js'
                // 'resources/assets/img/',  // Copied to public/assets/img
                // 'resources/assets/landing-theme/fonts/', // Copied to public/landing_front/css/fonts
                // 'resources/assets/theme/fonts/',  // Copied to public/fonts
            ],
            content: [
                "./vendor/vormkracht10/filament-2fa/resources/**.*.blade.php",
            ],
            refresh: true,
        }),
    ],
});
