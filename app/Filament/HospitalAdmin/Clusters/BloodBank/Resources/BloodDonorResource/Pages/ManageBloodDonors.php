<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodDonorResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodDonorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBloodDonors extends ManageRecords
{
    protected static string $resource = BloodDonorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.blood_donor_saved'))->modalHeading(__('messages.blood_donor.new_blood_donor')),
        ];
    }
}
