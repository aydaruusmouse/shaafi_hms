<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditMedicine extends EditRecord
{
    protected static string $resource = MedicineResource::class;

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
        getUniqueNameValidation(static::getModel(), $this->record, $this->data, $this, isEdit: true, isPage: true, error: __('messages.medicine.medicine'));
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.medicine_updated');
    }
}
