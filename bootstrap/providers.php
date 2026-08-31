<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AuthPanelProvider::class,
    App\Providers\Filament\HospitalAdminPanelProvider::class,
    App\Providers\Filament\SuperAdminPanelProvider::class,
    App\Providers\FortifyServiceProvider::class,
    Lab404\Impersonate\ImpersonateServiceProvider::class,
];
