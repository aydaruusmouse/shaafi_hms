<?php

namespace App\Livewire;

use App\Models\Subscription;
use App\Models\Transaction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Livewire\Component;

class HospitalTransactionTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }

    public function GetRecord()
    {
        $query = Transaction::where('user_id', $this->record->id);

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('payment_type')
                    ->formatStateUsing(function ($record) {
                        if ($record->payment_type == 1) {
                            return \App\Models\Subscription::PAYMENT_TYPES[1];
                        } elseif ($record->payment_type == 2) {
                            return \App\Models\Subscription::PAYMENT_TYPES[2];
                        } elseif ($record->payment_type == 3) {
                            return \App\Models\Subscription::PAYMENT_TYPES[3];
                        } elseif ($record->payment_type == 4) {
                            return \App\Models\Subscription::PAYMENT_TYPES[4];
                        } elseif ($record->payment_type == 5) {
                            return \App\Models\Subscription::PAYMENT_TYPES[5];
                        } elseif ($record->payment_type == 6) {
                            return \App\Models\Subscription::PAYMENT_TYPES[6];
                        } elseif ($record->payment_type == 0) {
                            return \App\Models\Subscription::PAYMENT_TYPES[0];
                        }
                    })
                    ->badge()
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
                    ->sortable()
                    ->color(function (Transaction $record) {
                        if ($record->status == 1) {
                            return 'success';
                        } elseif ($record->status == 0) {
                            return 'danger';
                        }
                    }),
            ])->filters([
                SelectFilter::make('payment_type')
                    ->label(__('messages.payments').':')
                    ->placeholder(__('messages.common.select_payment'))
                    ->native(false)
                    ->options(Subscription::PAYMENT_TYPES),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.hospital-transaction-table');
    }
}
