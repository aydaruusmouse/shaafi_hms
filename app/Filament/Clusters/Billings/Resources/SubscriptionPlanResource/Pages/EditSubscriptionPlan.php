<?php

namespace App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSubscriptionPlan extends EditRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['currency'] = strtoupper($data['currency']);

        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.subscription_plan_updated');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['currency'] = strtolower($data['currency']);

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
