<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources;

use App\Filament\HospitalAdmin\Clusters\Appointment;
use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\VitalAppointmentResource;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Appointment as ModelsAppointment;
use App\Models\Doctor;
use App\Models\PatientQueue;
use App\Models\User;
use App\Repositories\OpdPatientDepartmentRepository;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PatientQueueResource extends Resource
{
    protected static ?string $model = PatientQueue::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Appointment::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getLabel(): string
    {
        return __('Patient Queue');
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && !getModuleAccess('Appointments')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist'])) {
            return true;
        }
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            $query->with(['appointment.vitalSigns', 'appointment.patient.patientUser', 'appointment.doctor.doctorUser'])
                ->where('tenant_id', auth()->user()->tenant_id);

            if (getLoggedinDoctor()) {
                $doctorId = Doctor::where('user_id', getLoggedInUserId())->first();
                $query->whereHas('appointment', function (Builder $query) use ($doctorId) {
                    $query->where('doctor_id', '=', $doctorId->id);
                })->whereDate('created_at', Carbon::today());
            }if(getLoggedInUser()->hasRole('Receptionist')){
                $query->whereDate('created_at', Carbon::today());
            }
        });

        return $table
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('no')
                    ->badge('primary')
                    ->sortable()
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('appointment.patient.patientUser.profile')
                    ->label(__('messages.role.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (!$record->appointment->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->appointment->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn($record) => PatientResource::getUrl('view', ['record' => $record->appointment->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('appointment.patient.patientUser.full_name')
                    ->label('')
                    ->description(fn($record) => $record->appointment->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn($record) => '<a href="' . PatientResource::getUrl('view', ['record' => $record->appointment->patient->id]) . '" class="hoverLink">' . $record->appointment->patient->patientUser->full_name . '</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                SpatieMediaLibraryImageColumn::make('appointment.doctor.doctorUser.profile')
                    ->label(__('messages.role.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (!$record->appointment->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->appointment->doctor->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn($record) => DoctorResource::getUrl('view', ['record' => $record->appointment->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('appointment.doctor.doctorUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn($record) => $record->appointment->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->formatStateUsing(fn($record) => '<a href="' . DoctorResource::getUrl('view', ['record' => $record->appointment->doctor->id]) . '" class="hoverLink">' . $record->appointment->doctor->doctorUser->full_name . '</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('appointment.opd_date')
                    ->label(__('messages.opd_patient.appointment_date'))
                    ->sortable()
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $time = \Carbon\Carbon::parse($record->appointment->opd_date)->isoFormat('LT');
                        $date = \Carbon\Carbon::parse($record->appointment->opd_date)->translatedFormat('jS M, Y');
                        return "<div class='text-center'><span>{$time}</span><br><span>{$date}</span></div>";
                    })
                    ->html()
                    ->searchable(),
                TextColumn::make('appointment.is_completed')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->view('tables.columns.hospitalAdmin.patient_queue_status'),
            ])
            ->filters([
                //
            ])
            // ->recordAction(null)
            ->recordUrl(null)
            ->actions([
                Tables\Actions\Action::make('record_vitals')
                    ->icon('heroicon-o-heart')
                    ->iconButton()
                    ->color('warning')
                    ->modalHeading(fn ($record) => ($record->appointment?->vitalSigns ? __('messages.appointment.edit_vitals') : __('messages.appointment.record_vitals')).' - '.($record->appointment?->patient?->patientUser?->full_name ?? ''))
                    ->modalWidth('5xl')
                    ->form(VitalAppointmentResource::getVitalFormSchema())
                    ->fillForm(function ($record) {
                        $vitals = $record->appointment?->vitalSigns;
                        if (! $vitals) {
                            return [];
                        }

                        return [
                            'blood_pressure' => $vitals->blood_pressure,
                            'pulse_rate' => $vitals->pulse_rate,
                            'respiratory_rate' => $vitals->respiratory_rate,
                            'oxygen_saturation' => $vitals->oxygen_saturation,
                            'temperature' => $vitals->temperature,
                            'random_blood_sugar' => $vitals->random_blood_sugar,
                            'fasting_blood_sugar' => $vitals->fasting_blood_sugar,
                            'drug_allergies' => $vitals->drug_allergies,
                        ];
                    })
                    ->action(function ($record, array $data) {
                        $appointment = $record->appointment;
                        if (! $appointment) {
                            return;
                        }

                        \App\Models\VitalSign::updateOrCreate(
                            [
                                'appointment_id' => $appointment->id,
                                'tenant_id' => auth()->user()->tenant_id,
                            ],
                            [
                                'patient_id' => $appointment->patient_id,
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

                        Notification::make()
                            ->title(__('messages.appointment.vital_signs_saved'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('check_in')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->iconButton()
                    ->color('success')
                    ->action(function ($record) {
                        $appointment = ModelsAppointment::find($record->appointment_id);

                        if (! $appointment) {
                            return Notification::make()
                                ->title(__('messages.common.something_want_wrong'))
                                ->danger()
                                ->send();
                        }

                        if (! $appointment->vitalSigns) {
                            $appointment->update([
                                'is_completed' => ModelsAppointment::STATUS_IN_VITAL,
                            ]);

                            return Notification::make()
                                ->title(__('messages.appointment.record_vitals_before_check_in'))
                                ->warning()
                                ->send();
                        }

                        try {
                            app(OpdPatientDepartmentRepository::class)->createFromAppointment($appointment);
                        } catch (\Throwable $e) {
                            return Notification::make()
                                ->title(__('messages.common.something_want_wrong'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }

                        $appointment->update([
                            'is_completed' => ModelsAppointment::STATUS_CHECK_IN,
                        ]);

                        $record->delete();

                        return Notification::make()
                            ->title(__('messages.appointment.checked_in_to_opd'))
                            ->success()
                            ->send();
                    })->requiresConfirmation()
                    ->hidden(function ($record) {
                        return $record->appointment->is_completed == ModelsAppointment::STATUS_CHECK_IN;
                    }),
                Tables\Actions\Action::make('check_out')
                    ->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->iconButton()->color('danger')
                    ->action(function ($record) {
                        $appointment = ModelsAppointment::find($record->appointment_id);

                        $record->delete();

                        $appointment->update([
                            'is_completed' => ModelsAppointment::STATUS_COMPLETED
                        ]);

                        return Notification::make()
                            ->title(__('Status change successfully.'))
                            ->success()
                            ->send();
                    })->requiresConfirmation()->hidden(function ($record) {
                        return $record->is_completed != ModelsAppointment::STATUS_PENDING;
                    }),
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
            'index' => Pages\ListPatientQueues::route('/'),
        ];
    }
}
