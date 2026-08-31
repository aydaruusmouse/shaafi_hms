<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditMedicineBrand extends EditRecord
{
    protected static string $resource = MedicineBrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function beforeSave()
    {
        getUniqueNameValidation(static::getModel(), $this->record, $this->data, $this, isEdit: true, isPage: true);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.medicine_brand_updated');
    }
}
