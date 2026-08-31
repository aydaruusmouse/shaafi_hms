<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicineBrand extends CreateRecord
{
    protected static string $resource = MedicineBrandResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function beforeCreate()
    {
        getUniqueNameValidation(static::getModel(), null, $this->data, $this, isEdit: false, isPage: true);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.medicine_brand_saved');
    }
}
