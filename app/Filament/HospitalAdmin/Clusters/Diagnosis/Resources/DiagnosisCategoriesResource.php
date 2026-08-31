<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources;

use App\Filament\HospitalAdmin\Clusters\Diagnosis;
use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisCategoriesResource\Pages;
use App\Models\DiagnosisCategory;
use App\Models\PatientDiagnosisTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DiagnosisCategoriesResource extends Resource
{
    protected static ?string $model = DiagnosisCategory::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Diagnosis::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Categories')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.diagnosis_category.diagnosis_categories');
    }

    public static function getLabel(): ?string
    {
        return __('messages.diagnosis_category.diagnosis_categories');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician']) && getModuleAccess('Diagnosis Categories')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician']) && getModuleAccess('Diagnosis Categories')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician']) && getModuleAccess('Diagnosis Categories')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('messages.diagnosis_category.diagnosis_category'))
                    ->required()
                    ->validationAttribute(__('messages.diagnosis_category.diagnosis_category'))
                    ->maxLength(191),
                Forms\Components\Textarea::make('description')
                    ->label(__('messages.diagnosis_category.description'))
                    ->rows(4)
                    ->maxLength(255),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Receptionist', 'Lab Technician']) && ! getModuleAccess('Diagnosis Categories')) {
            abort(404);
        } elseif (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Categories')) {
            abort(404);
        }

        return $table = $table->modifyQueryUsing(function ($query) {
            $table = $query->where('tenant_id', getLoggedInUser()->tenant_id);

            return $table;
        })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('messages.diagnosis_category.diagnosis_category'))
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modal()->modalWidth('md')->successNotificationTitle(__('messages.flash.diagnosis_category_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (DiagnosisCategory $record) {
                        if (! canAccessRecord(DiagnosisCategory::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.diagnosis_category_not_found'))
                                ->send();
                        }
                        $diagnosisCategoryModal = [
                            PatientDiagnosisTest::class,
                        ];
                        $result = canDelete($diagnosisCategoryModal, 'category_id', $record->id);
                        if ($result) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.diagnosis_category_cant_deleted'))
                                ->send();
                        }

                        $record->delete();

                        return Notification::make()
                            ->danger()
                            ->title(__('messages.flash.diagnosis_category_deleted'))
                            ->send();
                    }),
            ])
            ->recordAction(null)
            ->recordUrl(null)
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
            'view' => Pages\ViewDiagnosisCategory::route('/{record}'),
            'index' => Pages\ManageDiagnosisCategories::route('/'),
        ];
    }
}
