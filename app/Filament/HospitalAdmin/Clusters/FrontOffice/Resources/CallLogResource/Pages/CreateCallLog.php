<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateCallLog extends CreateRecord
{
    protected static string $resource = CallLogResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.call_log_saved');
    }
}
