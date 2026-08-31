<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! canAccessRecord($record, $record->id)) {
            // Flash::error(__('messages.flash.not_allow_access_record'));
            Notification::make()
                ->danger()
                ->title(__('messages.flash.not_allow_access_record'))
                ->send();

            return $record;
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.item_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
