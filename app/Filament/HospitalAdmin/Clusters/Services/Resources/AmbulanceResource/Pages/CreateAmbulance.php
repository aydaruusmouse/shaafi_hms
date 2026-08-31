<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource;
use App\Repositories\AmbulanceRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAmbulance extends CreateRecord
{
    protected static string $resource = AmbulanceResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.ambulance_saved');
    }

    protected function handleRecordCreation(array $input): Model
    {
        app(AmbulanceRepository::class)->createNotification();

        return parent::handleRecordCreation($input);
    }

    protected function beforeCreate()
    {
        $isExist = static::getModel()::whereTenantId(getLoggedInUser()->tenant_id)->where('vehicle_number', $this->data['vehicle_number'])->exists();
        if ($isExist) {
            Notification::make()
                ->danger()
                ->title(__('messages.ambulance.vehicle_number').' '.__('messages.common.is_already_exists'))
                ->send();
            $this->halt();
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
