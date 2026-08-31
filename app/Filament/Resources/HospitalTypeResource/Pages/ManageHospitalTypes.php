<?php

namespace App\Filament\Resources\HospitalTypeResource\Pages;

use App\Filament\Resources\HospitalTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHospitalTypes extends ManageRecords
{
    protected static string $resource = HospitalTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('messages.common.new').' '.__('messages.hospitals_type'))
                ->modalWidth('md')
                ->successNotificationTitle(__('messages.hospitals_type').' '.__('messages.common.saved_successfully'))
                ->createAnother(false),
        ];
    }
}
