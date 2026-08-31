<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources;

use App\Filament\HospitalAdmin\Clusters\Inventory;
use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\IssuedItemResource\Pages;
use App\Models\IssuedItem;
use App\Models\Item;
use App\Models\User;
use App\Repositories\IssuedItemRepository;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IssuedItemResource extends Resource
{
    protected static ?string $model = IssuedItem::class;

    protected static ?string $cluster = Inventory::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Issued Items')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Issued Items')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.issued_item.issued_items');
    }

    public static function getLabel(): string
    {
        return __('messages.issued_item.issued_items');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Issued Items')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Issued Items')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Issued Items')) {
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
                        Select::make('department_id')
                            ->live()
                            ->label(__('messages.issued_item.department_id'))
                            ->relationship('department', 'name', fn ($query) => $query->where('name', '!=', 'Super Admin'))
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->afterStateUpdated(function ($set, $get) {
                                if ($get('department_id')) {
                                    $user = key(User::where('tenant_id', getLoggedInUser()->tenant_id)->where('department_id', $get('department_id'))->get()->pluck('full_name', 'id')->toArray());
                                    if (! empty($user)) {
                                        $set('user_id', $user);
                                    }
                                }
                            })
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.issued_item.department_id').' '.__('messages.fields.required'),
                            ]),
                        Select::make('user_id')
                            ->live()
                            ->label(__('messages.issued_item.user_id').':')
                            ->options(function ($get) {
                                if ($get('department_id')) {
                                    // return dd(User::where('tenant_id', getLoggedInUser()->tenant_id)->where('department_id', $get('department_id'))->get()->pluck('full_name', 'id')->toArray());
                                    return User::where('tenant_id', getLoggedInUser()->tenant_id)->where('department_id', $get('department_id'))->get()->pluck('full_name', 'id')->toArray();
                                }
                            })
                            ->disabled(function ($get) {
                                if ($get('department_id')) {
                                    $user = User::where('tenant_id', getLoggedInUser()->tenant_id)->where('department_id', $get('department_id'))->get()->pluck('full_name', 'id')->toArray();
                                    if (! empty($user)) {
                                        return false;
                                    }
                                }

                                return true;
                            })
                            ->placeholder(__('messages.message.select_user'))
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.issued_item.user_id').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('issued_by')
                            ->label(__('messages.issued_item.issued_by').':')
                            ->placeholder(__('messages.issued_item.issued_by'))
                            ->validationAttribute(__('messages.issued_item.issued_by'))
                            ->required(),
                        DatePicker::make('issued_date')
                            ->label(__('messages.issued_item.issued_date').':')
                            ->placeholder(__('messages.issued_item.issued_date'))
                            ->native(false)
                            ->live()
                            ->maxDate(now())
                            ->validationAttribute(__('messages.issued_item.issued_date'))
                            ->required(),
                        DatePicker::make('return_date')
                            ->label(__('messages.issued_item.return_date').':')
                            ->placeholder(__('messages.issued_item.return_date'))
                            ->minDate(fn ($get) => $get('issued_date') ?? now())
                            ->native(false)
                            ->validationAttribute(__('messages.issued_item.return_date'))
                            ->required(),
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
                                    return Item::where('item_category_id', $get('item_category_id'))->whereTenantId(getLoggedInUser()->tenant_id)->get()->pluck('name', 'id')->toArray();
                                }
                            })
                            // ->disabled(function ($get) {
                            //     if ($get('item_category_id')) {
                            //         $item = Item::where('item_category_id', $get('item_category_id'))->whereTenantId(getLoggedInUser()->tenant_id)->get()->pluck('id')->toArray();
                            //         if (!empty($item)) {
                            //             return false;
                            //         }
                            //     }
                            //     return true;
                            // })
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.item_stock.item').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('quantity')
                            ->label(function ($get) {
                                if ($get('item_id')) {
                                    $available_quantity = Item::where('item_category_id', $get('item_category_id'))->whereTenantId(getLoggedInUser()->tenant_id)->get()->pluck('available_quantity')->first() ?? null;

                                    return __('messages.issued_item.quantity').':'.' (Available Quantity: '.$available_quantity.')';
                                }

                                return __('messages.issued_item.quantity').':';
                            })
                            ->placeholder(__('messages.issued_item.quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(function ($get) {
                                if ($get('item_id')) {
                                    return Item::where('item_category_id', $get('item_category_id'))->get()->pluck('available_quantity')->first() ?? 0;
                                }

                                return 0;
                            })
                            ->readOnly(function ($get) {
                                if ($get('item_category_id')) {
                                    $item = Item::where('item_category_id', $get('item_category_id'))->get()->pluck('id')->toArray();
                                    if (! empty($item)) {
                                        return false;
                                    }
                                }

                                return true;
                            })
                            ->validationAttribute(__('messages.item_stock.item'))
                            ->required(),
                        Textarea::make('description')
                            ->label(__('messages.issued_item.description').':')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder(__('messages.issued_item.description')),
                    ])->columns(3),

            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Issued Items')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('item.name')
                        ->default(__('messages.common.n/a'))
                        ->sortable()
                        ->searchable()
                        ->label(__('messages.issued_item.item')),
                    TextColumn::make('item.itemcategory.name')
                        ->default(__('messages.common.n/a'))
                        ->sortable()
                        ->searchable()
                        ->searchable()
                        ->label(__('messages.issued_item.item_category')),
                    TextColumn::make('issued_date')
                        ->default(__('messages.common.n/a'))
                        ->sortable()
                        ->searchable()
                        ->badge()
                        ->label(__('messages.issued_item.issued_date'))
                        ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->issued_date)->isoFormat('Do MMM, Y') ?? __('messages.common.n/a')),
                    TextColumn::make('return_date')
                        ->default(__('messages.common.n/a'))
                        ->sortable()
                        ->searchable()
                        ->badge()
                        ->label(__('messages.issued_item.return_date'))
                        ->getStateUsing(fn ($record) => \Carbon\Carbon::parse($record->return_date)->isoFormat('Do MMM, Y') ?? __('messages.common.n/a')),
                    TextColumn::make('quantity')
                        ->default(__('messages.common.n/a'))
                        ->sortable()
                        ->searchable()
                        ->label(__('messages.issued_item.quantity'))
                        ->getStateUsing(fn ($record) => $record->quantity ?? __('messages.common.n/a'))
                        ->badge()
                        ->color('primary'),
                    TextColumn::make('status')
                        ->badge()
                        ->color(fn ($record) => $record->status == 0 ? 'info' : 'primary')
                        ->getStateUsing(fn ($record) => $record->status == 0 ? __('messages.issued_item.item_return') : __('messages.issued_item.item_returned'))
                        ->action(
                            \Filament\Tables\Actions\Action::make('status')
                                ->requiresConfirmation(fn ($record) => $record->status == 0)
                                ->modalSubheading(fn ($record) => $record->status == 0 ? __('messages.issued_item.are_you_sure_want_to_return_this_item').'?' : null)
                                ->modalButton(fn ($record) => $record->status == 0 ? __('messages.common.yes') : null)
                                ->modalIcon('fas-exclamation')
                                ->modalIconColor('warning')
                                ->action(function ($record) {
                                    $itemId = $record->id;
                                    if (! canAccessRecord(IssuedItem::class, $record->id)) {
                                        return Notification::make()
                                            ->danger()
                                            ->title(__('messages.flash.issued_item_not_found'))
                                            ->send();
                                    }

                                    $issuedItem = IssuedItem::whereId($itemId)->first();
                                    if ($issuedItem->status != IssuedItem::ITEM_RETURNED) {
                                        $newItemAvailableQty = $issuedItem->item->available_quantity + $issuedItem->quantity;
                                        $issuedItem->item()->update(['available_quantity' => $newItemAvailableQty]);
                                        $issuedItem->update(['return_date' => date('Y-m-d'), 'quantity' => 0, 'status' => IssuedItem::ITEM_RETURNED]);

                                        return Notification::make()
                                            ->success()
                                            ->title(__('messages.flash.item_returned'))
                                            ->send();
                                    }
                                })
                        ),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->label(__('messages.common.status'))
                        ->options([
                            '' => __('messages.filter.all'),
                            0 => __('messages.issued_item.item_return'),
                            1 => __('messages.issued_item.item_returned'),
                        ])
                        ->native(false),
                ])
                ->recordUrl(null)
                ->actions([
                    // Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                        $issuedItem = $record;
                        if (! canAccessRecord(IssuedItem::class, $issuedItem->id)) {
                            return Notification::make()
                                ->title(__('messages.flash.issued_item_not_found'))
                                ->danger()
                                ->send();
                        }

                        app(IssuedItemRepository::class)->destroyIssuedItemStock($issuedItem);

                        return Notification::make()
                            ->title(__('messages.flash.issued_item_deleted'))
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
            'index' => Pages\ListIssuedItems::route('/'),
            'create' => Pages\CreateIssuedItem::route('/create'),
            'edit' => Pages\EditIssuedItem::route('/{record}/edit'),
        ];
    }
}
