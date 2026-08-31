<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditVisitor extends EditRecord
{
    protected static string $resource = VisitorResource::class;

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
        return __('messages.flash.visitor_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
