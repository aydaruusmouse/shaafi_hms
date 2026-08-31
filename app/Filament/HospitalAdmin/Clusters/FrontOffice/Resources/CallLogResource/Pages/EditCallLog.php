<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCallLog extends EditRecord
{
    protected static string $resource = CallLogResource::class;

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
        return __('messages.flash.call_log_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
