<?php

namespace App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionPlan extends ViewRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('edit')
                ->label(__('messages.common.edit'))
                ->url(static::getResource()::getUrl('edit', ['record' => $this->record->id])),
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function getTitle(): string
    {
        return __('messages.subscription_plans.view_subscription_plan');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('')
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('messages.subscription_plans.name').':'),
                        TextEntry::make('currency')
                            ->label(__('messages.subscription_plans.currency').':'),
                        TextEntry::make('price')
                            ->label(__('messages.subscription_plans.price').':'),
                        TextEntry::make('frequency')
                            ->formatStateUsing(function (SubscriptionPlan $record) {
                                if ($record->frequency == 1) {
                                    return 'Month';
                                }

                                return 'Year';
                            })
                            ->badge()
                            ->label(__('messages.subscription_plans.frequency').':'),
                        TextEntry::make('trial_days')
                            ->label(__('messages.subscription_plans.trail_plan').':'),
                        TextEntry::make('id')
                            ->label(__('messages.subscription_plans.active_plan').':')
                            ->formatStateUsing(function (SubscriptionPlan $record) {
                                return $record->subscription->count();
                            }),
                        TextEntry::make('created_at')
                            ->label(__('messages.common.created_on').':')
                            ->since(),
                        TextEntry::make('updated_at')
                            ->label(__('messages.common.updated_at').':')
                            ->since(),
                        // TextEntry::make('')
                        //     ->label('Plan Features: '),
                        // TextEntry::make('')
                        //     ->label('')
                        //     ->since(),

                    ])->columns(2),
                Section::make('Plan Features')
                    ->label(__('messages.subscription_plans.plan_features').':')
                    ->schema($this->planFeatures($this->record))
                    ->columns(10),
            ]);
    }

    public static function planFeatures($record)
    {
        $features = [];
        foreach ($record->features as $feature) {
            $features[] = TextEntry::make($feature->name)
                ->label('')
                ->badge()
                ->icon('heroicon-o-check-circle')
                ->default($feature->name)
                ->tooltip(empty($feature->submenu) ? null : __('messages.subscription_plans.default_plan_text_one').' '.$feature->submenu.' '.__('messages.subscription_plans.default_plan_text_two'));
        }

        return $features;
    }
}
