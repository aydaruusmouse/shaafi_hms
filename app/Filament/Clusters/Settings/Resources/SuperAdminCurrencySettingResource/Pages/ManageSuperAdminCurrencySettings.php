<?php

namespace App\Filament\Clusters\Settings\Resources\SuperAdminCurrencySettingResource\Pages;

use App\Filament\Clusters\Settings\Resources\SuperAdminCurrencySettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSuperAdminCurrencySettings extends ManageRecords
{
    protected static string $resource = SuperAdminCurrencySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('messages.currency.new_currency'))
                ->createAnother(false)
                ->modalWidth('md')
                ->modalHeading(__('messages.currency.new_currency'))
                ->successNotificationTitle(__('messages.new_change.currency_store')),
        ];
    }
}
