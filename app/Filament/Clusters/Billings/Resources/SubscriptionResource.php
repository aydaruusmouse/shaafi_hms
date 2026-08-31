<?php

namespace App\Filament\Clusters\Billings\Resources;

use App\Filament\Clusters\Billings;
use App\Filament\Clusters\Billings\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Billings::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('messages.subscription.subscriptions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.subscription.subscriptions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(__('messages.user.name').':')
                    ->relationship('user', 'id')
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.user.name').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\Select::make('subscription_plan_id')
                    ->relationship('subscriptionPlan', 'name'),
                Forms\Components\TextInput::make('transaction_id')
                    ->numeric(),
                Forms\Components\TextInput::make('plan_amount')
                    ->numeric()
                    ->minValue(1)
                    ->default(0),
                Forms\Components\TextInput::make('plan_frequency')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\DateTimePicker::make('trial_ends_at'),
                Forms\Components\TextInput::make('sms_limit')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('status')
                    ->required(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('')
                    ->schema([
                        TextEntry::make('user.hospital_name')
                            ->label(__('messages.hospitals_list.hospital_name').':'),
                        TextEntry::make('subscriptionPlan.name')
                            ->label(__('messages.subscription_plans.plan_name').':'),
                        TextEntry::make('starts_at')
                            ->dateTime()
                            ->label(__('messages.subscription_plans.start_date').': '),
                        TextEntry::make('ends_at')
                            ->dateTime()
                            ->label(__('messages.subscription_plans.end_date').': '),
                        TextEntry::make('plan_frequency')
                            ->formatStateUsing(function (Subscription $record) {
                                if ($record->plan_frequency == 1) {
                                    return __('messages.month');
                                }

                                return __('messages.year');
                            })
                            ->badge()
                            ->color(function (Subscription $record) {
                                if ($record->plan_frequency == 1) {
                                    return 'success';
                                }

                                return 'danger';
                            })
                            ->label(__('messages.subscription_plans.frequency').': '),
                        TextEntry::make('plan_amount')
                            ->label(__('messages.subscription_plans.amount').': ')
                            ->formatStateUsing(function (Subscription $record) {
                                $currency = $record->subscriptionPlan->currency;

                                return getAdminCurrencySymbol($currency).' '.$record->plan_amount;
                            }),

                        TextEntry::make('created_at')
                            ->label(__('messages.common.created_on').':')
                            ->since(),
                        TextEntry::make('updated_at')
                            ->label(__('messages.common.last_updated').':')
                            ->since(),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {

            return $query;
        });

        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('user.first_name')
                    ->formatStateUsing(function (Subscription $record) {
                        return $record->user->first_name.' '.$record->user->last_name;
                    })
                    ->label(__('messages.hospitals_list.hospital_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriptionPlan.name')
                    ->label(__('messages.subscription_plans.plan_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriptionPlan.price')
                    ->label(__('messages.subscription_plans.amount'))
                    ->searchable()
                    ->formatStateUsing(function (Subscription $record) {
                        $currency = $record->subscriptionPlan->currency;

                        return getAdminCurrencySymbol($currency).' '.$record->subscriptionPlan->price;
                    })
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->view('tables.columns.in-subscription-start-date')
                    ->label(__('messages.subscription_plans.start_date'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->view('tables.columns.in-subscription-end-date')
                    ->label(__('messages.subscription_plans.end_date'))
                    ->searchable()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subscriptionPlan.frequency')
                    ->label(__('messages.subscription_plans.plan_type'))
                    ->badge()
                    ->color(function (Subscription $record) {
                        if ($record->plan_frequency == 1) {
                            return 'success';
                        } else {
                            return 'danger';
                        }
                    })
                    ->formatStateUsing(function (Subscription $record) {
                        if ($record->plan_frequency == 1) {
                            return __('messages.month');
                        } else {
                            return __('messages.year');
                        }
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.user.status'))
                    ->badge()
                    ->color(function (Subscription $record) {
                        if ($record->status == 1) {
                            return 'success';
                        } else {
                            return 'danger';
                        }
                    })
                    ->getStateUsing(function (Subscription $record) {
                        if ($record->status == 1) {
                            return __('messages.filter.active');
                        } else {
                            return __('messages.filter.deactive');
                        }
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->placeholder(__('messages.common.select_status'))
                    ->label(__('messages.common.status').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ])->native(false),
                Filter::make('ends_at')
                    ->label(__('messages.new_change.plan_expire_status').':')
                    ->form([
                        Select::make('ends_at')
                            ->label(__('messages.new_change.plan_expire_status').':')
                            ->placeholder(__('messages.new_change.select_plan_expire_status'))
                            ->options([
                                '' => __('messages.filter.all'),
                                '1' => __('messages.new_change.expired'),
                                '0' => __('messages.new_change.not_expired'),
                            ])->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['ends_at'] == '1') {
                            return $query->where('ends_at', '<', now());
                        }
                        if ($data['ends_at'] == '0') {
                            return $query->where('ends_at', '>=', now());
                        } else {
                            return $query;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip(__('messages.common.view'))
                    ->color('info')->iconButton(),
                Tables\Actions\EditAction::make()
                    ->tooltip(__('messages.common.edit'))
                    ->iconButton(),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->recordAction(null)
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
