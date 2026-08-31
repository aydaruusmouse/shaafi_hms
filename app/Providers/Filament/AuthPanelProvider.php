<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Models\SuperAdminSetting;
use App\Http\Middleware\CheckRole;
use Filament\Support\Colors\Color;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Auth\CustomLogin;
use App\Http\Middleware\AppNameMiddleware;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Vormkracht10\TwoFactorAuth\TwoFactorAuthPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\Auth\CustomRequestPasswordReset;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AuthPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->spa()
            ->default()
            ->id('auth')
            ->path('/')
            ->passwordReset(CustomRequestPasswordReset::class)
            ->registration(Register::class)
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'secondary' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::hex('#6571ff'),
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'lime' => Color::Lime,
                'violet' => Color::Violet,
                'purple' => Color::Purple,
                'fuchsia' => Color::Fuchsia,
                'rose' => Color::Rose,
            ])
            ->favicon(
                (function () {
                    try {
                        \DB::connection()->getPdo();

                        return SuperAdminSetting::where('key', 'favicon')->first()?->value ?? asset('web/img/hms-saas-favicon.ico');
                    } catch (\Exception $e) {
                        return asset('web/img/hms-saas-favicon.ico');
                    }
                })()
            )
            ->login(CustomLogin::class)
            ->plugin(TwoFactorAuthPlugin::make())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CheckRole::class,
                AppNameMiddleware::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}