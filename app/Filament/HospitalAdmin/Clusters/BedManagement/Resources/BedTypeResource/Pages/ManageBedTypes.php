<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedTypeResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBedTypes extends ManageRecords
{
    protected static string $resource = BedTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.bed_type_saved')),
        ];
    }
}
