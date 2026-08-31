<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource\Widgets;

use App\Models\Medicine;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MedicineList extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public $record;

    public function mount($record)
    {
        $this->record = $record->id;
    }

    public function getLabel(): string
    {
        return __('messages.medicine.medicine');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Medicine::where('category_id', $this->record)
            )
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.medicine.medicine'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label(__('messages.medicine.brand'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('messages.item.description'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label(__('messages.medicine.selling_price'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('buying_price')
                    ->label(__('messages.medicine.buying_price'))
                    ->searchable()
                    ->sortable(),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
