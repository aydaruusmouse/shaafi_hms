<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource;
use App\Models\Patient;
use App\Repositories\PatientAdmissionRepository;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePatientAdmission extends CreateRecord
{
    protected static string $resource = PatientAdmissionResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $input): Model
    {
        $patientId = Patient::with('patientUser')->whereId($input['patient_id'])->first();
        $birthDate = $patientId->user->dob;
        $admissionDate = Carbon::parse($input['admission_date'])->toDateString();
        if (! empty($birthDate) && $admissionDate < $birthDate) {
            Notification::make()->title(__('messages.flash.admission_date_smaller'))->danger()->send();
        }
        $saved = app(PatientAdmissionRepository::class)->store($input);

        return $saved;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.patient_admission_saved');
    }
}
