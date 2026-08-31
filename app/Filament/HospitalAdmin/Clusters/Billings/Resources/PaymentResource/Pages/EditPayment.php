<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

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
        return __('messages.flash.payment_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
