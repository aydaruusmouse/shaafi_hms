<?php

namespace App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionPlans extends ListRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function toggleStatus($planId)
    {
        SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);
        SubscriptionPlan::where('id', $planId)->update(['is_default' => true]);

        Notification::make()
            ->title(__('messages.flash.default_plan_changed'))
            ->success()
            ->send();

        $this->resetTable();
    }
}
