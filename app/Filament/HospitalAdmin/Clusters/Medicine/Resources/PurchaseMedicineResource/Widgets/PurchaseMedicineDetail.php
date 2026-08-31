<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Widgets;

use App\Models\PurchasedMedicine;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PurchaseMedicineDetail extends BaseWidget
{
    protected static string $view = 'filament.hospital-admin.widgets.purchase-medicine-detail';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public $record;

    public function mount($record)
    {
        $this->record = $record->id;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchasedMedicine::where('purchase_medicines_id', $this->record)
            )
            ->columns([
                Tables\Columns\TextColumn::make('medicines.name')
                    ->label(__('messages.medicine.medicines')),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label(__('messages.purchase_medicine.expiry_date'))
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('jS M, Y')
                    ),
                Tables\Columns\TextColumn::make('lot_no')
                    ->label(__('messages.purchase_medicine.lot_no')),

                Tables\Columns\TextColumn::make('medicines.buying_price')
                    ->label(__('messages.medicine.buying_price')),

                Tables\Columns\TextColumn::make('medicines.selling_price')
                    ->label(__('messages.medicine.selling_price')),

                Tables\Columns\TextColumn::make('tax')
                    ->label(__('messages.purchase_medicine.tax_amount')),

                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('messages.purchase_medicine.quantity')),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('messages.purchase_medicine.amount'))
                    ->formatStateUsing(function ($record, $state) {
                        if (empty($state)) {
                            return $record->medicines?->buying_price * $record?->quantity;
                        }

                        return $state;
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
