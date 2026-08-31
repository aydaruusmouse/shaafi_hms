<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources;

use App\Filament\HospitalAdmin\Clusters\Medicine;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Medicine as MedicineModel;
use App\Models\PurchasedMedicine;
use App\Models\SaleMedicine;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MedicineResource extends Resource
{
    protected static ?string $model = MedicineModel::class;

    protected static ?string $cluster = Medicine::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicines')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicines')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.medicines');
    }

    public static function getLabel(): string
    {
        return __('messages.medicines');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicines')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicines')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicines')) {
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
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('messages.medicine.medicine').':')
                            ->placeholder(__('messages.medicine.medicine'))
                            ->validationMessages([
                                'unique' => __('messages.medicine.medicine').' '.__('messages.common.is_already_exists'),
                            ])
                            ->required(),
                        Forms\Components\Select::make('category_id')
                            ->label(__('messages.medicine.category').':')
                            ->placeholder(__('messages.medicine.category'))
                            ->required()
                            ->options(Category::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.medicine.category').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('brand_id')
                            ->label(__('messages.medicine.brand').':')
                            ->placeholder(__('messages.medicine.brand'))
                            ->required()
                            ->options(Brand::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.medicine.brand').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('salt_composition')
                            ->label(__('messages.medicine.salt_composition').':')
                            ->placeholder(__('messages.medicine.salt_composition'))
                            ->validationAttribute(__('messages.medicine.salt_composition'))
                            ->required(),
                        Forms\Components\TextInput::make('buying_price')
                            ->label(__('messages.medicine.buying_price').':')
                            ->placeholder(__('messages.medicine.buying_price'))
                            ->numeric()
                            ->step(0.01)
                            ->validationAttribute(__('messages.medicine.buying_price'))
                            ->required(),
                        Forms\Components\TextInput::make('selling_price')
                            ->label(__('messages.medicine.selling_price').':')
                            ->placeholder(__('messages.medicine.selling_price'))
                            ->numeric()
                            ->step(0.01)
                            ->validationAttribute(__('messages.medicine.selling_price'))
                            ->required(),
                        Textarea::make('side_effects')
                            ->label(__('messages.medicine.side_effects').':')
                            ->placeholder(__('messages.medicine.side_effects'))
                            ->rows(4),
                        Textarea::make('description')
                            ->label(__('messages.medicine.description').':')
                            ->placeholder(__('messages.medicine.description'))
                            ->rows(4),
                        Hidden::make('quantity')
                            ->default(0),
                        Hidden::make('available_quantity')
                            ->default(0),
                    ])->columns(2),

            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name')
                    ->label(__('messages.medicine.medicine').':'),
                TextEntry::make('brand.name')
                    ->label(__('messages.medicine.brand').':'),
                TextEntry::make('category.name')
                    ->label(__('messages.medicine.category').':'),
                TextEntry::make('quantity')
                    ->label(__('messages.item_stock.quantity').':'),
                TextEntry::make('available_quantity')
                    ->label(__('messages.issued_item.available_quantity').':'),
                TextEntry::make('salt_composition')
                    ->label(__('messages.medicine.salt_composition').':'),
                TextEntry::make('selling_price')
                    ->label(__('messages.medicine.selling_price').':'),
                TextEntry::make('buying_price')
                    ->label(__('messages.medicine.buying_price').':'),
                TextEntry::make('side_effects')
                    ->label(__('messages.medicine.side_effects').':')
                    ->getStateUsing(fn ($record) => $record->side_effects ?? __('messages.common.n/a')),
                TextEntry::make('created_at')
                    ->since()
                    ->label(__('messages.common.created_on').':'),
                TextEntry::make('updated_at')
                    ->since()
                    ->label(__('messages.common.last_updated').':'),
                TextEntry::make('description')
                    ->label(__('messages.medicine.description').':')
                    ->getStateUsing(fn ($record) => $record->description ?? __('messages.common.n/a')),

            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicines')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.medicine.medicine'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label(__('messages.medicine.brand'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label(__('messages.item.available_quantity'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label(__('messages.medicine.selling_price'))
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state) ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('buying_price')
                    ->label(__('messages.medicine.buying_price'))
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state) ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->modalWidth('2xl')->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (MedicineModel $record) {
                        if (! canAccessRecord(MedicineModel::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.medicine_not_found'))
                                ->send();
                        }
                        $purchaseMedicine = PurchasedMedicine::whereMedicineId($record->id)->get();
                        $saleMedicine = SaleMedicine::whereMedicineId($record->id)->get();
                        if (isset($purchaseMedicine) && ! empty($purchaseMedicine)) {
                            $purchaseMedicine->map->delete();
                        }
                        if (isset($saleMedicine) && ! empty($saleMedicine)) {
                            $saleMedicine->map->delete();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.medicine_deleted'))
                            ->send();
                    })
                    ->successNotificationTitle(__('messages.flash.medicine_deleted')),
            ])
            ->recordUrl(false)
            ->actionsColumnLabel(__('messages.common.action'))
            ->defaultSort('id', 'desc')
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
            'index' => Pages\ListMedicines::route('/'),
            'create' => Pages\CreateMedicine::route('/create'),
            'edit' => Pages\EditMedicine::route('/{record}/edit'),
        ];
    }
}
