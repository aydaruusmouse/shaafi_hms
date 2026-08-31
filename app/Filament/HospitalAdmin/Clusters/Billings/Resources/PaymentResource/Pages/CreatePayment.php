<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

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
        return __('messages.flash.payment_saved');
    }
}
