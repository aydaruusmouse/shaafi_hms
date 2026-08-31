<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources;

use App\Filament\HospitalAdmin\Clusters\Medicine;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\UsedMedicineResource\Pages;
use App\Models\SaleMedicine;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UsedMedicineResource extends Resource
{
    protected static ?string $model = SaleMedicine::class;

    protected static ?string $cluster = Medicine::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('messages.used_medicine.used_medicine');
    }

    public static function getLabel(): string
    {
        return __('messages.used_medicine.used_medicine');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
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
            return $query->whereHas('medicineBill', function (Builder $q) {
                $q->where('payment_status', true)->whereTenantId(auth()->user()->tenant_id);
            });
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('medicine.name')
                    ->label(__('messages.medicine.medicines'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_quantity')
                    ->label(__('messages.used_medicine.used_quantity'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('medicineBill.model_type')
                    ->label(__('messages.used_medicine.used_at'))
                    ->formatStateUsing(function ($record) {
                        $modelType = $record->medicineBill->model_type ?? 'N/A';
                        $className = class_basename($modelType);

                        return $className;
                    })->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.message.date'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->html(),
            ])
            ->filters([
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
            'index' => Pages\ListUsedMedicines::route('/'),
        ];
    }
}
