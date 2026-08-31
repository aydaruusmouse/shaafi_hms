<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources;

use App\Filament\HospitalAdmin\Clusters\Inventory;
use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemResource\Pages;
use App\Models\IssuedItem;
use App\Models\Item;
use App\Models\ItemStock;
use Filament\Forms\Components;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $cluster = Inventory::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Items')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Items')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.items');
    }

    public static function getLabel(): string
    {
        return __('messages.items');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Items')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Items')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Items')) {
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
                            Components\TextInput::make('name')
                                ->label(__('messages.item.name').':')
                                ->required()
                                ->validationMessages([
                                    'unique' => __('messages.item.name').' '.__('messages.common.is_already_exists'),
                                ])
                                ->placeholder(__('messages.item.name')),
                            Components\Select::make('item_category_id')
                                ->label(__('messages.item.item_category').':')
                                ->relationship('itemcategory', 'name', fn (Builder $query) => $query->whereTenantId(getLoggedInUser()->tenant_id))
                                ->native(false)
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder(__('messages.item.select_item_category')) // Assuming $itemCategories is an array of options
                                ->reactive()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.item.item_category').' '.__('messages.fields.required'),
                                ]),
                            Components\TextInput::make('unit')
                                ->label(__('messages.item.unit').':')
                                ->required()
                                ->validationAttribute(__('messages.item.unit'))
                                ->placeholder(__('messages.item.unit'))
                                ->maxLength(4)
                                ->minLength(1)
                                ->numeric(),
                        ])->columns(3),
                        Components\Textarea::make('description')
                            ->label(__('messages.item.description').':')
                            ->rows(4)
                            ->placeholder(__('messages.item.description')),
                    ])->columns(1),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Items')) {
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
                    TextColumn::make('name')
                        ->label(__('messages.common.name'))
                        ->sortable(),
                    TextColumn::make('itemcategory.name')
                        ->label(__('messages.item_category.item_category'))
                        ->sortable(),
                    TextColumn::make('unit')
                        ->label(__('messages.item.unit'))
                        ->color('success')
                        ->badge()
                        ->sortable()->alignEnd(),
                    TextColumn::make('available_quantity')
                        ->label(__('messages.item.available_quantity'))
                        ->color('info')
                        ->badge()
                        ->sortable()->alignEnd(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                        $item = Item::find($record->id);
                        if (! canAccessRecord(Item::class, $item->id)) {
                            return Notification::make()
                                ->title(__('messages.flash.item_cant_deleted'))
                                ->warning()
                                ->send();
                        }
                        $itemModel = [
                            ItemStock::class,
                            IssuedItem::class,
                        ];
                        $result = canDelete($itemModel, 'item_id', $item->id);
                        if ($result) {
                            return Notification::make()
                                ->title(__('messages.flash.item_cant_deleted'))
                                ->warning()
                                ->send();
                        }

                        $item->delete();
                        Notification::make()
                            ->title(__('messages.flash.item_deleted'))
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
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
