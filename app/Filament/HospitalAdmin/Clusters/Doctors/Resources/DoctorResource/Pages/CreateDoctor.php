<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Models\ScheduleDay;
use App\Repositories\DoctorRepository;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class CreateDoctor extends CreateRecord
{
    protected static string $resource = DoctorResource::class;

    protected static bool $canCreateAnother = false;

    public function handleRecordCreation(array $input): Model
    {
        $input['region_code'] = ! empty($input['phone']) ? getRegionCode($input['region_code'] ?? '') : null;
        $input['phone'] = getPhoneNumber($input['phone']);

        $input['status'] = $input['status'] ? 1 : 0;
        $doctor = app(DoctorRepository::class)->store($input);

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $scheduleDay) {
            ScheduleDay::create([
                'doctor_id' => Session::get('doctor_id'),
                'schedule_id' => Session::get('schedule_id'),
                'available_on' => $scheduleDay,
                'available_from' => '10:00:00',
                'available_to' => '19:30:00',
            ]);
        }

        return $doctor;
    }

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
        return __('messages.flash.doctor_save');
    }
}
