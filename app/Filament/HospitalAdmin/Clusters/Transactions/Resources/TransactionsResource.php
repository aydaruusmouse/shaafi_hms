<?php

namespace App\Filament\HospitalAdmin\Clusters\Transactions\Resources;

use App\Filament\HospitalAdmin\Clusters\Transactions;
use App\Filament\HospitalAdmin\Clusters\Transactions\Resources\TransactionsResource\Pages;
use App\Models\Transaction;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TransactionsResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $cluster = Transactions::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.subscription_plans.transactions');
    }

    public static function getLabel(): string
    {
        return __('messages.subscription_plans.transactions');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
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
            $query->whereHas('user', function ($q) {
                $q->where('department_id', 1);
            })->with(['transactionSubscription.subscriptionPlan', 'user'])->where('tenant_id', auth()->user()->tenant_id)
                ->select('transactions.*');
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.hospital_name')
                    ->label(__('messages.hospitals_list.hospital_name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('payment_type')
                    ->label(__('messages.payments'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->payment_type == 1) {
                            return 'Stripe';
                        } elseif ($record->payment_type == 2) {
                            return 'Paypal';
                        } elseif ($record->payment_type == 3) {
                            return 'Razorpay';
                        } elseif ($record->payment_type == 4) {
                            return 'Cash';
                        } elseif ($record->payment_type == 5) {
                            return 'Paytm';
                        } elseif ($record->payment_type == 6) {
                            return 'Paystack';
                        } elseif ($record->payment_type == 7) {
                            return 'Phonepe';
                        } elseif ($record->payment_type == 8) {
                            return 'Flutterwave';
                        } else {
                            return 'N/A';
                        }
                    })
                    ->color(function ($record) {
                        if ($record->payment_type == 1) {
                            return 'primary';
                        } elseif ($record->payment_type == 2) {
                            return 'primary';
                        } elseif ($record->payment_type == 3) {
                            return 'primary';
                        } elseif ($record->payment_type == 4) {
                            return 'primary';
                        } elseif ($record->payment_type == 5) {
                            return 'primary';
                        } elseif ($record->payment_type == 6) {
                            return 'primary';
                        } elseif ($record->payment_type == 7) {
                            return 'primary';
                        } elseif ($record->payment_type == 8) {
                            return 'primary';
                        } else {
                            return 'danger';
                        }
                    })
                    ->searchable(),

                TextColumn::make('amount')
                    ->label(__('messages.subscription_plans.amount'))
                    ->formatStateUsing(function ($record) {
                        if (isset($record->transactionSubscription->subscriptionPlan)) {
                            return getAdminCurrencyFormat($record->transactionSubscription->subscriptionPlan->currency, $record->amount);
                        } else {
                            return '$'.number_format($record->amount, 2);
                        }
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('messages.subscription_plans.transaction_date'))
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->isoFormat('LT').'<br>'.\Carbon\Carbon::parse($state)->isoFormat('Do MMM, Y') ?? __('messages.common.n/a'))
                    ->sortable()
                    ->badge()
                    ->extraAttributes(['class' => 'text-center'])
                    ->html()
                    ->searchable(),
                // ViewColumn::make('is_manual_payment')
                //     ->label(__('messages.subscription.payment_approved'))
                //     ->view('tables.columns.hospitalAdmin.in-manual-payment-transaction'),
                TextColumn::make('is_manual_payment')
                    ->label(__('messages.subscription.payment_approved'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->is_manual_payment == 0 && $record->status == 0) {
                            return __('messages.subscription.waiting_for_approval');
                        } elseif ($record->is_manual_payment == 1) {
                            return __('messages.subscription.approved');
                        } elseif ($record->is_manual_payment == 2) {
                            return __('messages.subscription.denied');
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->color(function ($record) {
                        if ($record->is_manual_payment == 0 && $record->status == 0) {
                            return 'warning';
                        } elseif ($record->is_manual_payment == 1) {
                            return 'success';
                        } elseif ($record->is_manual_payment == 2) {
                            return 'danger';
                        } else {
                            return null;
                        }
                    }),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->status == 1) {
                            return __('messages.paid');
                        } elseif ($record->status == 0) {
                            return __('messages.unpaid');
                        }
                    })
                    ->color(function ($record) {
                        if ($record->status == 1) {
                            return 'success';
                        } elseif ($record->status == 0) {
                            return 'danger';
                        }
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->bulkActions([
                //
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
            'create' => Pages\CreateTransactions::route('/create'),
            'edit' => Pages\EditTransactions::route('/{record}/edit'),
        ];
    }
}
