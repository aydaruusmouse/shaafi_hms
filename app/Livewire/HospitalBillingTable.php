<?php

namespace App\Livewire;

use App\Models\Subscription;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Livewire\Component;

class HospitalBillingTable extends Component implements HasForms, HasTable
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
        $query = Subscription::where('user_id', $this->record->id);

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->defaultSort('id', 'asc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('subscriptionPlan.name')
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->label(__('messages.subscription_plans.plan_name')),
                TextColumn::make('transaction_id')
                    ->badge(function ($record) {
                        if ($record->transaction_id && $record->transactions->payment_type) {
                            return true;
                        } else {
                            return false;
                        }
                    })
                    ->label(__('messages.subscription_plans.transaction'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(function ($record) {
                        if ($record->transaction_id) {
                            if ($record->transactions->payment_type == 1) {
                                return \App\Models\Subscription::PAYMENT_TYPES[1];
                            } elseif ($record->transactions->payment_type == 2) {
                                return \App\Models\Subscription::PAYMENT_TYPES[2];
                            } elseif ($record->transactions->payment_type == 3) {
                                return \App\Models\Subscription::PAYMENT_TYPES[3];
                            } elseif ($record->transactions->payment_type == 4) {
                                return \App\Models\Subscription::PAYMENT_TYPES[4];
                            } elseif ($record->transactions->payment_type == 5) {
                                return \App\Models\Subscription::PAYMENT_TYPES[5];
                            } elseif ($record->transactions->payment_type == 6) {
                                return \App\Models\Subscription::PAYMENT_TYPES[6];
                            } elseif ($record->transactions->payment_type == 0) {
                                return \App\Models\Subscription::PAYMENT_TYPES[0];
                            }
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan_amount')
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => getAdminCurrencyFormat($record->subscriptionPlan->currency, $record->plan_amount))
                    ->label(__('messages.subscription_plans.amount'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan_frequency')
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => $record->plan_frequency == 1 ? 'Month' : 'Year')
                    ->label(__('messages.subscription_plans.frequency'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->default(__('messages.common.n/a'))
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->formatStateUsing(fn ($record) => ' <span>'.\Carbon\Carbon::parse($record->start_date)->isoFormat('LT').' <br>'.\Carbon\Carbon::parse($record->strats_at)->isoFormat('Do MMMM YYYY').'</span>')
                    ->label(__('messages.subscription_plans.start_date'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->default(__('messages.common.n/a'))
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->formatStateUsing(fn ($record) => ' <span>'.\Carbon\Carbon::parse($record->end_date)->isoFormat('LT').' <br>'.\Carbon\Carbon::parse($record->ends_at)->isoFormat('Do MMMM YYYY').'</span>')
                    ->label(__('messages.subscription_plans.end_date'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->default(__('messages.common.n/a'))
                    ->html()
                    ->extraAttributes(['class' => 'text-center'])
                    ->formatStateUsing(fn ($record) => ' <span>'.\Carbon\Carbon::parse($record->trial_ends_at)->isoFormat('LT').' <br>'.\Carbon\Carbon::parse($record->trial_ends_at)->isoFormat('Do MMMM YYYY').'</span>')
                    ->label(__('messages.subscription_plans.trail_end_date'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->color(fn ($record) => $record->status == 1 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($record) => $record->status == 1 ? __('messages.filter.active') : __('messages.filter.deactive')),
            ])->filters([
                SelectFilter::make('status')
                    ->label(__('messages.common.status').':')
                    ->native(false)
                    ->searchable()
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ]),
                SelectFilter::make('transactions.payment_type')
                    ->label(__('messages.payments').':')
                    ->native(false)
                    ->options(Arr::except(Subscription::PAYMENT_TYPES, [0]))
                    ->query(function (Builder $query, $state) {
                        if (isset($state['value'])) {
                            $query->whereHas('transactions', function (Builder $query) use ($state) {
                                $query->where('payment_type', '=', $state['value']);
                            });
                        }
                    }),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.hospital-billing-table');
    }
}
