<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Patient;
use App\Repositories\PatientRepository;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditPatient extends EditRecord
{
    protected static string $resource = PatientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! canAccessRecord(Patient::class, $data['id'])) {
            Notification::make()
                ->danger()
                ->title(__('messages.flash.access_denied'))
                ->body(__('messages.flash.not_allow_access_record'))
                ->send();

            return $data;
        }

        $record = $this->record;
        $data = Patient::with(['user', 'address'])->where('id', $record->id)->get()->toArray();
        $data = $data[0] + $data[0]['user'] + ($data[0]['address'] ?? []) + ($data[0]['custom_field'] ?? []);
        $data = Arr::except($data, ['media', 'profile', 'user', 'address', 'custom_field', 'owner_type', 'owner_id', 'template_id']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['region_code'] = ! empty($data['phone']) ? getRegionCode($data['region_code'] ?? '') : null;
        $data['phone'] = getPhoneNumber($data['phone']);

        $patient = app(PatientRepository::class)->update($record, $data);

        return $patient;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.Patient_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
