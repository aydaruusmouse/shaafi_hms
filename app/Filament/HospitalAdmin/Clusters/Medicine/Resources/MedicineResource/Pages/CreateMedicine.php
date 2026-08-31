<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMedicine extends CreateRecord
{
    protected static string $resource = MedicineResource::class;

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
        getUniqueNameValidation(static::getModel(), null, $this->data, $this, isEdit: false, isPage: true, error: __('messages.medicine.medicine'));
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.medicine_saved');
    }
}
