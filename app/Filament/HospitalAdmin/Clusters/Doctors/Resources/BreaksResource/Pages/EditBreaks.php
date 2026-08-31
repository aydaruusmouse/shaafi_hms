<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\BreaksResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditBreaks extends EditRecord
{
    protected static string $resource = BreaksResource::class;

    protected function getHeaderActions(): array
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
}
