<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources;

use App\Filament\HospitalAdmin\Clusters\Pathology;
use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyCategoryResource\Pages;
use App\Models\PathologyCategory;
use App\Models\PathologyTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PathologyCategoryResource extends Resource
{
    protected static ?string $model = PathologyCategory::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Pathology::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Pathology Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Pathology Categories')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.pathology_categories');
    }

    public static function getLabel(): string
    {
        return __('messages.pathology_categories');
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
                    ->validationAttribute(__('messages.common.name'))
                    ->maxLength(160),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Pathology Categories')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', Auth::user()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->label(__('messages.common.name'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->successNotificationTitle(__('messages.flash.pathology_category_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (PathologyCategory $record) {
                        if (! canAccessRecord(PathologyCategory::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.pathology_category_not_found'))
                                ->send();
                        }

                        $pathologyCategoryModels = [
                            PathologyTest::class,
                        ];
                        $result = canDelete($pathologyCategoryModels, 'category_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.pathology_category_cant_deleted'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.pathology_category_deleted'))
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePathologyCategories::route('/'),
        ];
    }
}
