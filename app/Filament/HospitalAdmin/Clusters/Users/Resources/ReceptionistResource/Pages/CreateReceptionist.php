<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\ReceptionistResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\ReceptionistResource;
use App\Repositories\ReceptionistRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReceptionist extends CreateRecord
{
    protected static string $resource = ReceptionistResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function handleRecordCreation(array $input): Model
    {
        $input['region_code'] = ! empty($input['phone']) ? getRegionCode($input['region_code'] ?? '') : null;
        $input['phone'] = getPhoneNumber($input['phone']);
        $input['status'] = $input['status'] ? 1 : 0;
        $receptionist = app(ReceptionistRepository::class)->store($input);

        return $receptionist;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.receptionist_saved');
    }
}
