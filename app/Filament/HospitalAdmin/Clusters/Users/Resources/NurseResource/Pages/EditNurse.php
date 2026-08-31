<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\NurseResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\NurseResource;
use App\Models\Address;
use App\Models\Nurse;
use App\Models\User;
use App\Repositories\NurseRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditNurse extends EditRecord
{
    protected static string $resource = NurseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        if (! canAccessRecord(Nurse::class, $data['id'])) {
            Notification::make()
                ->danger()
                ->title(__('messages.flash.access_denied'))
                ->body(__('messages.flash.not_allow_access_record'))
                ->send();

            return $data;
        }
        $user = User::find($data['user_id']);
        $data['first_name'] = $user->first_name;
        $data['last_name'] = $user->last_name;
        $data['email'] = $user->email;
        $data['phone'] = $user->phone;
        $data['gender'] = $user->gender;
        $data['dob'] = $user->dob;
        $data['status'] = $user->status;
        $data['blood_group'] = $user->blood_group;
        $data['qualification'] = $user->qualification;
        $data['designation'] = $user->designation;
        $ownerId = Nurse::where('user_id', $data['user_id'])->first()->id;
        $address = Address::where('owner_id', $ownerId)->where('owner_type', Nurse::class)->first();
        if (! $address) {
            return $data;
        } else {
            $data['address1'] = $address->address1;
            $data['address2'] = $address->address2;
            $data['city'] = $address->city;
            $data['zip'] = $address->zip;

            return $data;
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['region_code'] = ! empty($data['phone']) ? getRegionCode($data['region_code'] ?? '') : null;
        $data['phone'] = getPhoneNumber($data['phone']);

        $accountant = app(NurseRepository::class)->update($record, $data);

        return $accountant;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.nurse_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
