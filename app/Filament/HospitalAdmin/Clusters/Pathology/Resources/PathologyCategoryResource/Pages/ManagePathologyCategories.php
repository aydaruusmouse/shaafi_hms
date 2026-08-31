<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyCategoryResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePathologyCategories extends ManageRecords
{
    protected static string $resource = PathologyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.flash.pathology_category_saved')),
        ];
    }
}
