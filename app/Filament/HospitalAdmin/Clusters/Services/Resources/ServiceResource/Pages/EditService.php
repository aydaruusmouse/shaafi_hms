<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\ServiceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\ServiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

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
        return __('messages.flash.service_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
