<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodDonationResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodDonationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBloodDonations extends ManageRecords
{
    protected static string $resource = BloodDonationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.blood_donation_saved'))->modalHeading(__('messages.blood_donation.new_blood_donation')),
        ];
    }
}
