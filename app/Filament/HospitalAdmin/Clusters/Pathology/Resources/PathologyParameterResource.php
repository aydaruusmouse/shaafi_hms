<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources;

use App\Filament\HospitalAdmin\Clusters\Pathology;
use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyParameterResource\Pages;
use App\Models\PathologyParameter;
use App\Models\PathologyParameterItem;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PathologyParameterResource extends Resource
{
    protected static ?string $model = PathologyParameter::class;

    protected static ?string $cluster = Pathology::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Pathology Categories') && ! getModuleAccess('Pathology Tests')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Pathology Categories') && ! getModuleAccess('Pathology Tests')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.new_change.pathology_parameters');
    }

    public static function getLabel(): string
    {
        return __('messages.new_change.pathology_parameters');
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
                Forms\Components\TextInput::make('parameter_name')
                    ->required()
                    ->validationAttribute(__('messages.pathology_category.name'))
                    ->label(__('messages.pathology_category.name').':')
                    ->placeholder(__('messages.pathology_category.name'))
                    ->columnSpan(2),
                Forms\Components\TextInput::make('reference_range')
                    ->label(__('messages.new_change.reference_range').':')
                    ->placeholder(__('messages.new_change.reference_range'))
                    ->required()
                    ->validationAttribute(__('messages.new_change.reference_range'))
                    ->columnSpan(2),
                Select::make('unit_id')
                    ->relationship('pathologyUnit', 'name', fn (Builder $query) => $query->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'asc'))
                    ->label(__('messages.item.unit').':')
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpan(2)
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.item.unit').' '.__('messages.fields.required'),
                    ]),
                Textarea::make('description')->label(__('messages.common.description').':')->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('parameter_name')
                    ->label(__('messages.pathology_category.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference_range')
                    ->label(__('messages.new_change.reference_range'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pathologyUnit.name')
                    ->label(__('messages.item.unit'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.new_change.pathology_parameter').' '.__('messages.common.updated_successfully')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (PathologyParameter $record) {
                        if (! canAccessRecord(PathologyParameter::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.new_change.pathology_parameter_not_found'))
                                ->send();
                        }

                        $pathologyParameterModels = [
                            PathologyParameterItem::class,
                        ];
                        $result = canDelete($pathologyParameterModels, 'parameter_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.new_change.pathology_parameter_cant_deleted'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.new_change.pathology_parameter').' '.__('messages.common.deleted_successfully'))
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
            'index' => Pages\ManagePathologyParameters::route('/'),
        ];
    }
}
