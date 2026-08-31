<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorHolidaysResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorHolidaysResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateDoctorHolidays extends CreateRecord
{
    protected static string $resource = DoctorHolidaysResource::class;

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
        return __('messages.holiday.doctor_holiday_create');
    }
}
