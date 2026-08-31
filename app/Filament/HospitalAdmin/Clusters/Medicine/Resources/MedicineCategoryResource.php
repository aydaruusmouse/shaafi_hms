<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources;

use App\Filament\HospitalAdmin\Clusters\Medicine;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineCategoryResource\Pages;
use App\Models\Category;
use App\Models\Medicine as ModelsMedicine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MedicineCategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $cluster = Medicine::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicine Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicine Categories')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.medicine_categories');
    }

    public static function getLabel(): string
    {
        return __('messages.medicine_categories');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicine Categories')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicine Categories')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician']) && getModuleAccess('Medicine Categories')) {
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
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.medicine.category').':')
                    ->placeholder(__('messages.medicine.category'))
                    ->required()
                    ->validationAttribute(__('messages.medicine.category'))
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label(__('messages.common.status').':')
                    ->default(1)
                    ->validationAttribute(__('messages.common.status'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Medicine Categories')) {
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
                    ->label(__('messages.user.name'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record): string => route('filament.hospitalAdmin.medicine.resources.medicine-categories.view', $record)),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label(__('messages.common.status'))
                    ->afterStateUpdated(fn () => Notification::make()->success()->title(__('messages.common.status_updated_successfully'))->send()),
            ])
            ->recordUrl(false)
            ->filters([
                SelectFilter::make('is_active')
                    ->options(Category::STATUS_ARR)
                    ->label(__('messages.common.status').':')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('xl')->successNotificationTitle(__('messages.flash.medicine_category_updated'))->modalHeading(__('messages.medicine.edit_medicine_category'))
                    ->action(function ($record, $data) {
                        $foundCategory = Category::where('name', $data['name'])->where('id', '!=', $record->id)->whereTenantId(getLoggedInUser()->tenant_id)->first();

                        if (isset($foundCategory)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('validation.unique', ['attribute' => __('messages.medicine.category')]))
                                ->send();
                        } else {
                            $record->update($data);

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.medicine_category_updated'))
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (Category $record) {
                        if (! canAccessRecord(Category::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.medicine_category_not_found'))
                                ->send();
                        }
                        $medicineCategoryModel = [
                            ModelsMedicine::class,
                        ];
                        $result = canDelete($medicineCategoryModel, 'category_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.medicine_category_cant_deleted'))
                                ->send();
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.medicine_category_deleted'))
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
            'index' => Pages\ManageMedicineCategories::route('/'),
            'view' => Pages\ViewMedicineCategory::route('/{record}'),
        ];
    }
}
