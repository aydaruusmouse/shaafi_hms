<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyParameterResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyParameterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePathologyParameters extends ManageRecords
{
    protected static string $resource = PathologyParameterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->modalWidth('md')->successNotificationTitle(__('messages.new_change.pathology_unit').' '.__('messages.common.saved_successfully')),
        ];
    }
}
