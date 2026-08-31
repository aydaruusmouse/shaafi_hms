<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource;
use App\Models\Ambulance;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAmbulanceCall extends CreateRecord
{
    protected static string $resource = AmbulanceCallResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.ambulance_call_saved');
    }

    protected function handleRecordCreation(array $input): Model
    {
        Ambulance::where('id', $input['ambulance_id'])->update(['is_available' => false]);

        return parent::handleRecordCreation($input);
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
