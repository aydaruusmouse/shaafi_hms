<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources;

use App\Filament\HospitalAdmin\Clusters\Radiology;
use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyCategoryResource\Pages;
use App\Models\RadiologyCategory;
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

class RadiologyCategoryResource extends Resource
{
    protected static ?string $model = RadiologyCategory::class;

    protected static ?string $cluster = Radiology::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Radiology Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Radiology Categories')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.radiology_category.radiology_categories');
    }

    public static function getLabel(): string
    {
        return __('messages.radiology_category.radiology_categories');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->validationMessages([
                        'unique' => __('messages.user.name').' '.__('messages.common.is_already_exists'),
                    ])
                    ->validationAttribute(__('messages.user.name'))
                    ->label(__('messages.user.name').':')
                    ->maxLength(255),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Radiology Categories')) {
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
                    TextColumn::make('name')
                        ->sortable()
                        ->label(__('messages.common.name'))
                        ->searchable(),
                ])
                ->filters([
                    //
                ])
                ->actions([
                    Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.radiology_category_updated')),
                    Tables\Actions\DeleteAction::make()
                        ->iconButton()
                        ->action(function ($record) {
                            if (! canAccessRecord(RadiologyCategory::class, $record->id)) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.radiology_category_not_found'))
                                    ->send();
                            }
                            $record->delete();

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.radiology_category_deleted'))
                                ->send();
                        }),
                ])
                ->recordAction(null)
                ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ManageRadiologyCategories::route('/'),
        ];
    }
}
