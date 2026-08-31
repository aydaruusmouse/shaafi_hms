<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource;
use App\Repositories\LunchBreakRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBreaks extends CreateRecord
{
    protected static string $resource = BreaksResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    // public function handleRecordCreation(array $input): Model
    // {
    //     dd($input);
    //     $appointments = Appointment::whereDoctorId($input['doctor_id'])->get();

    //     foreach ($appointments as $appointment) {
    //         $time = Carbon::parse($appointment->opd_date)->format('h:i:s');
    //         $breakTime = Carbon::createFromTimeString($time)->between($input['break_from'], $input['break_to']);
    //         if ($breakTime) {
    //             Flash::error(__('messages.lunch_break.appointment_exist_time'));

    //             return redirect(route('breaks.create'));
    //         }
    //     }
    //     if (isset($input['date']) && !empty($input['date'])) {
    //         $opdDates = Appointment::whereRaw('DATE(opd_date) = ?', $input['date'])->exists();

    //         if ($opdDates) {
    //             Flash::error(__('messages.lunch_break.appointment_exist_time'));

    //             return redirect(route('breaks.create'));
    //         }
    //     }
    //     $lunchBreak = $this->lunchBreakRepository->store($input);

    //     if ($lunchBreak) {
    //         Flash::success(__('messages.lunch_break.break_create'));

    //         return redirect(route('breaks.index'));
    //     } else {
    //         Flash::error(__('messages.lunch_break.break_already_is_exist'));

    //         return redirect(route('breaks.create'));
    //     }
    // }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.lunch_break.break_create');
    }
}
