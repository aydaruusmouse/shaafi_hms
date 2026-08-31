<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources\IssuedItemResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\IssuedItemResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditIssuedItem extends EditRecord
{
    protected static string $resource = IssuedItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.issued_item_saved');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
