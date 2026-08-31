<?php

namespace App\Filament\HospitalAdmin\Clusters\Inventory\Resources;

use App\Filament\HospitalAdmin\Clusters\Inventory;
use App\Filament\HospitalAdmin\Clusters\Inventory\Resources\ItemsCategoryResource\Pages;
use App\Models\Item;
use App\Models\ItemCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ItemsCategoryResource extends Resource
{
    protected static ?string $model = ItemCategory::class;

    protected static ?string $cluster = Inventory::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Items Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Items Categories')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.items_categories');
    }

    public static function getLabel(): string
    {
        return __('messages.items_categories');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Items Categories')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Items Categories')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Items Categories')) {
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
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.item_category.name'))
                    ->required()
                    ->validationAttribute(__('messages.item_category.name')),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Items Categories')) {
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
                        ->sortable()
                        ->default(__('messages.item_category.name'))
                        ->label(__('messages.item_category.name')),
                ])
                ->filters([
                    //
                ])
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.item_category_updated')),
                    Tables\Actions\DeleteAction::make()
                        ->iconButton()
                        ->action(function (ItemCategory $record) {
                            if (! canAccessRecord(ItemCategory::class, $record->id)) {
                                return Notification::make()
                                    ->danger()
                                    ->title((__('messages.flash.item_category_not_found')))
                                    ->send();
                            }

                            $itemCategoryModel = [Item::class];
                            $result = canDelete($itemCategoryModel, 'item_category_id', $record->id);
                            if ($result) {
                                return Notification::make()
                                    ->danger()
                                    ->title((__('messages.flash.item_category_not_found')))
                                    ->send();
                            }
                            $record->delete();

                            return Notification::make()
                                ->success()
                                ->title((__('messages.flash.item_category_deleted')))
                                ->send();
                        })
                        ->successNotificationTitle(__('messages.flash.item_category_deleted')),
                ])
                ->recordAction(null)
                ->bulkActions([
                    // Tables\Actions\BulkActionGroup::make([
                    //     Tables\Actions\DeleteBulkAction::make(),
                    // ]),
                ])
                ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageItemsCategories::route('/'),
        ];
    }
}
