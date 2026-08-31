<?php

namespace App\Filament\HospitalAdmin\Clusters\Prescription\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Prescription;
use App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource\Pages;
use App\Models\Doctor;
use App\Models\Medicine as ModelsMedicine;
use App\Models\MedicineBill;
use App\Models\Prescription as ModalPrescription;
use App\Models\Prescription as ModelsPrescription;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\PrescriptionRepository;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class PrescriptionResource extends Resource
{
    protected static ?string $model = ModelsPrescription::class;

    protected static ?string $cluster = Prescription::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Prescriptions')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Prescriptions')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.prescriptions');
    }

    public static function getLabel(): string
    {
        return __('messages.prescriptions');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Prescriptions')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Prescriptions')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Prescriptions')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Doctor') && ! getModuleAccess('Prescriptions')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Pharmacist', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('messages.prescription.prescription_details'))  // You can provide a title for the section
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Select::make('patient_id')
                                    ->label(__('messages.document.patient').': ')
                                    ->placeholder(__('messages.document.select_patient'))
                                    ->options(function () {
                                        $repo = app(PrescriptionRepository::class);

                                        return $repo->getPatients();
                                    })
                                    ->searchable()
                                    ->native(false)
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('messages.fields.the').' '.__('messages.document.patient').' '.__('messages.fields.required'),
                                    ]),

                                Auth::user()->hasRole('Doctor')
                                    ? Forms\Components\Hidden::make('doctor_id')
                                        ->default(Auth::user()->owner_id)
                                    : Select::make('doctor_id')
                                        ->label(__('messages.case.doctor').':')
                                        ->options(Doctor::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                                        ->required()
                                        ->placeholder(__('messages.web_home.select_doctor'))
                                        ->native(false)
                                        ->validationMessages([
                                            'required' => __('messages.fields.the').' '.__('messages.case.doctor').' '.__('messages.fields.required'),
                                        ]),

                                TextInput::make('health_insurance')
                                    ->label(__('messages.prescription.health_insurance').':')
                                    ->placeholder(__('messages.prescription.health_insurance')),

                                TextInput::make('low_income')
                                    ->label(__('messages.prescription.low_income').':')
                                    ->placeholder(__('messages.prescription.low_income')),

                                TextInput::make('reference')
                                    ->label(__('messages.prescription.reference').':')
                                    ->placeholder(__('messages.prescription.reference')),

                                Toggle::make('status')
                                    ->label(__('messages.common.status').':')
                                    ->default(true)
                                    ->inlineLabel()
                                    ->extraAttributes(['class' => getLoggedInUser()->language == 'ar' ? 'float-end' : '']),
                            ]),
                    ]),
                Section::make(__('messages.medicine.medicine'))
                    ->headerActions([
                        Action::make(__('messages.open_ai.suggest_medicines'))
                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                if (
                                    ! $get('high_blood_pressure') && ! $get('food_allergies') &&
                                    ! $get('tendency_bleed') && ! $get('heart_disease') && ! $get('diabetic') &&
                                    ! $get('female_pregnancy') && ! $get('breast_feeding') && ! $get('current_medication') &&
                                    ! $get('surgery') && ! $get('accident') &&
                                    ! $get('plus_rate') && ! $get('temperature') && ! $get('problem_description')
                                ) {
                                    return Notification::make()
                                        ->danger()
                                        ->title(__('messages.prescription.fill_physical_information'))
                                        ->send();
                                }
                                $openAiSetting = Setting::where('key', 'open_ai_enable')
                                    ->where('tenant_id', auth()->user()->tenant_id)
                                    ->first();
                                $openAiKey = null;

                                if ($openAiSetting && $openAiSetting->value == 1) {
                                    $openAiKeySetting = Setting::where('key', 'open_ai_key')->first();
                                    if ($openAiKeySetting) {
                                        $openAiKey = $openAiKeySetting->value;
                                    }
                                }

                                if (empty($openAiKey)) {
                                    $openAiKey = config('services.open_ai.open_api_key');
                                }

                                if (empty($openAiKey)) {
                                    return Notification::make()
                                        ->danger()
                                        ->title(__('messages.open_ai.open_ai_key_not_found'))
                                        ->send();
                                }

                                $data = [
                                    'high_blood_pressure',
                                    'food_allergies',
                                    'tendency_bleed',
                                    'heart_disease',
                                    'diabetic',
                                    'female_pregnancy',
                                    'breast_feeding',
                                    'current_medication',
                                    'surgery',
                                    'accident',
                                    'plus_rate',
                                    'temperature',
                                    'problem_description',
                                ];

                                $patientInfo = "Patient Details:\n";
                                foreach ($data as $key) {
                                    $value = $get($key);
                                    if (! empty($value)) {
                                        $patientInfo .= '- '.ucwords(str_replace('_', ' ', $key)).': '.$value."\n";
                                    }
                                }

                                $prompt = <<<PROMPT
                                        $patientInfo

                                        Prescription Request:

                                        1. Dose Duration: Choose one:
                                            - Only one day
                                            - Up to Three days
                                            - Up to One week
                                            - Up to two weeks
                                            - Up to one month

                                        2. Dose Interval: Choose one:
                                            - Daily morning
                                            - Daily morning and evening
                                            - Daily morning, noon, and evening
                                            - 4 times a day

                                        3. Time: Choose one:
                                            - After Meal
                                            - Before Meal

                                        Please provide the prescription details for multiple medicines in JSON format, choosing from the options provided. Ensure to include at least 3 or more medicine entries. Use the format below:

                                        {
                                            "medicines": [
                                                {
                                                    "Real Medicine Name": "Provide real Medicine name",
                                                    "Dosage": "Provide Dosage count in only number",
                                                    "Dose Duration": "Choose from the options from Dose Duration",
                                                    "Dose Interval": "Choose from the options from Dose Interval",
                                                    "Time": "Choose from the options from Time",
                                                    "Comment": "Please give guidance"
                                                },
                                                ...
                                            ]
                                        }
                                    PROMPT;

                                try {
                                    $response = Http::withToken($openAiKey)
                                        ->withHeaders(['Content-Type' => 'application/json'])
                                        ->post('https://api.openai.com/v1/chat/completions', [
                                            'model' => 'gpt-3.5-turbo',
                                            'messages' => [['role' => 'user', 'content' => $prompt]],
                                        ]);
                                    $responseData = $response->json();

                                    if (isset($responseData['error'])) {
                                        return Notification::make()
                                            ->danger()
                                            ->title($responseData['error']['message'])
                                            ->send();
                                    }

                                    $prescriptionData = $responseData['choices'][0]['message']['content'];

                                    $set('prescription_data', $prescriptionData);

                                    return Notification::make()
                                        ->success()
                                        ->title(__('messages.prescription.data_retrive_from_openai'))
                                        ->send();
                                } catch (\Exception $e) {
                                    return Notification::make()
                                        ->danger()
                                        ->title($e->getMessage())
                                        ->send();
                                }
                            }),

                        Action::make('showPrescriptionModal')
                            ->label(__('messages.common.show').' '.__('messages.open_ai.suggested_medicines'))
                            ->modalHeading(__('messages.open_ai.suggested_medicines'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('messages.common.close'))
                            ->infolist(function (Forms\Get $get) {
                                $prescriptionData = $get('prescription_data');

                                $decodedData = json_decode($prescriptionData, true);

                                if (! $decodedData || ! isset($decodedData['medicines'])) {
                                    return Infolist::make()->state([])->schema([]);
                                }

                                $medicinesSchema = [];
                                foreach ($decodedData['medicines'] as $index => $medicine) {
                                    $medicinesSchema[] = ComponentsSection::make()
                                        ->schema([
                                            Grid::make(3)
                                                ->schema([
                                                    TextEntry::make("real_medicine_name_{$index}")
                                                        ->label(__('messages.prescription.medicine_name').':')
                                                        ->state($medicine['Real Medicine Name']),
                                                    TextEntry::make("dosage_{$index}")
                                                        ->label(__('messages.ipd_patient_prescription.dosage').':')
                                                        ->state($medicine['Dosage']),
                                                    TextEntry::make("dose_duration_{$index}")
                                                        ->label(__('messages.purchase_medicine.dose_duration').':')
                                                        ->state($medicine['Dose Duration']),

                                                ]),
                                            Grid::make(3)
                                                ->schema([
                                                    TextEntry::make("dose_interval_{$index}")
                                                        ->label(__('messages.medicine_bills.dose_interval').':')
                                                        ->state($medicine['Dose Interval']),
                                                    TextEntry::make("time_{$index}")
                                                        ->label(__('messages.appointment.time').':')
                                                        ->state($medicine['Time']),
                                                    TextEntry::make("comment_{$index}")
                                                        ->label(__('messages.prescription.comment').':')
                                                        ->state($medicine['Comment']),
                                                ]),
                                        ])
                                        ->columnSpan(2);
                                }

                                return Infolist::make()
                                    ->state([])
                                    ->schema($medicinesSchema)
                                    ->columns(1);
                            })
                            ->hidden(fn (Forms\Get $get) => empty($get('prescription_data'))),
                    ])
                    ->schema([
                        Forms\Components\Grid::make('full')
                            ->schema([
                                static::getItemsRepeater(),
                            ])->columnSpan('full'),
                    ]),
                Section::make(__('messages.prescription.physical_information'))

                    ->schema([

                        Forms\Components\Grid::make(4)
                            ->schema([
                                TextInput::make('high_blood_pressure')
                                    ->label(__('messages.prescription.high_blood_pressure').':')
                                    ->placeholder(__('messages.prescription.high_blood_pressure')),

                                TextInput::make('food_allergies')
                                    ->label(__('messages.prescription.food_allergies').':')
                                    ->placeholder(__('messages.prescription.food_allergies')),

                                TextInput::make('tendency_bleed')
                                    ->label(__('messages.prescription.tendency_bleed').':')
                                    ->placeholder(__('messages.prescription.tendency_bleed')),

                                TextInput::make('heart_disease')
                                    ->label(__('messages.prescription.heart_disease').':')
                                    ->placeholder(__('messages.prescription.heart_disease')),

                                TextInput::make('diabetic')
                                    ->label(__('messages.prescription.diabetic').':')
                                    ->placeholder(__('messages.prescription.diabetic')),

                                DatePicker::make('medical_history')
                                    ->native(false)
                                    ->label(__('messages.new_change.added_at').':')
                                    ->placeholder(__('messages.new_change.added_at'))
                                    ->extraInputAttributes([
                                        'class' => getLoggedInUser()->thememode ? 'bg-light' : 'bg-white',
                                        'autocomplete' => 'off',
                                    ]),

                                TextInput::make('female_pregnancy')
                                    ->label(__('messages.prescription.female_pregnancy').':')
                                    ->placeholder(__('messages.prescription.female_pregnancy')),

                                TextInput::make('breast_feeding')
                                    ->label(__('messages.prescription.breast_feeding').':')
                                    ->placeholder(__('messages.prescription.breast_feeding')),

                                TextInput::make('current_medication')
                                    ->label(__('messages.prescription.current_medication').':')
                                    ->placeholder(__('messages.prescription.current_medication')),

                                TextInput::make('surgery')
                                    ->label(__('messages.prescription.surgery').':')
                                    ->placeholder(__('messages.prescription.surgery')),

                                TextInput::make('accident')
                                    ->label(__('messages.prescription.accident'))
                                    ->placeholder(__('messages.prescription.accident')),

                                TextInput::make('others')
                                    ->label(__('messages.prescription.others'))
                                    ->placeholder(__('messages.prescription.others')),
                                TextInput::make('plus_rate')
                                    ->label(__('messages.prescription.plus_rate'))
                                    ->placeholder(__('messages.prescription.plus_rate')),

                                TextInput::make('temperature')
                                    ->label(__('messages.prescription.temperature'))
                                    ->placeholder(__('messages.prescription.temperature')),

                                Textarea::make('problem_description')
                                    ->label(__('messages.prescription.problem_description'))
                                    ->placeholder(__('messages.prescription.problem_description'))
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make('')  // You can provide a title for the section
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('test')
                                    ->label(__('messages.prescription.test'))
                                    ->placeholder(__('messages.prescription.test'))
                                    ->rows(4),

                                Forms\Components\Textarea::make('advice')
                                    ->label(__('messages.prescription.advice'))
                                    ->placeholder(__('messages.prescription.advice'))
                                    ->rows(4),

                                Forms\Components\Fieldset::make(__('messages.prescription.next_visit'))
                                    ->schema([
                                        Forms\Components\TextInput::make('next_visit_qty')
                                            ->type('number')
                                            ->placeholder('1')
                                            ->minValue(1),

                                        Forms\Components\Select::make('next_visit_time')
                                            ->options(\App\Models\Prescription::TIME_ARR)
                                            ->native(false),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Doctor', 'Pharmacist', 'Patient']) && ! getModuleAccess('Prescriptions')) {
            abort(404);
        } elseif (auth()->user()->hasRole('Admin') && ! getModuleAccess('Prescriptions')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function (Builder $query) {
            if (! getLoggedinDoctor()) {
                if (getLoggedinPatient()) {
                    $query = ModalPrescription::whereHas('patient', function (Builder $query) {
                        $query->where('patient_id', getLoggedInUser()->owner_id);
                    })->where('tenant_id', getLoggedInUser()->tenant_id);
                } else {
                    $query = ModalPrescription::select('prescriptions.*')->with('patient', 'doctor')->where('tenant_id', getLoggedInUser()->tenant_id);
                }
            } else {
                $doctorId = Doctor::where('user_id', getLoggedInUserId())->first();
                $query = ModalPrescription::select('prescriptions.*')->with('patient', 'doctor')->where(
                    'doctor_id',
                    $doctorId->id
                )->where('tenant_id', getLoggedInUser()->tenant_id);
            }

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.prescription.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['users.first_name', 'users.last_name']),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.prescription.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->doctorUser->full_name.'</a>')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('medical_history')
                    ->label(__('messages.new_change.added_at'))
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        try {
                            if ($record->medical_history) {
                                return Carbon::parse($record->medical_history)->translatedFormat('jS M, Y');
                            } else {
                                return __('messages.common.n/a');
                            }
                        } catch (\Exception $e) {
                            return __('messages.common.n/a');
                        }
                    }),
                Tables\Columns\ToggleColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->hidden(! Auth::user()->hasRole('Admin'))
                    ->updateStateUsing(function (ModelsPrescription $prescription, bool $state) {
                        $state ? $prescription->status = 1 : $prescription->status = 0;
                        $prescription->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()
                    ->visible(function ($record) {
                        $medicineBill = MedicineBill::whereModelType(\App\Models\Prescription::class)->whereModelId($record->id)->first();
                        if (isset($medicineBill->payment_status) && $medicineBill->payment_status == false) {
                            return true;
                        }

                        return false;
                    }),
                TableAction::make('pdf')
                    ->iconButton()
                    ->hidden(! Auth::user()->hasRole('Admin'))
                    ->icon('heroicon-s-printer')
                    ->color('warning')
                    ->url(function ($record) {
                        return route('prescriptions.pdf', $record->id);
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (ModelsPrescription $record) {
                        if (! canAccessRecord(ModelsPrescription::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.prescription_not_found'))
                                ->send();
                        }

                        if (getLoggedInUser()->hasRole('Doctor')) {
                            $patientPrescriptionHasDoctor = Prescription::whereId($record->id)->whereDoctorId(getLoggedInUser()->owner_id)->exists();
                            if (! $patientPrescriptionHasDoctor) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.prescription_not_found'))
                                    ->send();
                            }
                        }
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.prescription_deleted'))
                            ->send();
                    }),
            ])
            ->recordAction(null)
            ->recordUrl(false)

            ->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                //
            ])
            ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListPrescriptions::route('/'),
            'create' => Pages\CreatePrescription::route('/create'),
            'view' => Pages\ViewPrescription::route('/{record}'),
            'edit' => Pages\EditPrescription::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('getMedicine')
            ->relationship('getMedicine')
            ->label('')
            ->schema([
                Forms\Components\Grid::make(6)
                    ->schema([
                        Forms\Components\Select::make('medicine')
                            ->label(__('messages.medicine.medicine').':')
                            ->placeholder(__('messages.medicine_bills.select_medicine'))
                            ->required()
                            ->options(ModelsMedicine::where('tenant_id', getLoggedInUser()->tenant_id)
                                ->get()->pluck('name', 'id')->toArray())
                            ->native(false),

                        Forms\Components\TextInput::make('dosage')
                            ->label(__('messages.ipd_patient_prescription.dosage').':')
                            ->required()
                            ->placeholder(__('messages.ipd_patient_prescription.dosage')),

                        Select::make('day')
                            ->required()
                            ->label(__('messages.prescription.duration').':')
                            ->options(ModelsPrescription::DOSE_DURATION)
                            ->native(false),

                        Select::make('time')
                            ->required()
                            ->label(__('messages.prescription.time').':')
                            ->options(ModelsPrescription::MEAL_ARR)
                            ->native(false),

                        Select::make('dose_interval')
                            ->required()
                            ->label(__('messages.medicine_bills.dose_interval').':')
                            ->options(ModelsPrescription::DOSE_INTERVAL)
                            ->native(false),
                        Textarea::make('comment')
                            ->label(__('messages.prescription.comment').':')
                            ->placeholder(__('messages.prescription.comment'))
                            ->rows(1),
                    ]),
            ])
            ->deletable(function ($state) {
                if (count($state) === 1) {
                    return false;
                }

                return true;
            })
            ->dehydrated();
    }
}
