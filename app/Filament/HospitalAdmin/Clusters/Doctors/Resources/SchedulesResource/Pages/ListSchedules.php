<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\SchedulesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchedules extends ListRecords
{
    protected static string $resource = SchedulesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
