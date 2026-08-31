<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource;
use App\Models\Patient;
use App\Models\PatientAdmission;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBill extends EditRecord
{
    protected static string $resource = BillResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $patientAdmission = PatientAdmission::with(['patient.patientUser', 'doctor.doctorUser', 'package.packageServicesItems.service'])->where('patient_admission_id', $data['patient_admission_id'])->first();
        $admissionDate = Carbon::parse($patientAdmission->admission_date);
        $dischargeDate = Carbon::parse($patientAdmission->discharge_date);
        $data['email'] = $patientAdmission->patient->patientUser->email ?? __('messages.common.n/a');
        $data['phone'] = $patientAdmission->patient->patientUser->phone ?? __('messages.common.n/a');
        $data['gender'] = $patientAdmission->patient->patientUser->gender ?? 0;
        $data['dob'] = $patientAdmission->patient->patientUser->dob ?? __('messages.common.n/a');
        $data['doctor_id'] = $patientAdmission->doctor->doctorUser->full_name ?? __('messages.common.n/a');
        $data['admission_date'] = $patientAdmission->admission_date ?? __('messages.common.n/a');
        $data['discharge_date'] = $patientAdmission->discharge_date ?? __('messages.common.n/a');
        $data['package_id'] = $patientAdmission->package->name ?? __('messages.common.n/a');
        $data['insurance_id'] = $patientAdmission->insurance->name ?? __('messages.common.n/a');
        $data['total_days'] = round($admissionDate->diffInDays($dischargeDate) + 1) ?? __('messages.common.n/a');
        $data['policy_no'] = $patientAdmission->policy_no ?? __('messages.common.n/a');
        $data['patient_id'] = $patientAdmission->patient_id ?? __('messages.common.n/a');
        $data['total_amt'] = $data['amount'] ?? 0;

        return $data;
    }

    protected function beforeSave(): void
    {
        $patientId = Patient::with('patientUser')->whereId($this->data['patient_id'])->first();
        $birthDate = $patientId->patientUser->dob;
        $billDate = Carbon::parse($this->data['bill_date'])->toDateString();
        if (! empty($birthDate) && $billDate < $birthDate) {
            Notification::make()
                ->danger()
                ->title(__('messages.flash.bill_date_smaller'))
                ->send();
            $this->halt;
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['amount'] = $data['total_amt'];

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.bill_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
