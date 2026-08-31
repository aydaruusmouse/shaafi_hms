<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\AccountResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->modalWidth('md')
                ->label(__('messages.common.edit'))
                ->successNotificationTitle(__('messages.flash.account_update')),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
