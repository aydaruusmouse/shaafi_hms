<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyUnitResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePathologyUnits extends ManageRecords
{
    protected static string $resource = PathologyUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.new_change.pathology_unit').' '.__('messages.common.saved_successfully')),
        ];
    }
}
