<?php

namespace App\Filament\Clusters\Billings\Resources\SubscriptionResource\Pages;

use App\Filament\Clusters\Billings\Resources\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

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
        return __('messages.flash.subscription_created');
    }
}
