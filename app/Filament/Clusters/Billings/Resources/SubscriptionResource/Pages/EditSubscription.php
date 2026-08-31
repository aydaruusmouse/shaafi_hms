<?php

namespace App\Filament\Clusters\Billings\Resources\SubscriptionResource\Pages;

use App\Filament\Clusters\Billings\Resources\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected static string $view = 'filament.resources.users.pages.edit-user';

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $subscription = Subscription::find($record->id);
        $subscription->update([
            'ends_at' => $data['ends_at'],
            'sms_limit' => $data['sms_limit'],
        ]);

        return $subscription;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    TextInput::make('user.hospital_name')
                        ->label(__('messages.hospitals_list.hospital_name').': ')
                        ->formatStateUsing(function ($record) {
                            return $record->user->hospital_name;
                        })
                        ->readonly(),
                    TextInput::make('subscriptionPlan.name')
                        ->label(__('messages.subscription_plans.plan_name').': ')
                        ->formatStateUsing(function ($record) {
                            return $record->subscriptionPlan->name;
                        })
                        ->readonly(),
                    TextInput::make('plan_frequency')
                        ->label(__('messages.subscription_plans.frequency').': ')
                        ->formatStateUsing(function ($record) {
                            if ($record->plan_frequency == 1) {
                                return __('messages.subscription.month');
                            }

                            return __('messages.subscription.year');
                        })
                        ->readonly(),
                    TextInput::make('status')
                        ->label(__('messages.user.status').':')
                        ->formatStateUsing(function ($record) {
                            if ($record->status == 1) {
                                return __('messages.common.active');
                            }

                            return __('messages.common.deactive');
                        })
                        ->readonly(),
                    TextInput::make('starts_at')
                        ->label(__('messages.subscription_plans.start_date').': ')
                        ->formatStateUsing(function ($record) {
                            return $record->starts_at->format('d-m-Y H:i A');
                        })
                        ->readonly(),
                    DateTimePicker::make('ends_at')
                        ->native(false)
                        ->label(__('messages.subscription_plans.end_date').': ')
                        ->displayFormat('d-m-Y H:i A')
                        ->minDate(fn ($operation, $state) => $operation == 'edit' ? $state : 'ends_at'),
                    TextInput::make('sms_limit')
                        ->label(__('messages.new_change.sms_limit').': ')
                        ->numeric()
                        ->default(0),
                ])->columns(4)->columnSpanFull(),

            ]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.subscription_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
