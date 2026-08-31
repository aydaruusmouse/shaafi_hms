<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyCategoryResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRadiologyCategories extends ManageRecords
{
    protected static string $resource = RadiologyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.radiology_category_saved'))->modalHeading(__('messages.radiology_category.new_radiology_category'))->before(fn ($record, $data, $action) => getUniqueNameValidation(static::getModel(), $record, $data, $this, false)),
        ];
    }
}
