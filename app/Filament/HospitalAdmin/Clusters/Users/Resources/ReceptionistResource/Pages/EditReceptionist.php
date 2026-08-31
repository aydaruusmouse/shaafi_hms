<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\ReceptionistResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\ReceptionistResource;
use App\Models\Address;
use App\Models\Receptionist;
use App\Models\User;
use App\Repositories\ReceptionistRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class EditReceptionist extends EditRecord
{
    protected static string $resource = ReceptionistResource::class;

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

        if (! canAccessRecord(Receptionist::class, $data['id'])) {
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
        $ownerId = Receptionist::where('user_id', $data['user_id'])->first()->id;
        $address = Address::where('owner_id', $ownerId)->where('owner_type', Receptionist::class)->first();
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
        $receptionist = app(ReceptionistRepository::class)->update($record, $data);

        return $receptionist;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.receptionist_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
