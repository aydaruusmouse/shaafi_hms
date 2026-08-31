<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCharges extends ManageRecords
{
    protected static string $resource = ChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->successNotificationTitle(__('messages.flash.charge_saved'))->before(fn ($record, $data, $action) => getUniqueCodeValidation(static::getModel(), $record, $data, $action, isEdit: false)),
        ];
    }
}
