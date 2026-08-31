<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceCallResource;
use App\Models\Ambulance;
use App\Repositories\AmbulanceCallRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAmbulanceCall extends EditRecord
{
    protected static string $resource = AmbulanceCallResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.ambulance_call_updated');
    }

    // protected function mutateFormDataBeforeFill(array $data): array
    // {
    //     Ambulance::where('id', $data['ambulance_id'])->where('tenant_id', getLoggedInUser()->tenant_id)->whereIsAvailable(1)->pluck('vehicle_model', 'id')->sort();
    //     dd($data);
    // }

    protected function handleRecordUpdate(Model $record, array $input): Model
    {
        app(AmbulanceCallRepository::class)->update($input, $record);

        return parent::handleRecordUpdate($record, $input);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
