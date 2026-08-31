<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Termwind\Components\Dd;

class EditSchedules extends EditRecord
{
    protected static string $resource = SchedulesResource::class;

    protected function getHeaderActions(): array
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

    protected function handleRecordUpdate(Model $record, array $input): Model
    {
        // {
        //     $schedule = Schedule::findOrFail($id);
        //     $schedule->update($input);

        //     $scheduleDayArray = Arr::only($input, ['available_on', 'available_from', 'available_to']);
        //     $scheduleDayItemInput = $this->prepareInputForScheduleDayItem($scheduleDayArray);
        //     foreach ($scheduleDayItemInput as $key => $data) {
        //         $scheduleDay = ScheduleDay::whereScheduleId($id)
        //             ->where('available_on', $data['available_on']);
        //         $data['doctor_id'] = $input['doctor_id'];
        //         $data['schedule_id'] = $schedule->id;
        //         $scheduleDay->update($data);
        //     }

        //     return true;
        // }
        $isDoctorSchedule = Schedule::where('doctor_id', '!=', $record->doctor_id)->where('doctor_id', $input['doctor_id'])->exists();

        if ($isDoctorSchedule) {
            Notification::make()
                ->danger()
                ->title(__('validation.custom.doctor_id.unique'))
                ->send();
            $this->halt();
        }

        $scheduleDay = [];

        $schedule = Schedule::findOrFail($record->id);
        // dd($schedule);
        $schedule->update($input);
        foreach ($input['schedule'] as $data) {
            $scheduleDay = ScheduleDay::whereScheduleId($record->id)
                ->where('available_on', $data['available_on']);
            $data['doctor_id'] = $input['doctor_id'];
            $data['schedule_id'] = $schedule->id;
            $scheduleDay->update($data);
        }

        return $schedule;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->record->scheduleDays as $key => $scheduleDay) {
            $data['schedule'][$key]['available_on'] = $scheduleDay->available_on;
            $data['schedule'][$key]['available_from'] = $scheduleDay->available_from;
            $data['schedule'][$key]['available_to'] = $scheduleDay->available_to;
        }

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.schedule_updated');
    }
}
