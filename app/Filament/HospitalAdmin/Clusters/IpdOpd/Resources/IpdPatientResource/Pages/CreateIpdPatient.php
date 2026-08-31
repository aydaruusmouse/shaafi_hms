<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource\Pages;

use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use App\Models\IpdPatientDepartment;
use App\Repositories\IpdPatientDepartmentRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateIpdPatient extends CreateRecord
{
    protected static string $resource = IpdPatientResource::class;

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

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.IPD_Patient_saved');
    }

    protected function handleRecordCreation(array $input): Model
    {
        $IpdPatientDepartmentRepository = app(IpdPatientDepartmentRepository::class);
        $existsCaseId = IpdPatientDepartment::where('case_id', $input['case_id'])->latest()->first();

        if ($existsCaseId && $existsCaseId->is_discharge == 0) {
            Notification::make()
                ->danger()
                ->title(__('messages.lunch_break.case_exist'))
                ->send();

            $this->halt();
        }
        $data = $IpdPatientDepartmentRepository->store($input);
        $IpdPatientDepartmentRepository->createNotification($input);

        return $data;
    }
}
