<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Widgets;

use App\Models\BillItems;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BillItemList extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public $record;

    public function mount($record)
    {
        $this->record = $record->id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BillItems::where('bill_id', $this->record)
            )
            ->columns([
                Tables\Columns\TextColumn::make('item_name')
                    ->label(__('messages.bill.item_name')),

                Tables\Columns\TextColumn::make('qty')
                    ->label(__('messages.bill.qty')),

                Tables\Columns\TextColumn::make('price')
                    ->label(__('messages.bill.price')),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('messages.bill.amount'))
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money()
                            ->formatStateUsing(fn ($state) => getCurrencyFormat($state))
                            ->label(__('messages.bill.total_amount')),
                    ]),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
