<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAmbulance extends EditRecord
{
    protected static string $resource = AmbulanceResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function beforeSave()
    {
        $isExist = static::getModel()::whereTenantId(getLoggedInUser()->tenant_id)->where('id', '!=', $this->record->id)->where('vehicle_number', $this->data['vehicle_number'])->exists();

        if ($isExist) {
            Notification::make()
                ->danger()
                ->title(__('messages.ambulance.vehicle_number').' '.__('messages.common.is_already_exists'))
                ->send();
            $this->halt();
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.ambulance_update');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
