<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Repositories\PatientRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePatient extends CreateRecord
{
    protected static string $resource = PatientResource::class;

    protected static bool $canCreateAnother = false;

    protected ?int $createdPatientId = null;

    protected bool $redirectToAppointmentCreate = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        $actions = [
            $this->getCreateFormAction(),
        ];

        if (AppointmentResource::canCreate()) {
            $actions[] = Action::make('createAndAppointment')
                ->label(__('messages.common.save_and_make_appointment'))
                ->color('success')
                ->action('createAndBookAppointment');
        }

        $actions[] = $this->getCancelFormAction();

        return $actions;
    }

    public function createAndBookAppointment(): void
    {
        abort_unless(AppointmentResource::canCreate(), 403);

        $this->redirectToAppointmentCreate = true;

        try {
            $this->create(another: false);
        } catch (\Throwable $e) {
            $this->redirectToAppointmentCreate = false;

            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        if ($this->createdPatientId && ($this->redirectToAppointmentCreate || request()->boolean('create_and_appointment'))) {
            $this->redirectToAppointmentCreate = false;

            return AppointmentResource::getUrl('create', [
                'patient_id' => $this->createdPatientId,
            ]);
        }

        return static::getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $input): Model
    {
        $input['region_code'] = ! empty($input['phone']) ? getRegionCode($input['region_code'] ?? '') : null;
        $input['phone'] = getPhoneNumber($input['phone']);

        $record = app(PatientRepository::class)->store($input);
        $this->createdPatientId = $record->owner_id ?? $record->id;
        app(PatientRepository::class)->createNotification($input);

        return $record;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.Patient_saved');
    }
}
