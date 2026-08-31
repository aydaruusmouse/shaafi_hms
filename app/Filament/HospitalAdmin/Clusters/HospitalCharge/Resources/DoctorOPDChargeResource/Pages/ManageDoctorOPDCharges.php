<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\DoctorOPDChargeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\DoctorOPDChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDoctorOPDCharges extends ManageRecords
{
    protected static string $resource = DoctorOPDChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.flash.OPD_charge_saved')),
        ];
    }
}
