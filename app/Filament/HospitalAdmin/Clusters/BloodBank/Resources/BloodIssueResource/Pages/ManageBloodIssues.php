<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodIssueResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBloodIssues extends ManageRecords
{
    protected static string $resource = BloodIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->successNotificationTitle(__('messages.flash.blood_issue_saved'))->modalHeading(__('messages.blood_issue.new_blood_issue')),
        ];
    }
}
