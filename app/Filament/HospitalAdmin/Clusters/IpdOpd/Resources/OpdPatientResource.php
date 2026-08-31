<?php

namespace App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\IpdOpd;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\CustomField;
use App\Models\DoctorOPDCharge;
use App\Models\OpdPatientDepartment;
use App\Models\Patient;
use App\Models\PatientCase;
use App\Models\User;
use App\Repositories\IpdPatientDepartmentRepository;
use App\Repositories\OpdPatientDepartmentRepository;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OpdPatientResource extends Resource
{
    protected static ?string $model = OpdPatientDepartment::class;

    // public function mount(): void
    // {
    //     $data = app(OpdPatientDepartmentRepository::class)->getAssociatedData();
    //     dd($data['revisit']);
    //     $data['revisit'] = ($request->get('revisit')) ? $request->get('revisit') : 0;
    //     $customField = CustomField::where('module_name', CustomField::OpdPatient)->get()->toArray();
    //     if ($data['revisit']) {
    //         $id = $data['revisit'];
    //         $data['last_visit'] = OpdPatientDepartment::findOrFail($id);
    //     }
    // }

    protected static ?string $cluster = IpdOpd::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Patient'])) {
            return true;
        } elseif (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('OPD Patients')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('OPD Patients')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.opd_patients');
    }

    public static function getLabel(): string
    {
        return __('messages.opd_patients');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse']) && getModuleAccess('OPD Patients')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse']) && getModuleAccess('OPD Patients')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse']) && getModuleAccess('OPD Patients')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        $customFields = CustomField::where('module_name', CustomField::OpdPatient)->Where('tenant_id', getLoggedInUser()->tenant_id)->get();

        $customFieldComponents = [];
        foreach ($customFields as $field) {
            $fieldType = CustomField::FIELD_TYPE_ARR[$field->field_type];
            $fieldName = 'field'.$field->id;
            $fieldLabel = $field->field_name;
            $isRequired = $field->is_required;
            $gridSpan = $field->grid;

            $customFieldComponents[] = match ($fieldType) {
                'text' => TextInput::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->placeholder($fieldLabel)
                    ->columnSpan($gridSpan),

                'textarea' => Textarea::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->placeholder($fieldLabel)
                    ->rows(4)
                    ->columnSpan($gridSpan),

                'toggle' => Toggle::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->columnSpan($gridSpan),

                'number' => TextInput::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->placeholder($fieldLabel)
                    ->numeric()
                    ->minValue(1)
                    ->columnSpan($gridSpan),

                'select' => Select::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->options(explode(',', $field->values))
                    ->placeholder($fieldLabel)
                    ->columnSpan($gridSpan)
                    ->native(false),

                'multiSelect' => MultiSelect::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->options(explode(',', $field->values))
                    ->placeholder($fieldLabel)
                    ->columnSpan($gridSpan),

                'date' => DatePicker::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->columnSpan($gridSpan),

                'date & Time' => DateTimePicker::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->columnSpan($gridSpan),

                default => TextInput::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->placeholder($fieldLabel)
                    ->columnSpan($gridSpan),
            };
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Group::make()->columns(6)->schema([
                            Select::make('patient_id')
                                ->label(__('messages.case.patient').':')
                                ->placeholder(__('messages.document.select_patient'))
                                ->required()
                                ->live()
                                ->options(function () {
                                    return app(IpdPatientDepartmentRepository::class)->getAssociatedData()['patients'];
                                })
                                ->getSearchResultsUsing(fn (string $search) => searchPatientSelectOptions($search))
                                ->afterStateUpdated(fn ($set, $get) => ! empty(PatientCase::where('patient_id', $get('patient_id'))->where('status', 1)->first()->id) ? $set('case_id', PatientCase::where('patient_id', $get('patient_id'))->where('status', 1)->first()->id) : $set('case_id', null))
                                ->searchable()
                                ->native(false)
                                ->preload()
                                ->optionsLimit(500)
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.case.patient').' '.__('messages.fields.required'),
                                ]),
                            Select::make('case_id')
                                ->live()
                                ->required()
                                ->options(function ($get) {
                                    if ($get('patient_id')) {
                                        return PatientCase::where('patient_id', $get('patient_id'))->where('status', 1)->get()->pluck('case_id', 'id')->toArray();                        // dd($case);
                                    }
                                })
                                ->placeholder(__('messages.case.select_case'))
                                // ->disabled(function (Get $get) {
                                //     $case = PatientCase::where('patient_id', $get('patient_id'))->where('status', 1)->get()->pluck('case_id', 'id')->toArray();
                                //     if (empty($case)) {
                                //         return true;
                                //     }
                                //     return false;
                                // })
                                ->label(__('messages.case.case').':')
                                ->native(false)
                                ->afterStateUpdated(function ($set, $state, $get) {
                                    $existsCaseId = OpdPatientDepartment::where('case_id', $state)->latest()->first();
                                    if ($existsCaseId && $existsCaseId->is_discharge == 0) {
                                        Notification::make()
                                            ->title(Patient::where('id', $get('patient_id'))->first()->patientUser->full_name.' '.__('messages.lunch_break.case_exist'))
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.case.case').' '.__('messages.fields.required'),
                                ]),
                            TextInput::make('opd_number')
                                ->label(__('messages.opd_patient.opd_number').':')
                                ->default(OpdPatientDepartment::generateUniqueOpdNumber())
                                ->maxLength(255)
                                ->readOnly(),
                            TextInput::make('height')
                                ->label(__('messages.ipd_patient.height').':')
                                ->placeholder(__('messages.ipd_patient.height'))
                                ->numeric()
                                ->minValue(1),
                            TextInput::make('weight')
                                ->label(__('messages.ipd_patient.weight').':')
                                ->maxLength(255)
                                ->placeholder(__('messages.ipd_patient.weight')),
                            TextInput::make('bp')
                                ->label(__('messages.ipd_patient.bp').':')
                                ->maxLength(255)
                                ->placeholder(__('messages.ipd_patient.bp')),
                        ]),
                        Group::make()->columns(4)->schema([
                            DateTimePicker::make('appointment_date')
                                ->label(__('messages.opd_patient.appointment_date').':')
                                ->native(false)
                                ->placeholder(__('messages.opd_patient.appointment_date'))
                                ->required()
                                ->validationAttribute(__('messages.opd_patient.appointment_date'))
                                ->default(now()),
                            Select::make('doctor_id')
                                ->label(__('messages.case.doctor').':')
                                ->placeholder(__('messages.web_home.select_doctor'))
                                ->required()
                                ->options(function () {
                                    $repo = app(OpdPatientDepartmentRepository::class);

                                    return $repo->getAssociatedData()['doctors'];
                                })
                                ->live()
                                ->searchable()
                                ->native(false)
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(
                                    function (Set $set, Get $get) {
                                        $doctorId = $get('doctor_id');
                                        if ($doctorId) {
                                            $opdDoctor = DoctorOPDCharge::where('doctor_id', $doctorId)->first();
                                            $set('standard_charge', $opdDoctor->standard_charge ?? 0);
                                        }
                                    }
                                )
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.case.doctor').' '.__('messages.fields.required'),
                                ]),
                            TextInput::make('standard_charge')
                                ->label(__('messages.doctor_opd_charge.standard_charge').':')
                                ->suffix(getCurrencySymbol())
                                ->placeholder(__('messages.doctor_opd_charge.standard_charge'))
                                ->maxLength(255)
                                ->numeric()
                                ->minValue(1)
                                ->live()
                                ->reactive()
                                ->validationAttribute(__('messages.doctor_opd_charge.standard_charge'))
                                ->required(),
                            Select::make('payment_mode')
                                ->label(__('messages.ipd_payments.payment_mode').':')
                                ->placeholder(__('messages.ipd_payments.select_payment_mode'))
                                ->options(OpdPatientDepartment::PAYMENT_MODES)
                                ->native(false)
                                ->preload()
                                ->searchable()
                                ->required()
                                ->validationMessages([
                                    'required' => __('messages.fields.the').' '.__('messages.ipd_payments.payment_mode').' '.__('messages.fields.required'),
                                ]),
                        ]),
                        Group::make()->columns(2)->schema([
                            Textarea::make('symptoms')
                                ->label(__('messages.ipd_patient.symptoms').':')
                                ->rows(4)
                                ->placeholder(__('messages.ipd_patient.symptoms'))
                                ->maxLength(255),
                            Textarea::make('notes')
                                ->label(__('messages.ipd_patient.notes').':')
                                ->rows(4)
                                ->placeholder(__('messages.ipd_patient.notes'))
                                ->maxLength(255),
                            Toggle::make('is_old_patient')
                                ->live()
                                ->label(__('messages.ipd_patient.is_old_patient').':'),
                        ]),
                        Section::make('')
                            ->schema($customFieldComponents)
                            ->columns(12)
                            ->visible(function () {
                                $customFields = CustomField::where('module_name', CustomField::OpdPatient)->Where('tenant_id', getLoggedInUser()->tenant_id)->get();
                                if ($customFields->count() == 0) {
                                    return false;
                                } else {
                                    return true;
                                }
                            }),
                    ])->columns(1),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse']) && ! getModuleAccess('OPD Patients')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->hasRole('Patient')) {
                    $query->whereTenantId(auth()->user()->tenant_id)->where('patient_id', auth()->user()->owner_id);
                }
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('opd_number')
                        ->label(__('messages.opd_patient.opd_number'))
                        ->searchable()
                        ->badge()
                        ->url(fn ($record) => OpdPatientResource::getUrl('view', ['record' => $record->id]))
                        ->color('info')
                        ->sortable(),
                    SpatieMediaLibraryImageColumn::make('patient.user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.patient'))->width(50)->height(50)
                        ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                        ->sortable(['first_name'])
                        ->defaultImageUrl(function ($record) {
                            if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->patient->user->first_name);
                            }
                        }),
                    TextColumn::make('patient.user.full_name')
                        ->label('')
                        ->description(function ($record) {
                        return displayPatientEmail($record->patient->user->email ?? null);
                    })
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->user->full_name.'</a>')
                        ->html()
                        ->searchable(['first_name', 'last_name', 'email']),
                    TextColumn::make('patient.patient_unique_id')
                        ->label(__('messages.lunch_break.patient_unique_id'))
                        ->default(__('messages.common.n/a'))
                        ->searchable()
                        ->toggleable(),
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
                    TextColumn::make('appointment_date')
                        ->label(__('messages.opd_patient.appointment_date'))
                        ->sortable(['appointment_date'])
                        ->view('tables.columns.hospitalAdmin.appointment_date')
                        ->searchable(['appointment_date']),
                    TextColumn::make('standard_charge')
                        ->label(__('messages.doctor_opd_charge.standard_charge'))
                        ->sortable()
                        ->getStateUsing(function ($record) {
                            return getCurrencyFormat($record->standard_charge);
                        }),
                    TextColumn::make('payment_mode')
                        ->sortable()
                        ->label(__('messages.ipd_payments.payment_mode'))
                        ->getStateUsing(function ($record) {
                            if ($record->payment_mode == 1) {
                                return __('messages.transaction_filter.cash');
                            } elseif ($record->payment_mode == 2) {
                                return __('messages.transaction_filter.cheque');
                            }
                        })
                        ->badge()
                        ->color(fn ($record) => $record->payment_mode == 1 ? 'primary' : 'success'),
                    TextColumn::make('id')
                        ->sortable()
                        ->label(__('messages.opd_patient.total_visits'))
                        ->getStateUsing(function ($record) {
                            return getLoggedinPatient() ? $record->opd_count : count($record->patient->opd);
                        })
                        ->badge()
                        ->color('info'),
                    TextColumn::make('is_discharge')
                        ->label(__('messages.common.status'))
                        ->badge()
                        ->getStateUsing(fn ($record) => $record->is_discharge ? __('messages.ipd_patient.discharged') : __('messages.filter.active'))
                        ->color(fn ($record) => $record->is_discharge ? 'success' : 'warning'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('is_discharge')
                        ->label(__('messages.common.status').':')
                        ->options([
                            '' => __('messages.filter.all'),
                            0 => __('messages.filter.active'),
                            1 => __('messages.ipd_patient.discharged'),
                        ])->native(false),
                ])
                ->recordUrl(null)
                ->actions([
                    Action::make('discharge')
                        ->label(__('messages.ipd_patient.discharged'))
                        ->icon('heroicon-o-arrow-right-start-on-rectangle')
                        ->iconButton()
                        ->color('success')
                        ->modalHeading(__('Discharge'))
                        ->modalWidth('lg')
                        ->form([
                            Section::make()
                                ->schema([
                                    Placeholder::make('status')
                                        ->label(__('messages.common.status').':')
                                        ->content(__('messages.filter.active')),
                                    DateTimePicker::make('discharge_date')
                                        ->label(__('messages.patient_admission.discharge_date').':')
                                        ->native(false)
                                        ->required()
                                        ->default(now()),
                                    Textarea::make('discharge_summary')
                                        ->label(__('Discharge Summary').':')
                                        ->rows(4)
                                        ->required(),
                                ]),
                        ])
                        ->fillForm(fn ($record) => [
                            'discharge_date' => $record->discharge_date ?? now(),
                            'discharge_summary' => $record->discharge_summary,
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'is_discharge' => true,
                                'discharge_date' => $data['discharge_date'],
                                'discharge_summary' => $data['discharge_summary'],
                            ]);

                            Notification::make()
                                ->title(__('OPD patient discharged successfully.'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ! $record->is_discharge && ! auth()->user()->hasRole('Patient')),
                    DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.OPD_Patient_deleted')),
                ])
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
            'index' => Pages\ListOpdPatients::route('/'),
            'create' => Pages\CreateOpdPatient::route('/create'),
            'view' => Pages\ViewOpdPatient::route('/{record}'),
            'edit' => Pages\EditOpdPatient::route('/{record}/edit'),
        ];
    }
}
