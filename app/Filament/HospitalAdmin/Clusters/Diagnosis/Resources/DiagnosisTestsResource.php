<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources;

use App\Filament\HospitalAdmin\Clusters\Diagnosis;
use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\PatientDiagnosisProperty;
use App\Models\PatientDiagnosisTest;
use App\Models\User;
use App\Repositories\DoctorRepository;
use App\Repositories\PatientDiagnosisTestRepository;
use App\Repositories\PatientRepository;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DiagnosisTestsResource extends Resource
{
    protected static ?string $model = PatientDiagnosisTest::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Diagnosis::class;

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Tests')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Tests')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.patient_diagnosis_test.diagnosis_test');
    }

    public static function getLabel(): ?string
    {
        return __('messages.patient_diagnosis_test.diagnosis_test');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician']) && getModuleAccess('Diagnosis Tests')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician']) && getModuleAccess('Diagnosis Tests')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician']) && getModuleAccess('Diagnosis Tests')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Lab Technician', 'Patient'])) {
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
                        Forms\Components\Select::make('patient_id')
                            ->required()
                            ->options(fn () => app(PatientRepository::class)->getPatients())
                            ->label(__('messages.patient_diagnosis_test.patient').':')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.patient_diagnosis_test.patient'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.patient_diagnosis_test.patient').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('doctor_id')
                            ->required()
                            ->hidden(auth()->user()->hasRole('Doctor'))
                            ->options(fn () => app(DoctorRepository::class)->getDoctors())
                            ->label(__('messages.doctor_opd_charge.doctor').':')
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->placeholder(__('messages.doctor_opd_charge.doctor'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.doctor_opd_charge.doctor').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('category_id')
                            ->label(__('messages.patient_diagnosis_test.diagnosis_category').':')
                            ->options(fn () => app(PatientDiagnosisTestRepository::class)->getDiagnosisCategory())
                            ->placeholder(__('messages.patient_diagnosis_test.diagnosis_category'))
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.patient_diagnosis_test.diagnosis_category').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('report_number')
                            ->label(__('messages.patient_diagnosis_test.report_number').':')
                            ->required()
                            ->validationAttribute(__('messages.patient_diagnosis_test.report_number'))
                            ->default(patientDiagnosisTestRepository::getUniqueReportNumber())
                            ->readOnly()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('age')
                            ->label(__('messages.patient_diagnosis_test.age').':')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('messages.patient_diagnosis_test.age'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('height')
                            ->label(__('messages.patient_diagnosis_test.height').':')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('messages.patient_diagnosis_test.height'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('weight')
                            ->label(__('messages.patient_diagnosis_test.weight').':')
                            ->placeholder(__('messages.patient_diagnosis_test.weight'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('average_glucose')
                            ->label(__('messages.patient_diagnosis_test.average_glucose').':')
                            ->placeholder(__('messages.patient_diagnosis_test.average_glucose'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('fasting_blood_sugar')
                            ->label(__('messages.patient_diagnosis_test.fasting_blood_sugar').':')
                            ->placeholder(__('messages.patient_diagnosis_test.fasting_blood_sugar'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('urine_sugar')
                            ->label(__('messages.patient_diagnosis_test.urine_sugar').':')
                            ->placeholder(__('messages.patient_diagnosis_test.urine_sugar'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('blood_pressure')
                            ->label(__('messages.patient_diagnosis_test.blood_pressure').':')
                            ->placeholder(__('messages.patient_diagnosis_test.blood_pressure'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('diabetes')
                            ->label(__('messages.patient_diagnosis_test.diabetes').':')
                            ->placeholder(__('messages.patient_diagnosis_test.diabetes'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('cholesterol')
                            ->label(__('messages.patient_diagnosis_test.cholesterol').':')
                            ->placeholder(__('messages.patient_diagnosis_test.cholesterol'))
                            ->maxLength(191),
                    ])->columns(4),

                Repeater::make('add_other_diagnosis_property')
                    ->label(__('messages.patient_diagnosis_test.add_other_diagnosis_property'))
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('property_name')
                            // ->relationship('patientDiagnosisProperties', 'property_name')
                            ->label(__('messages.patient_diagnosis_test.diagnosis_property_name').':')
                            ->placeholder(__('messages.patient_diagnosis_test.diagnosis_property_name'))
                            ->maxLength(191),
                        Forms\Components\TextInput::make('property_value')
                            // ->relationship('patientDiagnosisProperties', 'property_value')
                            ->label(__('messages.patient_diagnosis_test.diagnosis_property_value').':')
                            ->placeholder(__('messages.patient_diagnosis_test.diagnosis_property_value'))
                            ->maxLength(191),
                    ])->columns(2),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Receptionist', 'Lab Technician', 'Patient']) && ! getModuleAccess('Diagnosis Tests')) {
            abort(404);
        } elseif (auth()->user()->hasRole('Admin') && ! getModuleAccess('Diagnosis Tests')) {
            abort(404);
        }

        return $table = $table->modifyQueryUsing(function ($query) {
            $table = $query->where('tenant_id', getLoggedInUser()->tenant_id);
            $user = Auth::user();
            if ($user->hasRole('Patient')) {
                $query->where('patient_id', $user->owner_id);
            }
            if ($user->hasRole('Doctor')) {
                $query->where('doctor_id', $user->owner_id);
            }

            return $table;
        })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('report_number')
                    ->label(__('messages.patient_diagnosis_test.report_number'))
                    ->sortable()
                    ->url(fn ($record) => DiagnosisTestsResource::getUrl('view', ['record' => $record->id]))
                    ->badge()
                    ->color('info')
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.patient'))->width(50)->height(50)
                    ->url(fn ($record) => ! Auth::user()->hasRole('Lab Technician') ? PatientResource::getUrl('view', ['record' => $record->patient->id]) : '')
                    ->sortable(['first_name'])
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->patientUser->first_name);
                        }
                    }),
                TextColumn::make('patient.user.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => ! Auth::user()->hasRole('Lab Technician') ? "<a href='".PatientResource::getUrl('view', ['record' => $record->patient->id])."' class='hoverLink'>".$record->patient->patientUser->full_name.'</a>' : $record->patient->patientUser->full_name)
                    ->html()
                    ->description(function ($record) {
                        return $record->patient->patientUser->email;
                    })
                    ->searchable(['first_name', 'last_name', 'email']),
                SpatieMediaLibraryImageColumn::make('doctor.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.doctor'))->width(50)->height(50)
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->sortable(['first_name'])
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->first_name);
                        }
                    }),
                TextColumn::make('doctor.user.full_name')
                    ->label('')
                    ->description(function ($record) {
                        return $record->doctor->user->email;
                    })
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->user->full_name.'</a>')
                    ->html()
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('category.name')
                    ->label(__('messages.patient_diagnosis_test.diagnosis_category'))
                    ->color('primary')
                    ->html()
                    ->formatStateUsing(fn ($record) => '<a href="'.DiagnosisCategoriesResource::getUrl('view', ['record' => $record->category->id]).'" class="hoverLink">'.$record->category->name.'</a>')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    // resources/views/tables/columns/hospitalAdmin/createdAt.blade.php
                    ->getStateUsing(function ($record) {
                        return $record->created_at->translatedFormat('jS M, Y');
                    })->label(__('messages.common.created_at'))
                    ->badge()
                    ->searchable()
                    ->sortable(),

            ])
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\PrintAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (PatientDiagnosisTest $record) {
                        if (! canAccessRecord(PatientDiagnosisTest::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.diagnosis_test_not_found'))
                                ->send();
                        }

                        PatientDiagnosisProperty::wherePatientDiagnosisId($record->id)->delete();
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.patient_diagnosis_deleted'))
                            ->send();
                    })
                    ->successNotificationTitle(__('messages.patient_diagnosis_test.patient_diagnosis_test').' '.__('messages.common.has_been_deleted')),
            ])
            ->actionsColumnLabel((auth()->user()->hasRole('Patient')) ? '' : __('messages.common.action'))
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
            'index' => Pages\ListDiagnosisTests::route('/'),
            'create' => Pages\CreateDiagnosisTests::route('/create'),
            'view' => Pages\ViewDiagnosisTests::route('/{record}'),
            'edit' => Pages\EditDiagnosisTests::route('/{record}/edit'),
        ];
    }
}
