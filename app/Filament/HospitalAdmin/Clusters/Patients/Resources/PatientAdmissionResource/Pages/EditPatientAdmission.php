<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource;
use App\Models\Patient;
use App\Repositories\PatientAdmissionRepository;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPatientAdmission extends EditRecord
{
    protected static string $resource = PatientAdmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function beforeSave(): void
    {
        $patientId = Patient::with('patientUser')->whereId($this->data['patient_id'])->first();
        $birthDate = $patientId->patientUser->dob;
        $admissionDate = Carbon::parse($this->data['admission_date'])->toDateString();
        if (! empty($birthDate) && $admissionDate < $birthDate) {
            Notification::make()
                ->danger()
                ->title(__('messages.flash.admission_date_smaller'))
                ->send();
            $this->halt();
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = App(PatientAdmissionRepository::class)->update($data, $record);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.patient_admission_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
