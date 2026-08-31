<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources\AccountantResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Users\Resources\AccountantResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewAccountant extends ViewRecord
{
    protected static string $resource = AccountantResource::class;

    protected static string $view = 'filament.resources.users.pages.view-accountant';

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
