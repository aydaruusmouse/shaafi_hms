<?php

namespace App\Providers;

use App\Rules\ValidRecaptcha;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Filament\Support\Facades\FilamentIcon;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Vormkracht10\TwoFactorAuth\Http\Responses\LoginResponse as ResponsesLoginResponse;
use Vormkracht10\TwoFactorAuth\Listeners\SendTwoFactorCodeListener;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            RegistrationResponse::class,
            \App\Http\Responses\RegistrationResponse::class
        );
        $this->app->singleton(
            ResponsesLoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );
        $this->app->singleton(
            LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
        FilamentIcon::register([
            'panels::pages.dashboard.navigation-item' => view('icons.pie-chart'),
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen([
            TwoFactorAuthenticationChallenged::class,
            TwoFactorAuthenticationEnabled::class
        ], SendTwoFactorCodeListener::class);
        Paginator::useBootstrap();
        Validator::extend('recaptcha', ValidRecaptcha::class);
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->userPreferredLocale(function () {
                    return auth()->user()->language ?? 'en';
                })
                ->locales(['ar', 'en', 'fr', 'de', 'es', 'pt', 'ru', 'tr', 'zh'])
                ->flags([
                    'ar' => asset('images/flags/jordan.png'),
                    'en' => asset('images/flags/united-states.png'),
                    'fr' => asset('images/flags/france.png'),
                    'de' => asset('images/flags/germany.png'),
                    'es' => asset('images/flags/spain.png'),
                    'pt' => asset('images/flags/portugal.png'),
                    'it' => asset('images/flags/italy.png'),
                    'ru' => asset('images/flags/russia.png'),
                    'tr' => asset('images/flags/turkey.png'),
                    'zh' => asset('images/flags/china.png'),
                ])
                ->visible(outsidePanels: true)
                ->outsidePanelRoutes([
                    'auth.login',
                    'auth.register',
                    'auth.password-reset',
                ]);
        });
    }
}