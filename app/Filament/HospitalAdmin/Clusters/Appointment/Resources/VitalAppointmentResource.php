<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources;

use App\Filament\HospitalAdmin\Clusters\Appointment;
use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\VitalAppointmentResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Appointment as AppointmentModel;
use App\Models\Doctor;
use App\Models\PatientQueue;
use App\Models\User;
use App\Models\VitalSign;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VitalAppointmentResource extends Resource
{
    protected static ?string $model = AppointmentModel::class;

    protected static ?string $cluster = Appointment::class;

    protected static ?string $slug = 'vital-signs';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('messages.vital_signs');
    }

    public static function getLabel(): string
    {
        return __('messages.vital_signs');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Patient'])) {
            return false;
        }

        return auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist', 'Nurse'])
            && (getModuleAccess('Appointments') || getModuleAccess('Vital Signs'));
    }

    public static function canViewAny(): bool
    {
        return self::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['patient.patientUser.media', 'doctor.doctorUser.media', 'vitalSigns'])
                    ->where('tenant_id', auth()->user()->tenant_id);

                if (! getLoggedinDoctor()) {
                    if (getLoggedinPatient()) {
                        $patient = Auth::user();
                        $query->whereHas('patient', function (Builder $query) use ($patient) {
                            $query->where('user_id', '=', $patient->id);
                        });
                    }
                } else {
                    $doctorId = Doctor::where('user_id', getLoggedInUserId())->first();
                    if ($doctorId) {
                        $query->where('doctor_id', $doctorId->id);
                    }
                }
            })
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.role.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.user.full_name')
                    ->label(__('messages.role.patient'))
                    ->color('primary')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label(__('messages.role.doctor'))
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(function ($record) {
                        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist'])) {
                            return '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->doctorUser->full_name.'</a>';
                        }

                        return $record->doctor->doctorUser->full_name;
                    })
                    ->html()
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('opd_date')
                    ->label(__('messages.appointment.date'))
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('M j, Y g:i A'))
                    ->sortable(),
                TextColumn::make('vitalSigns.blood_pressure')
                    ->label(__('messages.appointment.blood_pressure'))
                    ->placeholder(__('messages.common.n/a')),
                TextColumn::make('vitalSigns.pulse_rate')
                    ->label(__('messages.appointment.pulse_rate'))
                    ->placeholder(__('messages.common.n/a')),
                TextColumn::make('vitalSigns.temperature')
                    ->label(__('messages.appointment.temperature'))
                    ->placeholder(__('messages.common.n/a')),
                TextColumn::make('is_completed')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return AppointmentModel::STATUS_ARR[(string) $state] ?? __('messages.common.n/a');
                    })
                    ->color(fn ($state) => match ((int) $state) {
                        AppointmentModel::STATUS_IN_VITAL => 'warning',
                        AppointmentModel::STATUS_IN_QUEUE => 'info',
                        AppointmentModel::STATUS_CHECK_IN => 'success',
                        AppointmentModel::STATUS_COMPLETED => 'success',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('record_vitals')
                    ->label(fn ($record) => $record->vitalSigns ? __('messages.appointment.edit_vitals') : __('messages.appointment.record_vitals'))
                    ->icon('heroicon-o-clipboard-document')
                    ->color(fn ($record) => $record->vitalSigns ? 'warning' : 'primary')
                    ->modalHeading(fn ($record) => ($record->vitalSigns ? __('messages.appointment.edit_vitals') : __('messages.appointment.record_vitals')).' - '.$record->patient->patientUser->full_name)
                    ->modalWidth('5xl')
                    ->form(self::getVitalFormSchema())
                    ->fillForm(function ($record) {
                        $record = $record->fresh(['vitalSigns']);
                        if (! $record->vitalSigns) {
                            return [];
                        }

                        return [
                            'blood_pressure' => $record->vitalSigns->blood_pressure,
                            'pulse_rate' => $record->vitalSigns->pulse_rate,
                            'respiratory_rate' => $record->vitalSigns->respiratory_rate,
                            'oxygen_saturation' => $record->vitalSigns->oxygen_saturation,
                            'temperature' => $record->vitalSigns->temperature,
                            'random_blood_sugar' => $record->vitalSigns->random_blood_sugar,
                            'fasting_blood_sugar' => $record->vitalSigns->fasting_blood_sugar,
                            'drug_allergies' => $record->vitalSigns->drug_allergies,
                        ];
                    })
                    ->action(function ($record, array $data) {
                        VitalSign::updateOrCreate(
                            [
                                'appointment_id' => $record->id,
                                'tenant_id' => auth()->user()->tenant_id,
                            ],
                            [
                                'patient_id' => $record->patient_id,
                                'blood_pressure' => $data['blood_pressure'],
                                'pulse_rate' => $data['pulse_rate'],
                                'respiratory_rate' => $data['respiratory_rate'],
                                'oxygen_saturation' => $data['oxygen_saturation'],
                                'temperature' => $data['temperature'],
                                'random_blood_sugar' => $data['random_blood_sugar'],
                                'fasting_blood_sugar' => $data['fasting_blood_sugar'],
                                'drug_allergies' => $data['drug_allergies'],
                                'type' => 'Appointment',
                                'ipd_opd_number' => null,
                            ]
                        );

                        if ((int) $record->is_completed !== AppointmentModel::STATUS_IN_QUEUE
                            && (int) $record->is_completed !== AppointmentModel::STATUS_CHECK_IN) {
                            $record->is_completed = AppointmentModel::STATUS_IN_VITAL;
                            $record->save();
                        }

                        Notification::make()
                            ->title(__('messages.appointment.vital_signs_saved'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('send_to_queue')
                    ->label(__('messages.appointment.send_to_queue'))
                    ->icon('heroicon-o-queue-list')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->vitalSigns && (int) $record->is_completed === AppointmentModel::STATUS_IN_VITAL)
                    ->action(function ($record) {
                        self::sendAppointmentToQueue($record);

                        Notification::make()
                            ->title(__('messages.appointment.sent_to_queue'))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordUrl(null)
            ->emptyStateHeading(__('messages.appointment.no_vital_appointments'))
            ->emptyStateDescription(__('messages.appointment.no_vital_appointments_desc'))
            ->emptyStateIcon('heroicon-o-heart');
    }

    public static function sendAppointmentToQueue(AppointmentModel $record): PatientQueue
    {
        $queue = PatientQueue::where('appointment_id', $record->id)->first();

        if (! $queue) {
            $lastQueue = PatientQueue::where('tenant_id', $record->tenant_id)->max('no');
            $queue = PatientQueue::create([
                'appointment_id' => $record->id,
                'no' => $lastQueue ? $lastQueue + 1 : 1,
                'tenant_id' => $record->tenant_id,
            ]);
        }

        $record->is_completed = AppointmentModel::STATUS_IN_QUEUE;
        $record->save();

        return $queue;
    }

    public static function getVitalFormSchema(): array
    {
        return [
            Forms\Components\Section::make(__('messages.vital_signs'))
                ->schema([
                    Forms\Components\TextInput::make('blood_pressure')
                        ->label(__('messages.appointment.blood_pressure'))
                        ->required()
                        ->maxLength(300),
                    Forms\Components\TextInput::make('pulse_rate')
                        ->label(__('messages.appointment.pulse_rate'))
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    Forms\Components\TextInput::make('respiratory_rate')
                        ->label(__('messages.appointment.respiratory_rate'))
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    Forms\Components\TextInput::make('oxygen_saturation')
                        ->label(__('messages.appointment.oxygen_saturation'))
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->maxValue(200),
                    Forms\Components\TextInput::make('temperature')
                        ->label(__('messages.appointment.temperature'))
                        ->numeric()
                        ->required()
                        ->minValue(30),
                ])->columns(2),
            Forms\Components\Section::make(__('messages.appointment.additional_information'))
                ->schema([
                    Forms\Components\TextInput::make('random_blood_sugar')
                        ->label(__('messages.appointment.random_blood_sugar'))
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\TextInput::make('fasting_blood_sugar')
                        ->label(__('messages.appointment.fasting_blood_sugar'))
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\Textarea::make('drug_allergies')
                        ->label(__('messages.appointment.drug_allergies'))
                        ->rows(2),
                ])->columns(2),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVitalAppointments::route('/'),
        ];
    }
}
