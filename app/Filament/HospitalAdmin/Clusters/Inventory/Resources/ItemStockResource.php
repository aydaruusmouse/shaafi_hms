<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources;

use App\Filament\HospitalAdmin\Clusters\Inventory;
use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemStockResource\Pages;
use App\Models\Item;
use App\Models\ItemStock;
use App\Repositories\ItemStockRepository;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemStockResource extends Resource
{
    protected static ?string $model = ItemStock::class;

    protected static ?string $cluster = Inventory::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Item Stocks')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Item Stocks')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.item_stock.item_stock');
    }

    public static function getLabel(): string
    {
        return __('messages.item_stock.item_stock');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Item Stocks')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Item Stocks')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Item Stocks')) {
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
                Section::make()
                    ->schema([
                        Group::make()->schema([
                            Select::make('item_category_id')
                                ->live()
                                ->label(__('messages.item_stock.item_category').':')
                                ->relationship('item.itemcategory', 'name', fn (Builder $query) => $query->whereTenantId(getLoggedInUser()->tenant_id))
                                ->native(false)
                                ->searchable()
                                ->placeholder(__('messages.item.select_item_category'))
                                ->preload()
                                ->afterStateUpdated(function (callable $set, $get) {
                                    if ($get('item_category_id')) {
                                        $item = Item::where('item_category_id', $get('item_category_id'))->get()->pluck('id')->first() ?? null;
                                        $set('item_id', $item);
                                    }

                                    return false;
                                })
                                ->required()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.item_stock.item_category').' '.__('messages.fields.required'),
                                ]),
                            Select::make('item_id')
                                ->live()
                                ->label(__('messages.item_stock.item').':')
                                ->placeholder(__('messages.new_change.select_item'))
                                ->options(function ($get) {
                                    if ($get('item_category_id')) {
                                        return Item::where('item_category_id', $get('item_category_id'))->get()->pluck('name', 'id')->toArray();
                                    }
                                })
                                ->disabled(function ($get) {
                                    if ($get('item_category_id')) {
                                        $item = Item::where('item_category_id', $get('item_category_id'))->get()->pluck('id')->toArray();
                                        if (! empty($item)) {
                                            return false;
                                        }
                                    }

                                    return true;
                                })
                                ->native(false)
                                ->searchable()
                                ->preload()
                                ->required()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.item_stock.item').' '.__('messages.fields.required'),
                                ]),
                            TextInput::make('supplier_name')
                                ->label(__('messages.item_stock.supplier_name').':')
                                ->required()
                                ->validationAttribute(__('messages.item_stock.supplier_name'))
                                ->placeholder(__('messages.item_stock.supplier_name')),
                            TextInput::make('store_name')
                                ->label(__('messages.item_stock.store_name').':')
                                ->validationAttribute(__('messages.item_stock.store_name'))
                                ->required()
                                ->placeholder(__('messages.item_stock.store_name')),
                            TextInput::make('quantity')
                                ->label(__('messages.item_stock.quantity').':')
                                ->required()
                                ->validationAttribute(__('messages.item_stock.quantity'))
                                ->numeric()
                                ->minValue(1)
                                ->placeholder(__('messages.item_stock.quantity')),
                            TextInput::make('purchase_price')
                                ->label(__('messages.item_stock.purchase_price').':')
                                ->required()
                                ->validationAttribute(__('messages.item_stock.purchase_price'))
                                ->numeric()
                                ->minValue(1)
                                ->placeholder(__('messages.item_stock.purchase_price')),
                        ])->columns(3),
                        Textarea::make('description')
                            ->label(__('messages.item_stock.description').':')
                            ->rows(4)
                            ->placeholder(__('messages.item_stock.description')),
                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->collection(ItemStock::PATH)
                            ->label(__('messages.item_stock.attachment'))
                            ->avatar()
                            ->imageCropAspectRatio(null)
                            ->disk(config('app.media_disk'))
                            ->image(),
                        Hidden::make('avatar_remove'),
                    ])->columns(1),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Item Stocks')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    Tables\Columns\TextColumn::make('item.name')
                        ->label(__('messages.item.item'))
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('item.itemCategory.name')
                        ->label(__('messages.item_category.item_category'))
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('quantity')
                        ->label(__('messages.item_stock.quantity'))
                        ->searchable()
                        ->badge()
                        ->color('success')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('purchase_price')
                        ->label(__('messages.item_stock.purchase_price'))
                        ->searchable()
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->purchase_price) ?? __('messages.common.n/a'))
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label(__('messages.common.created_at'))
                        ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->created_at)->isoFormat('Do MMM, Y') ?? __('messages.common.n/a'))
                        ->badge()
                        ->searchable()
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function (ItemStock $itemStock, $record) {
                        $itemStock->where('id', $record->id);
                        if (! canAccessRecord(ItemStock::class, $itemStock->id)) {
                            Notification::make()
                                ->title(__('messages.common.not_allowed_to_access'))
                                ->warning()
                                ->send();
                        }

                        app(ItemStockRepository::class)->destroyItemStock($itemStock);

                        return Notification::make()
                            ->title(__('messages.flash.item_stock_deleted'))
                            ->success()
                            ->send();
                    }),
                ])
                ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListItemStocks::route('/'),
            'create' => Pages\CreateItemStock::route('/create'),
            'edit' => Pages\EditItemStock::route('/{record}/edit'),
        ];
    }
}
