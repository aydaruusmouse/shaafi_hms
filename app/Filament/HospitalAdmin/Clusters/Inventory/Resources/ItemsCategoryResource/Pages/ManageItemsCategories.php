<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemsCategoryResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemsCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageItemsCategories extends ManageRecords
{
    protected static string $resource = ItemsCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.flash.item_category_saved')),
        ];
    }
}
