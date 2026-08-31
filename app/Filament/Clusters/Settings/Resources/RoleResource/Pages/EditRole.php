<?php

namespace App\Filament\Clusters\Settings\Resources\RoleResource\Pages;

use App\Filament\Clusters\Settings\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        RoleResource::syncModuleAccess($this->data, $this->record);
    }

}
