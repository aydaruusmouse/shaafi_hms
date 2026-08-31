<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\AccountResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAccounts extends ManageRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.flash.account_save')),
        ];
    }
}
