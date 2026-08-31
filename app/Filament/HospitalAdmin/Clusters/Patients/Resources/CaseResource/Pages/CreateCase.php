<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseResource;
use App\Models\Patient;
use App\Repositories\PatientCaseRepository;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCase extends CreateRecord
{
    protected static string $resource = CaseResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordCreation(array $input): Model
    {
        $patientId = Patient::with('patientUser')->whereId($input['patient_id'])->first();
        $birthDate = $patientId->patientUser->dob;
        $caseDate = Carbon::parse($input['date'])->toDateString();
        if (! empty($birthDate) && $caseDate < $birthDate) {
            // Flash::error(__('messages.flash.case_date_smaller'));
            Notification::make()
                ->title(__('messages.flash.case_date_smaller'))
                ->danger()
                ->send();

            return redirect()->back()->withInput($input);
        }

        $input['fee'] = removeCommaFromNumbers($input['fee']);
        $input['status'] = isset($input['status']) ? 1 : 0;
        // $input['phone'] = preparePhoneNumber($input, 'phone');

        app(PatientCaseRepository::class)->store($input);
        app(PatientCaseRepository::class)->createNotification($input);

        $record = new ($this->getModel())($input);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.case_saved');
    }
}
