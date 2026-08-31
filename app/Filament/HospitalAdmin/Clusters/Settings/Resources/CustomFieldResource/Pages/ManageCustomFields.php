<?php

namespace App\Filament\HospitalAdmin\Clusters\Settings\Resources\CustomFieldResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Settings\Resources\CustomFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomFields extends ManageRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)->successNotificationTitle(__('messages.custom_field.custom_field').' '.__('messages.common.saved_successfully')),
        ];
    }
}
