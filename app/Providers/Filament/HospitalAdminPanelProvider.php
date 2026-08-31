<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CustomThemePage;
use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use App\Models\Setting;
use Filament\PanelProvider;
use function Pest\Laravel\get;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use App\Filament\Pages\EditProfile;
use Filament\View\PanelsRenderHook;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use App\Http\Middleware\AppNameMiddleware;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\RedirectAuthenticated;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Spatie\Permission\Middleware\RoleMiddleware;
use Vormkracht10\TwoFactorAuth\TwoFactorAuthPlugin;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\SmartPatientCardResource;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Hasnayeen\Themes\ThemesPlugin;

class HospitalAdminPanelProvider extends PanelProvider
{
    public function tenant()
    {
        return Auth::user()->tenant_id;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('hospitalAdmin')
            ->path('hospital')
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->schedulerLicenseKey('')
                    ->selectable(true)
                    ->editable()
                    ->timezone(config('app.timezone'))
                    ->locale(config('app.locale'))
                    ->plugins(['dayGrid', 'timeGrid'])
                    ->config([]),
                ThemesPlugin::make(),
            ])
            ->pages([
                // Pages\Dashboard::class,
                CustomThemePage::class
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn () => Auth::user()->full_name)
                    ->icon(fn () => Auth::user()->profile),
            ])
            // ->plugin(TwoFactorAuthPlugin::make())
            ->profile(EditProfile::class, isSimple: false)
            ->userMenuItems([
                MenuItem::make()
                    ->hidden(fn () => ! auth()->user()->hasRole(['Admin']))
                    ->label(fn () => __('messages.subscription_plans.subscription_plans'))
                    ->icon('heroicon-o-star')
                    ->visible(fn () => auth()->user()->hasRole('Admin'))
                    ->url(fn () => route('filament.hospitalAdmin.pages.subscription-plans')),
                // MenuItem::make()
                //     ->label(fn() => __('messages.user.back_to_admin'))
                //     ->icon('heroicon-o-arrow-top-right-on-square')
                //     ->visible(function () {
                //         if (session()->has('impersonated_by') && session()->get('impersonated_by') == 1) {
                //             return true;
                //         }
                //         return false;
                //     })
                //     ->url(fn() => route('filament-impersonate.leave')),
            ])
            ->spaUrlExceptions(fn (): array => [
                url(route('filament.hospitalAdmin.pages.subscription-plans')),
                SmartPatientCardResource::getUrl('create'),
            ])
            ->renderHook(PanelsRenderHook::HEAD_START, fn () => (session()->has('impersonated_by') && session()->get('impersonated_by') == 1) ? view('layout.hospital-head') : '') // for add something in header-tag
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(PanelsRenderHook::SCRIPTS_BEFORE, fn () => view('layout.scripts'))
            // ->renderHook('panels::user-menu.after', fn() => Blade::render('@livewire(\'edit-profile\')'))
            // ->renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER, fn() => view('layout.edit-profile-btn'))
            ->renderHook('panels::user-menu.after', fn () => Blade::render('@livewire(\'change-password\')'))
            ->renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER, fn () => view('layout.change-password-btn'))
            ->renderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER, fn () => view('layout.translations'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn () => auth()->user()->hasRole('Patient') ? view('layout.patient-smart-card') : '')

            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn () => auth()->user()->hasRole('Admin') ? view('layout.front-url') : '')
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER, fn () => Blade::render('@livewire(\'notification-read\')'))

            ->renderHook(PanelsRenderHook::SIDEBAR_NAV_START, fn () => view('layout.search-in-sidebar'))
            ->renderHook(PanelsRenderHook::FOOTER, fn () => view('layout.footer'))
            ->colors([
                'danger' => Color::Red,
                'gray' => Color::Gray,
                'secondary' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::hex('#6571ff'),
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->favicon(function () {
                return Setting::where('tenant_id', $this->tenant())->where('key', '=', 'favicon')->first()->value ?? '';
            })
            // ->spa()
            ->breadcrumbs(false)
            ->maxContentWidth(MaxWidth::Full)
            ->discoverClusters(in: app_path('Filament/HospitalAdmin/Clusters'), for: 'App\\Filament\\HospitalAdmin\\Clusters')
            ->discoverResources(in: app_path('Filament/HospitalAdmin/Resources'), for: 'App\\Filament\\HospitalAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/HospitalAdmin/Pages'), for: 'App\\Filament\\HospitalAdmin\\Pages')
           
            ->discoverWidgets(in: app_path('Filament/HospitalAdmin/Widgets'), for: 'App\\Filament\\HospitalAdmin\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
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
                CheckSubscription::class,
                AppNameMiddleware::class,
                SetTheme::class,
            ])
            ->authMiddleware([
                EnsureEmailIsVerified::class,
                RedirectAuthenticated::class,
                RoleMiddleware::class.':Admin|Accountant|Doctor|Patient|Nurse|Receptionist|Pharmacist|Lab Technician|Case Manager',
            ]);
    }
}