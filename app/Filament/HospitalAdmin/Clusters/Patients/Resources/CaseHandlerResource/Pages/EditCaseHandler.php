<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseHandlerResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseHandlerResource;
use App\Models\Address;
use App\Models\CaseHandler;
use App\Models\User;
use App\Repositories\CaseHandlerRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCaseHandler extends EditRecord
{
    protected static string $resource = CaseHandlerResource::class;

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
        if (! canAccessRecord(CaseHandler::class, $data['id'])) {
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
        $ownerId = CaseHandler::where('user_id', $data['user_id'])->first()->id;
        $address = Address::where('owner_id', $ownerId)->where('owner_type', CaseHandler::class)->first();
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

    protected function handleRecordUpdate(Model $record, array $input): Model
    {
        $input['region_code'] = ! empty($input['phone']) ? getRegionCode($input['region_code'] ?? '') : null;
        $input['phone'] = getPhoneNumber($input['phone']);
        $record = app(CaseHandlerRepository::class)->update($record, $input);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.case_handler_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
