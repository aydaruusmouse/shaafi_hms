<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorHolidaysResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorHolidaysResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDoctorHolidays extends ListRecords
{
    protected static string $resource = DoctorHolidaysResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
