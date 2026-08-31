<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources;

use App\Filament\HospitalAdmin\Clusters\Diagnosis;
use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisResource\Pages;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource;
use App\Models\OpdDiagnosis;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DiagnosisResource extends Resource
{
    protected static ?string $model = OpdDiagnosis::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Diagnosis::class;

    public static function getNavigationLabel(): string
    {
        return __('messages.patient_diagnosis_test.diagnosis');
    }

    public static function getLabel(): ?string
    {
        return __('messages.patient_diagnosis_test.diagnosis');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Categories')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Categories')) {
            return false;
        }

        return true;
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
                Tables\Columns\TextColumn::make('report_type')
                    ->label(__('messages.ipd_patient_diagnosis.report_type'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('opdPatientDepartment.opd_number')
                    ->label(__('messages.opd_patient.opd_number'))
                    ->searchable()
                    ->badge()
                    ->url(fn ($record) => OpdPatientResource::getUrl('view', ['record' => $record->opdPatientDepartment->id]))
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->label(__('messages.ipd_patient_diagnosis.report_date'))
                    ->date()
                    ->getStateUsing(fn ($record) => $record->report_date ? Carbon::parse($record->report_date)->translatedFormat('jS M, Y') : __('messages.common.n/a'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document')
                    ->label(__('messages.ipd_patient_diagnosis.document'))
                    ->view('tables.columns.hospitalAdmin.in-diagnosis-document'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(20)
                    ->getStateUsing(fn ($record) => $record->description ?? __('messages.common.n/a'))
                    ->label(__('messages.common.description'))
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('report_generated')
                    ->label(__('messages.patient_diagnosis_test.report_generated'))
                    ->afterStateUpdated(function ($state, $record) {
                        if ($state == true) {
                            $record->report_generated = 1;
                            $record->save();
                            Notification::make()
                                ->success()
                                ->title(__('messages.flash.opd_diagnosis_report_generated'))
                                ->send();
                        } else {
                            $record->report_generated = 0;
                            $record->save();
                            Notification::make()
                                ->success()
                                ->title(__('messages.common.status_updated_successfully'))
                                ->send();
                        }
                    })
                    ->getStateUsing(fn ($record) => $record->report_generated == 0 ? '' : true)
                    ->sortable(),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->filters([
                //
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListDiagnoses::route('/'),
        ];
    }
}
