<?php

namespace App\Filament\Clusters\Billings\Resources;

use App\Filament\Clusters\Billings;
use App\Filament\Clusters\Billings\Resources\SubscriptionPlanResource\Pages;
use App\Models\Feature;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminCurrencySetting;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Billings::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.subscription_plan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.subscription_plan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormSection::make('')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->validationAttribute(__('messages.user.name'))
                            ->label(__('messages.user.name').':')
                            ->placeholder(__('messages.user.name'))
                            ->maxLength(255),
                        Forms\Components\Select::make('currency')
                            ->required()
                            ->placeholder(__('messages.subscription_plans.select_currency'))
                            ->options(SuperAdminCurrencySetting::all()->mapWithKeys(function ($currency) {
                                return [$currency->currency_code => strtoupper($currency->currency_code).' - '.$currency->currency_name];
                            }))
                            ->native(false)
                            ->label(__('messages.subscription_plans.currency').':')
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.subscription_plans.currency').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('messages.invoice.price'))
                            ->label(__('messages.invoice.price').':')
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.invoice.price').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('frequency')
                            ->native(false)
                            ->options([
                                1 => __('messages.month'),
                                2 => __('messages.year'),
                            ])
                            ->label(__('messages.subscription_plans.plan_type').':')
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.subscription_plans.plan_type').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('trial_days')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label(__('messages.subscription_plans.valid_until').':')
                            ->placeholder(__('messages.subscription_plans.valid_until')),
                        Forms\Components\TextInput::make('sms_limit')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->label(__('messages.new_change.sms_limit').':')
                            ->placeholder(__('messages.new_change.sms_limit'))
                            ->reactive()
                            ->visible(fn ($get) => in_array(Feature::where('name', 'SMS / Mail')->value('id'), (array) $get('plan_feature'))),
                    ])->columns(3),
                FormSection::make(['name' => 'Plan Features'])
                    ->schema([
                        CheckboxList::make('plan_feature')
                            ->bulkToggleable()
                            ->relationship('features', 'name')
                            ->options(Feature::where('has_parent', 0)->where('is_default', 0)->pluck('name', 'id'))
                            ->label(__('messages.subscription_plans.plan_features').':')
                            ->columns(4)
                            ->live(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->recordUrl(false)
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->label(__('messages.user.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('messages.bill.price'))
                    ->formatStateUsing(function (SubscriptionPlan $record) {
                        $currency = $record->currency;

                        return getAdminCurrencySymbol($currency).' '.$record->price;
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label(__('messages.subscription_plans.plan_type'))
                    ->formatStateUsing(function (SubscriptionPlan $record) {
                        if ($record->frequency == 1) {
                            return __('messages.month');
                        } else {
                            return __('messages.year');
                        }
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trial_days')
                    ->label(__('messages.subscription_plans.valid_until'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function (SubscriptionPlan $record) {
                        return $record->trial_days.' '.__('messages.prescription.days');
                    }),
                TextColumn::make('id')
                    ->formatStateUsing(function (SubscriptionPlan $record) {
                        return $record->subscription->count();
                    })
                    ->badge()
                    ->label(__('messages.subscription_plans.active_plan')),
                // ToggleColumn::make('is_default')
                //     ->updateStateUsing(function (SubscriptionPlan $record, bool $state) {
                //         if ($state) {
                //             SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);
                //             $record->is_default = true;
                //             $record->save();
                //             Notification::make()
                //                 ->body('Default plan changed successfully.')
                //                 ->success()
                //                 ->send();
                //         }
                //     })
                //     ->label('MAKE DEFAULT'),
                ViewColumn::make('is_default')
                    ->label(Str::ucfirst(strtolower(__('messages.subscription_plans.make_default'))))
                    ->view('tables.columns.status-switcher'),

            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->filters([
                SelectFilter::make('frequency')
                    ->label(__('messages.subscription_plans.plan_type').':')
                    ->options([
                        '' => __('messages.subscription_plans.select_plan_type'),
                        '1' => __('messages.month'),
                        '2' => __('messages.year'),
                    ])->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->color('info')
                    ->iconButton(),
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->tooltip(__('messages.common.delete'))
                    ->iconButton()
                    ->action(function ($record) {
                        $result = Subscription::where('subscription_plan_id', $record['id'])->where(
                            'status',
                            Subscription::ACTIVE
                        )->count();
                        if ($result > 0) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.subscription_plan_cant_deleted'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.subscription_plan_deleted'))
                            ->send();
                    })
                    ->visible(function (SubscriptionPlan $record) {
                        return $record->is_default == false;
                    }),

            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptionPlans::route('/'),
            'create' => Pages\CreateSubscriptionPlan::route('/create'),
            'view' => Pages\ViewSubscriptionPlan::route('/{record}'),
            'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
