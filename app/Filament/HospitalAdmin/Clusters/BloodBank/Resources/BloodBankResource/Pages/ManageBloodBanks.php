<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodBankResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodBankResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBloodBanks extends ManageRecords
{
    protected static string $resource = BloodBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.blood_group_saved'))->modalHeading(__('messages.hospital_blood_bank.new_blood_group')),
        ];
    }
}
