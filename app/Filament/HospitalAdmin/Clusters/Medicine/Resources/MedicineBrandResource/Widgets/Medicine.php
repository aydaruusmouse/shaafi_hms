<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBrandResource\Widgets;

use App\Models\Medicine as ModelsMedicine;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class Medicine extends BaseWidget
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
                ModelsMedicine::where('category_id', $this->record)
            )
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('messages.medicine.category'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.medicine.medicine'))
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
