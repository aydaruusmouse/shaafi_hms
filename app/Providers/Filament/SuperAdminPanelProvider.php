<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CustomThemePage;
use Filament\Pages;
use Filament\Panel;
use Pest\Plugins\Profile;
use Filament\PanelProvider;
use App\Models\SuperAdminSetting;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use App\Filament\Pages\EditProfile;
use Filament\View\PanelsRenderHook;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Session\Middleware\StartSession;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use App\Http\Middleware\AppNameMiddleware;
use App\Http\Middleware\RedirectAuthenticated;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Spatie\Permission\Middleware\RoleMiddleware;
use Vormkracht10\TwoFactorAuth\TwoFactorAuthPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('superAdmin')
            ->plugin(ThemesPlugin::make())
            ->pages([
                CustomThemePage::class,
            ])
            ->path('super-admin')
            ->profile(EditProfile::class, isSimple: false)
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => Auth::user()->full_name)
                    ->icon(fn() => Auth::user()->profile),
            ])
            // ->plugin(TwoFactorAuthPlugin::make())
            ->renderHook(PanelsRenderHook::HEAD_START, fn () => view('layout.head'))
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => view('layout.scripts'))
            ->renderHook(PanelsRenderHook::FOOTER, fn () => view('layout.footer'))
            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn () => view('layout.search-in-sidebar'))
            // ->renderHook('panels::user-menu.after', fn() => Blade::render('@livewire(\'edit-profile\')'))
            // ->renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER, fn() => view('layout.edit-profile-btn'))
            ->renderHook('panels::user-menu.after', fn () => Blade::render('@livewire(\'change-password\')'))
            ->renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER, fn () => view('layout.change-password-btn'))
            ->renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER, fn () => view('layout.translations'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn () => Blade::render('@livewire(\'notification-read\')'))
            ->passwordReset()
            ->registration()
            ->sidebarCollapsibleOnDesktop()
            ->favicon((function () {
                try {
                    \DB::connection()->getPdo();

                    return SuperAdminSetting::where('key', 'favicon')->first()?->value ?? asset('web/img/hms-saas-favicon.ico');
                } catch (\Exception $e) {
                    return asset('web/img/hms-saas-favicon.ico');
                }
            })())
            // ->spa()
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'secondary' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::hex('#6571ff'),
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->breadcrumbs(false)
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
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
                AppNameMiddleware::class,
                SetTheme::class,
            ])
            ->authMiddleware([
                RedirectAuthenticated::class,
                RoleMiddleware::class . ':Super Admin',
            ]);
    }
}