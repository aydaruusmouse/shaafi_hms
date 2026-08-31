<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\NurseResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\NurseResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewNurse extends ViewRecord
{
    protected static string $resource = NurseResource::class;

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
