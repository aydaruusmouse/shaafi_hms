<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Widgets;

use App\Models\SaleMedicine;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MedicineDetail extends BaseWidget
{
    protected static string $view = 'filament.hospital-admin.widgets.purchase-medicine-detail';

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
                SaleMedicine::where('medicine_bill_id', $this->record)
            )
            ->columns([
                Tables\Columns\TextColumn::make('medicine.name')
                    ->label(__('messages.bill.item_name')),

                Tables\Columns\TextColumn::make('sale_quantity')
                    ->label(__('messages.bill.qty')),

                Tables\Columns\TextColumn::make('sale_price')
                    ->label(__('messages.bill.price')),

                Tables\Columns\TextColumn::make('tax')
                    ->label(__('messages.purchase_medicine.tax')),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('messages.bill.amount'))
                    ->formatStateUsing(function ($record) {
                        $total = $record->sale_quantity * $record->sale_price;

                        return getCurrencyFormat($total, 2);
                    }),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'desc');
    }
}
