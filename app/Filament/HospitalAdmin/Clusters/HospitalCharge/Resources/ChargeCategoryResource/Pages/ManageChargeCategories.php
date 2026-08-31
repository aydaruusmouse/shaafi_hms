<?php

namespace App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeCategoryResource\Pages;

use App\Filament\HospitalAdmin\Clusters\HospitalCharge\Resources\ChargeCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageChargeCategories extends ManageRecords
{
    protected static string $resource = ChargeCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->successNotificationTitle(__('messages.flash.charge_category_saved'))->before(fn ($record, $data, $action) => getUniqueNameValidation(static::getModel(), $record, $data, $this, isEdit: false)),
        ];
    }
}
