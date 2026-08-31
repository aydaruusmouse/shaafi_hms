<?php

namespace App\Filament\Resources\HospitalResource\Pages;

use App\Filament\Resources\HospitalResource;
use App\Models\MultiTenant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditHospital extends EditRecord
{
    protected static string $resource = HospitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $input): Model
    {
        $user = User::find($record->id);
        $input['first_name'] = $input['hospital_name'];
        $input['region_code'] = ! empty($input['phone']) ? getRegionCode($input['region_code'] ?? '') : null;
        $input['phone'] = getPhoneNumber($input['phone']);
        $user->update(Arr::except($input, ['username']));
        $userTenant = MultiTenant::find($user->tenant_id);
        $userTenant->hospital_name = $input['first_name'];
        $userTenant->save();

        return $user;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.hospital_update');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
