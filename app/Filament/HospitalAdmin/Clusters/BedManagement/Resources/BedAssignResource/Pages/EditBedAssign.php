<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource\Pages;

use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditBedAssign extends EditRecord
{
    protected static string $resource = BedAssignResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.bed_assign_update');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
