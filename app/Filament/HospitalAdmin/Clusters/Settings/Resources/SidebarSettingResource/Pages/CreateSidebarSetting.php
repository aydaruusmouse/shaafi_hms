<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Resources\SidebarSettingResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Settings\Resources\SidebarSettingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Redirect;

class CreateSidebarSetting extends CreateRecord
{
    protected static string $resource = SidebarSettingResource::class;

    public function mount(): void
    {
        Redirect::to(static::getResource()::getUrl('index'));
    }
}
