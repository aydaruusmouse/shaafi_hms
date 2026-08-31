<?php

namespace App\Filament\HospitalAdmin\Clusters\SmsMail\Resources\SmsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\SmsMail\Resources\SmsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSms extends ViewRecord
{
    protected static string $resource = SmsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }
}
