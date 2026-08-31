<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorDepartmentResource\Pages;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DoctorDepartmentResource extends Resource
{
    protected static ?string $model = DoctorDepartment::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Doctors::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Doctor Departments')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('DoDoctor Departments')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.doctor_department.doctor_department');
    }

    public static function getLabel(): string
    {
        return __('messages.doctor_department.doctor_department');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Doctor Departments')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Doctor Departments')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin') && getModuleAccess('Doctor Departments')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor'])) {
            return true;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('messages.doctor_department.title').':')
                    ->required()
                    ->validationAttribute(__('messages.doctor_department.title'))
                    ->placeholder(__('messages.doctor_department.title'))
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('messages.doctor_department.description').':')
                    ->placeholder(__('messages.doctor_department.description'))
                    ->rows(5)
                    ->maxLength(255),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Doctor Departments')) {
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
                    Tables\Columns\TextColumn::make('title')
                        ->label(__('messages.doctor_department.title'))
                        ->color('primary')
                        ->searchable()
                        ->sortable(),
                ])
                ->filters([
                    //
                ])
                ->actionsColumnLabel(__('messages.common.action'))
                ->actions([
                    Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                    Tables\Actions\EditAction::make()
                        ->iconButton()
                        ->modalWidth('md')
                        ->action(function ($record, array $data) {
                            $doctorDepartment = DoctorDepartment::findOrFail($record->id);

                            $doctorDepartment->update($data);

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.doctor_department_updated'))
                                ->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->iconButton()
                        ->action(function ($record) {
                            $doctorDepartment = DoctorDepartment::findOrFail($record->id);

                            if (! canAccessRecord(DoctorDepartment::class, $doctorDepartment->id)) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.doctor_department_not_found'))
                                    ->send();
                            }
                            $doctorDepartmentModels = [
                                Doctor::class,
                            ];
                            $result = canDelete($doctorDepartmentModels, 'doctor_department_id', $doctorDepartment->id);
                            if ($result) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.doctor_department_cant_deleted'))
                                    ->send();
                            }
                            $doctorDepartment->delete();

                            return Notification::make()
                                ->success()
                                ->title(__('messages.flash.doctor_department_deleted'))
                                ->send();
                        }),
                ])
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
            'view' => Pages\ViewDoctorDepartment::route('/{record}'),
            'index' => Pages\ManageDoctorDepartments::route('/'),
        ];
    }
}
