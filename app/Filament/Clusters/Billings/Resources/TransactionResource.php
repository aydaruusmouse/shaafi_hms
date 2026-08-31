<?php

namespace App\Filament\Clusters\Billings\Resources;

use App\Filament\Clusters\Billings;
use App\Filament\Clusters\Billings\Resources\TransactionResource\Pages;
use App\Models\Subscription;
use App\Models\Transaction;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Billings::class;

    protected static ?int $navigationSort = 2;

    public $statusFilter;

    public static function getNavigationLabel(): string
    {
        return __('messages.subscription_plans.transactions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('messages.subscription_plans.transactions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            $query = Transaction::whereHas('user', function ($q) {
                $q->where('department_id', 1);
            })->with(['transactionSubscription.subscriptionPlan', 'user'])->select('transactions.*');

            if (getLoggedInUser()->hasRole('Admin')) {
                $query->where('user_id', '=', getLoggedInUserId());
            }

            return $query;
        });

        return $table
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('user.hospital_name')
                    ->label(__('messages.hospitals_list.hospital_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_type')
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->payment_type == 1) {
                            return __('messages.transaction_filter.stripe');
                        } elseif ($record->payment_type == 2) {
                            return __('messages.transaction_filter.paypal');
                        } elseif ($record->payment_type == 3) {
                            return __('messages.transaction_filter.razorpay');
                        } elseif ($record->payment_type == 4) {
                            return __('messages.transaction_filter.cash');
                        } elseif ($record->payment_type == 5) {
                            return __('messages.transaction_filter.paytm');
                        } elseif ($record->payment_type == 6) {
                            return __('messages.transaction_filter.paystack');
                        } elseif ($record->payment_type == 7) {
                            return __('messages.phonepe.phonepe');
                        } elseif ($record->payment_type == 8) {
                            return __('messages.flutterwave.flutterwave');
                        } else {
                            return __('messages.common.n/a');
                        }
                    })->badge()
                    ->label(__('messages.payments')),
                TextColumn::make('amount')
                    ->label(__('messages.invoice.amount'))
                    ->searchable()
                    ->formatStateUsing(function (Transaction $record) {
                        return getAdminCurrencyFormat($record->transactionSubscription->subscriptionPlan->currency ?? 'usd', $record->amount);
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('messages.subscription_plans.transaction_date'))
                    ->view('tables.columns.created_at')
                    ->searchable()
                    ->sortable(),
                ViewColumn::make('is_manual_payment')
                    ->label(__('messages.subscription.payment_approved'))
                    ->view('tables.columns.in-manual-payment'),
                TextColumn::make('status')
                    ->label(__('messages.user.status'))
                    ->formatStateUsing(function (Transaction $record) {
                        if ($record->status == 1) {
                            return __('messages.paid');
                        } elseif ($record->status == 0) {
                            return __('messages.unpaid');
                        }
                    })
                    ->badge()
                    ->color(function (Transaction $record) {
                        if ($record->status == 1) {
                            return 'success';
                        } elseif ($record->status == 0) {
                            return 'danger';
                        }
                    }),
            ])
            ->filters([
                SelectFilter::make('payment_type')
                    ->label(__('messages.payments'))
                    ->placeholder(__('messages.common.select_payment'))
                    ->native(false)
                    ->options([
                        '' => __('messages.common.select_payment'),
                        '1' => __('messages.setting.stripe'),
                        '2' => __('messages.setting.paypal'),
                        '3' => __('messages.setting.razorpay'),
                        '4' => __('messages.transaction_filter.cash'),
                        '5' => __('messages.setting.paytm'),
                        '6' => __('messages.setting.paystack'),
                        '7' => __('messages.phonepe.phonepe'),
                        '8' => __('messages.flutterwave.flutterwave'),
                    ])
                    ->options(Subscription::PAYMENT_TYPES_FILTER),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTransactions::route('/'),
            // 'create' => Pages\CreateTransaction::route('/create'),
            // 'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
