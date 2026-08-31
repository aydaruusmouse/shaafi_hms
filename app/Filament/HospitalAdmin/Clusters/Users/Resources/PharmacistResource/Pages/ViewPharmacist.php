<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\PharmacistResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\PharmacistResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPharmacist extends ViewRecord
{
    protected static string $resource = PharmacistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
