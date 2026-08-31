<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorDepartmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorDepartmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDoctorDepartments extends ManageRecords
{
    protected static string $resource = DoctorDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.flash.department_saved')),
        ];
    }
}
