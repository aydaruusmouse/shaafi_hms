<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Widgets;

use App\Filament\hospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Mail\NotifyMailHospitalAdminForBookingAppointment;
use App\Models\Appointment;
use App\Models\CustomField;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\LunchBreak;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\ScheduleDay;
use App\Models\User;
use App\Models\UserTenant;
use App\Repositories\AppointmentRepository;
use App\Repositories\AppointmentTransactionRepository;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Actions\CreateAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Stevebauman\Location\Facades\Location;

class AppoinmentCalenderWidget extends FullCalendarWidget
{
    protected function headerActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(AppointmentResource::getUrl('index')),
        ];
    }

    public Model|string|null $model = Appointment::class;

    public function fetchEvents(array $fetchInfo): array
    {

        $appoinment = Appointment::query()
            ->where('opd_date', '>=', $fetchInfo['start'])
            ->where('opd_date', '<=', $fetchInfo['end'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->get()
            ->map(
                fn (Appointment $event) => [
                    'id' => $event->id,
                    'title' => $event->patient->patientUser->full_name,
                    'start' => Carbon::parse($event->opd_date),
                    'end' => Carbon::parse($event->opd_date),
                ]
            )
            ->all();

        return $appoinment;
    }

    public function getFormSchema(): array
    {
        $customFields = CustomField::where('module_name', CustomField::Appointment)->where('tenant_id', getLoggedInUser()->tenant_id)->get();

        $customFieldComponents = [];
        foreach ($customFields as $field) {
            $fieldType = CustomField::FIELD_TYPE_ARR[$field->field_type];
            $fieldName = 'field'.$field->id;
            $fieldLabel = $field->field_name;
            $isRequired = $field->is_required;
            $gridSpan = $field->grid; //

            $customFieldComponents[] = match ($fieldType) {
                'text' => TextInput::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->placeholder($fieldLabel)
                    ->columnSpan($gridSpan)
                    ->default(fn ($record) => $record?->{$fieldName} ?? null),

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
                    ->columnSpan($gridSpan),

                'select' => Select::make($fieldName)
                    ->label($fieldLabel)
                    ->required($isRequired)
                    ->options(explode(',', $field->values))
                    ->placeholder($fieldLabel)
                    ->columnSpan($gridSpan),

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

        return [
            Grid::make(2)->schema([
                Select::make('patient_id')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(fn () => count(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id')) + 10)
                    ->label(__('messages.document.patient').': ')
                    ->placeholder(__('messages.document.select_patient'))
                    ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.document.patient').' '.__('messages.fields.required'),
                    ]),
                Select::make('doctor_id')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->optionsLimit(fn () => count(Doctor::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id')) + 10)
                    ->label(__('messages.case.doctor').':')
                    ->placeholder(__('messages.web_home.select_doctor'))
                    ->options(Doctor::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                    ->native(false)
                    ->afterStateUpdated(function ($component, $operation, $set, $get) {
                        if ($operation == 'create') {
                            $set('opd_date', $component->getContainer()->getLivewire()->mountedActionsArguments[0]['start']->format('Y-m-d H:i:s') ?? now());
                        } else {
                            $set('opd_date', now());
                        }
                        $doctorId = $get('doctor_id');
                        $opd_date = $get('opd_date');

                        $date = Carbon::parse($opd_date)->format('Y-m-d');
                        $dayName = Carbon::parse($opd_date)->format('l');
                        $scheduleDay = ScheduleDay::where('doctor_id', $doctorId)->Where('available_on', $dayName)->get();
                        $perPatientTime = Schedule::whereDoctorId($doctorId)->first();

                        if (isset($input['date'])) {
                            $doctorHoliday = DoctorHoliday::where('doctor_id', $doctorId)->where('date', $date)->get();
                            $break = LunchBreak::where('doctor_id', $doctorId)->where('date', $date)->get();
                            if ($break->count() == 0) {
                                $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->whereNotNull('every_day')->get();
                            } else {
                                $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->where('date', $date)->get();
                            }
                        } else {
                            $doctorHoliday = DoctorHoliday::where('doctor_id', $doctorId)->get();
                            $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->whereNotNull('every_day')->get();
                        }

                        if ($scheduleDay->count() != 0 && $doctorHoliday->count() == 0) {

                            $availableFrom = '';

                            if (Carbon::now()->format('Y-m-d') === $date) {
                                $time = Carbon::parse($perPatientTime->per_patient_time);
                                $totalMinutes = $time->hour * 60 + $time->minute;
                                $totaltime = $totalMinutes.' minutes';
                                $startTime = $scheduleDay->first()->available_from;
                                $endTime = $scheduleDay->first()->available_to;
                                $currentTime = Carbon::now('Asia/Kolkata');
                                if ($currentTime->between($startTime, $endTime)) {
                                    $availableFrom = $currentTime->addMinutes($perPatientTime->per_patient_time)->ceil($totaltime)->format('H:i:s');

                                    if (Carbon::parse($availableFrom)->greaterThan($endTime)) {
                                        $availableFrom = $endTime->format('H:i:s');
                                    }
                                } else {
                                    $availableFrom = $startTime->format('H:i:s');
                                }
                            } else {
                                $availableFrom = $scheduleDay->first()->available_from;
                            }

                            $doctorStartTime = $date.' '.$availableFrom;
                            $doctorEndTime = $date.' '.$scheduleDay->first()->available_to;

                            if (Carbon::parse($doctorEndTime)->isBefore(Carbon::now())) {
                                return Notification::make()
                                    ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                    ->warning()
                                    ->send();
                            }

                            $doctorPatientTime = $perPatientTime->per_patient_time;
                            $timeParts = explode(':', $doctorPatientTime);
                            $minutes = ($timeParts[0] * 60) + $timeParts[1];
                            $startTime = Carbon::now()->setHours((int) substr($doctorStartTime, 11, 2))
                                ->setMinutes((int) substr($doctorStartTime, 14, 2));

                            $endTime = Carbon::now()->setHours((int) substr($doctorEndTime, 11, 2))
                                ->setMinutes((int) substr($doctorEndTime, 14, 2));

                            $appointmentIntervals = [];
                            while ($startTime < $endTime) {
                                $appointmentIntervals[] = $startTime->format('H:i');
                                $startTime->addMinutes($minutes);
                            }

                            if (! empty($doctorBreak)) {
                                foreach ($doctorBreak as $break) {
                                    $startBreakTime = Carbon::parse($date.' '.$break->break_from);
                                    $endBreakTime = Carbon::parse($date.' '.$break->break_to);

                                    $appointmentBreakIntervals = [];
                                    while ($startBreakTime < $endBreakTime) {
                                        $appointmentIntervals[] = $startBreakTime->format('H:i');
                                        $startBreakTime->addMinutes(1);
                                    }

                                    // ??
                                    $appointmentIntervals = array_filter($appointmentIntervals, function ($slot) use ($appointmentBreakIntervals) {
                                        ! in_array($slot, $appointmentBreakIntervals);
                                    });
                                }
                            }
                            if ($availableFrom != '00:00:00' && $scheduleDay->first()->available_to != '00:00:00' && $doctorStartTime != $doctorEndTime) {
                                // ??
                            } else {
                                return Notification::make()
                                    ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            return Notification::make()
                                ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                ->warning()
                                ->send();
                        }
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.case.doctor').' '.__('messages.fields.required'),
                    ])
                    ->required(),
                DatePicker::make('opd_date')
                    ->label(__('messages.visitor.date').':')
                    ->required()
                    ->afterStateUpdated(function (Set $set, $get) {
                        $set('time', null);

                        $doctorId = $get('doctor_id');
                        $opd_date = $get('opd_date');

                        $date = Carbon::parse($opd_date)->format('Y-m-d');
                        $dayName = Carbon::parse($opd_date)->format('l');
                        $scheduleDay = ScheduleDay::where('doctor_id', $doctorId)->Where('available_on', $dayName)->get();
                        $perPatientTime = Schedule::whereDoctorId($doctorId)->first();

                        if (isset($input['date'])) {
                            $doctorHoliday = DoctorHoliday::where('doctor_id', $doctorId)->where('date', $date)->get();
                            $break = LunchBreak::where('doctor_id', $doctorId)->where('date', $date)->get();
                            if ($break->count() == 0) {
                                $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->whereNotNull('every_day')->get();
                            } else {
                                $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->where('date', $date)->get();
                            }
                        } else {
                            $doctorHoliday = DoctorHoliday::where('doctor_id', $doctorId)->get();
                            $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->whereNotNull('every_day')->get();
                        }

                        if ($scheduleDay->count() != 0 && $doctorHoliday->count() == 0) {

                            $availableFrom = '';

                            if (Carbon::now()->format('Y-m-d') === $date) {
                                $time = Carbon::parse($perPatientTime->per_patient_time);
                                $totalMinutes = $time->hour * 60 + $time->minute;
                                $totaltime = $totalMinutes.' minutes';
                                $startTime = $scheduleDay->first()->available_from;
                                $endTime = $scheduleDay->first()->available_to;
                                $currentTime = Carbon::now('Asia/Kolkata');
                                if ($currentTime->between($startTime, $endTime)) {
                                    $availableFrom = $currentTime->addMinutes($perPatientTime->per_patient_time)->ceil($totaltime)->format('H:i:s');

                                    if (Carbon::parse($availableFrom)->greaterThan($endTime)) {
                                        $availableFrom = $endTime->format('H:i:s');
                                    }
                                } else {
                                    $availableFrom = $startTime->format('H:i:s');
                                }
                            } else {
                                $availableFrom = $scheduleDay->first()->available_from;
                            }

                            $doctorStartTime = $date.' '.$availableFrom;
                            $doctorEndTime = $date.' '.$scheduleDay->first()->available_to;

                            if (Carbon::parse($doctorEndTime)->isBefore(Carbon::now())) {
                                return Notification::make()
                                    ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                    ->warning()
                                    ->send();
                            }

                            $doctorPatientTime = $perPatientTime->per_patient_time;
                            $timeParts = explode(':', $doctorPatientTime);
                            $minutes = ($timeParts[0] * 60) + $timeParts[1];
                            $startTime = Carbon::now()->setHours((int) substr($doctorStartTime, 11, 2))
                                ->setMinutes((int) substr($doctorStartTime, 14, 2));

                            $endTime = Carbon::now()->setHours((int) substr($doctorEndTime, 11, 2))
                                ->setMinutes((int) substr($doctorEndTime, 14, 2));

                            $appointmentIntervals = [];
                            while ($startTime < $endTime) {
                                $appointmentIntervals[] = $startTime->format('H:i');
                                $startTime->addMinutes($minutes);
                            }

                            if (! empty($doctorBreak)) {
                                foreach ($doctorBreak as $break) {
                                    $startBreakTime = Carbon::parse($date.' '.$break->break_from);
                                    $endBreakTime = Carbon::parse($date.' '.$break->break_to);

                                    $appointmentBreakIntervals = [];
                                    while ($startBreakTime < $endBreakTime) {
                                        $appointmentIntervals[] = $startBreakTime->format('H:i');
                                        $startBreakTime->addMinutes(1);
                                    }

                                    // ??
                                    $appointmentIntervals = array_filter($appointmentIntervals, function ($slot) use ($appointmentBreakIntervals) {
                                        ! in_array($slot, $appointmentBreakIntervals);
                                    });
                                }
                            }
                            if ($availableFrom != '00:00:00' && $scheduleDay->first()->available_to != '00:00:00' && $doctorStartTime != $doctorEndTime) {
                                // ??
                            } else {
                                return Notification::make()
                                    ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                    ->warning()
                                    ->send();
                            }
                        } else {
                            return Notification::make()
                                ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                ->warning()
                                ->send();
                        }
                    })
                    ->live()
                    ->formatStateUsing(function ($component, $operation) {
                        if ($operation == 'create') {
                            return $component->getContainer()->getLivewire()->mountedActionsArguments[0]['start']->format('Y-m-d H:i:s') ?? now();
                        } else {
                            return now();
                        }
                    })
                    ->native(false),
                Toggle::make('status')
                    ->label(__('messages.common.status').':')
                    ->inline(false)
                    ->extraAlpineAttributes(['class' => 'mt-5']),
                Select::make('payment_type')
                    ->label(__('messages.ipd_payments.payment_mode').':')
                    ->placeholder(__('messages.lunch_break.select_payment_mode'))
                    ->options(Appointment::EDIT_PAYMENT_TYPES)
                    ->searchable()
                    ->required()
                    ->live()
                    ->visible(function (Get $get, Set $set) {
                        $doctorId = $get('doctor_id');
                        $doctor = Doctor::find($doctorId);
                        if ($doctor && $doctor->appointment_charge > 0) {
                            $set('charge', $doctor->appointment_charge);
                        }

                        return $doctor && $doctor->appointment_charge > 0;
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.ipd_payments.payment_mode').' '.__('messages.fields.required'),
                    ])
                    ->native(false),
                TextInput::make('charge')
                    ->label(__('messages.appointment_charge').':')
                    ->visible(function (Get $get) {
                        $doctorId = $get('doctor_id');
                        $doctor = Doctor::find($doctorId);

                        return $doctor && $doctor->appointment_charge > 0;
                    })

                    ->live()
                    ->readOnly(),
                ToggleButtons::make('time')
                    ->label(__('messages.available_slots').':')
                    ->visibleOn('create')
                    ->hiddenOn('edit')
                    ->inline()
                    ->extraAttributes(['class' => 'booked-appointment-slot'])
                    ->options(function (Get $get) {
                        $position = Location::get(request()->ip());
                        $timezone = $position && $position->timezone ? $position->timezone : config('app.timezone');
                        $doctorId = $get('doctor_id');
                        $opd_date = $get('opd_date');
                        $date = Carbon::parse($opd_date)->format('Y-m-d');
                        $dayName = Carbon::parse($opd_date)->format('l');
                        $scheduleDay = ScheduleDay::where('doctor_id', $doctorId)->Where('available_on', $dayName)->get();
                        $perPatientTime = Schedule::whereDoctorId($doctorId)->first();
                        if (isset($date)) {
                            $doctorHoliday = DoctorHoliday::where('doctor_id', $doctorId)->where('date', $date)->get();
                            $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->get();
                        } else {
                            $doctorHoliday = DoctorHoliday::where('doctor_id', $doctorId)->get();
                            $doctorBreak = LunchBreak::where('doctor_id', $doctorId)->get();
                        }
                        if ($scheduleDay->count() != 0 && $doctorHoliday->count() == 0) {

                            $availableFrom = '';

                            if (Carbon::now()->format('Y-m-d') === $date) {
                                $time = Carbon::parse($perPatientTime->per_patient_time);
                                $totalMinutes = $time->hour * 60 + $time->minute;
                                $totaltime = $totalMinutes.' minutes';
                                $startTime = $scheduleDay->first()->available_from;
                                $endTime = $scheduleDay->first()->available_to;
                                $currentTime = Carbon::now($timezone);
                                if ($currentTime->between($startTime, $endTime)) {
                                    $availableFrom = $currentTime->addMinutes($perPatientTime->per_patient_time)->ceil($totaltime)->format('H:i:s');

                                    if (Carbon::parse($availableFrom)->greaterThan($endTime)) {
                                        $availableFrom = $endTime;
                                    }
                                } else {
                                    $availableFrom = $startTime;
                                }
                            } else {
                                $availableFrom = $scheduleDay->first()->available_from;
                            }

                            $doctorStartTime = $date.' '.$availableFrom;
                            $doctorEndTime = $date.' '.$scheduleDay->first()->available_to;

                            if (Carbon::parse($doctorEndTime)->isBefore(Carbon::now())) {
                                // Notification::make()
                                //     ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                //     ->warning()
                                //     ->send();
                                return [];
                            }

                            $doctorPatientTime = $perPatientTime->per_patient_time;
                            $timeParts = explode(':', $doctorPatientTime);
                            $minutes = ($timeParts[0] * 60) + $timeParts[1];
                            $startTime = Carbon::now()->setHours((int) substr($doctorStartTime, 11, 2))
                                ->setMinutes((int) substr($doctorStartTime, 14, 2));

                            $endTime = Carbon::now()->setHours((int) substr($doctorEndTime, 11, 2))
                                ->setMinutes((int) substr($doctorEndTime, 14, 2));

                            $appointmentIntervals = [];
                            while ($startTime < $endTime) {
                                $appointmentIntervals[] = $startTime->format('H:i');
                                $startTime->addMinutes($minutes);
                            }

                            if (! empty($doctorBreak)) {
                                foreach ($doctorBreak as $break) {
                                    if ($break->every_day == 1) {

                                        $startBreakTime = Carbon::parse($date.' '.$break->break_from);
                                        $endBreakTime = Carbon::parse($date.' '.$break->break_to);

                                        $appointmentBreakIntervals = [];
                                        while ($startBreakTime < $endBreakTime) {
                                            $appointmentBreakIntervals[] = $startBreakTime->format('H:i');
                                            $startBreakTime->addMinutes(1);
                                        }

                                        // ??
                                        $appointmentIntervals = array_filter($appointmentIntervals, function ($slot) use ($appointmentBreakIntervals) {
                                            return ! in_array($slot, $appointmentBreakIntervals);
                                        });
                                    } else {
                                        if ($break->date == $date) {
                                            $startBreakTime = Carbon::parse($date.' '.$break->break_from);
                                            $endBreakTime = Carbon::parse($date.' '.$break->break_to);

                                            $appointmentBreakIntervals = [];
                                            while ($startBreakTime < $endBreakTime) {
                                                $appointmentBreakIntervals[] = $startBreakTime->format('H:i');
                                                $startBreakTime->addMinutes(1);
                                            }

                                            // ??
                                            $appointmentIntervals = array_filter($appointmentIntervals, function ($slot) use ($appointmentBreakIntervals) {
                                                return ! in_array($slot, $appointmentBreakIntervals);
                                            });
                                        }
                                    }
                                }
                            }
                            if (count($appointmentIntervals) > 0) {
                                $timeSlots = [];
                                foreach ($appointmentIntervals as $timeSlot) {
                                    $timeSlots[$timeSlot] = $timeSlot;
                                }

                                $availableTo = $scheduleDay->first()->available_to;

                                $scheduleTimeHtml = $dayName.' ['.$availableFrom.' - '.$availableTo.']';

                                echo '<div class="schedule-time text-sm font-medium leading-6 text-gray-950 w-full dark:text-white">'.$scheduleTimeHtml.'</div>';

                                return $timeSlots;
                            }

                            if ($availableFrom != '00:00:00' && $scheduleDay->first()->available_to != '00:00:00' && $doctorStartTime != $doctorEndTime) {
                                // ??
                            } else {
                                // Notification::make()
                                //     ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                                //     ->warning()
                                //     ->send();
                                return [];
                            }
                        } else {
                            // Notification::make()
                            //     ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date'))
                            //     ->warning()
                            //     ->send();
                            return [];
                        }
                    })
                    ->visible(function (Get $get) {
                        return $get('opd_date') != null;
                    })
                    ->disableOptionWhen(function ($value, Get $get) {
                        $doctorId = $get('doctor_id');
                        $opd_date = $get('opd_date');
                        $date = Carbon::parse($opd_date)->format('Y-m-d');

                        $bookedAppointments = Appointment::where('doctor_id', $doctorId)
                            ->whereDate('opd_date', $date)
                            ->pluck('opd_date')
                            ->map(function ($appointment) {
                                return Carbon::parse($appointment)->format('H:i');
                            })
                            ->toArray();

                        return in_array($value, $bookedAppointments);
                    })
                    ->columnSpanFull()
                    ->required(),

                Textarea::make('problem')
                    ->label(__('messages.common.description').':')
                    ->rows(4)
                    ->columnSpanFull(),
            ])
                ->disabled(function ($component) {
                    $startDate = $component->getContainer()->getLivewire()->mountedActionsArguments[0]['start'];
                    $now = Carbon::now();
                    if ($startDate < $now->startOfDay()) {
                        return true;
                    }

                    return false;
                }),
            Section::make('')
                ->schema($customFieldComponents)
                ->hidden(empty($customFieldComponents))
                ->columns(12)->disabled(function ($component) {
                    $startDate = $component->getContainer()->getLivewire()->mountedActionsArguments[0]['start'];
                    $now = Carbon::now();
                    if ($startDate < $now->startOfDay()) {
                        return true;
                    }

                    return false;
                }),
        ];
    }

    protected function modalActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading(__('messages.appointment.new_appointment'))
                ->createAnother(false)
                ->form($this->getFormSchema())
                ->disabled(auth()->user()->hasRole('Doctor'))
                ->action(function (array $data) {
                    $doctor = Doctor::whereId($data['doctor_id'])->first();

                    $data['opd_date'] = $data['opd_date'].' '.$data['time'];
                    $data['payment_type'] = $data['payment_type'] ?? 4;
                    $data['is_completed'] = Appointment::STATUS_IN_VITAL;
                    $data['department_id'] = $doctor->doctor_department_id ?? 3;
                    $appointmentRepository = app(AppointmentRepository::class);

                    if ($data['payment_type'] != 8 && $data['payment_type'] != 7) {
                        $nData = $appointmentRepository->create($data);
                    }

                    if (in_array($data['payment_type'], [1, 2, 3, 5])) {
                        $nData->update(['payment_type' => 4]);
                    }

                    if (! in_array((int) $data['payment_type'], [1, 2, 3, 5, 7, 8], true) && isset($nData)) {
                        app(AppointmentTransactionRepository::class)->store($nData);
                    }
                    $userId = UserTenant::whereTenantId(getLoggedInUser()->tenant_id)->value('user_id');
                    $hospitalDefaultAdmin = User::whereId($userId)->first();
                    if (! empty($hospitalDefaultAdmin)) {
                        $hospitalDefaultAdminEmail = $hospitalDefaultAdmin->email;
                        $doctor = Doctor::whereId($data['doctor_id'])->first();
                        $patient = Patient::whereId($data['patient_id'])->first();

                        $mailData = [
                            'booking_date' => Carbon::parse($data['opd_date'])->translatedFormat('g:i A').' '.Carbon::parse($data['opd_date'])->translatedFormat('jS M, Y'),
                            'patient_name' => $patient->user->full_name,
                            'patient_email' => $patient->user->email,
                            'doctor_name' => $doctor->user->full_name,
                            'doctor_department' => $doctor->department->title,
                            'doctor_email' => $doctor->user->email,
                        ];

                        $mailData['patient_type'] = 'Old';
                        Mail::to($hospitalDefaultAdminEmail)
                            ->send(new NotifyMailHospitalAdminForBookingAppointment(
                                'emails.booking_appointment_mail',
                                __('messages.new_change.notify_mail_for_patient_book'),
                                $mailData
                            ));
                        Mail::to($doctor->user->email)
                            ->send(new NotifyMailHospitalAdminForBookingAppointment(
                                'emails.booking_appointment_mail',
                                __('messages.new_change.notify_mail_for_patient_book'),
                                $mailData
                            ));
                    }

                    $appointmentRepository->createNotification($data);

                    Notification::make()
                        ->success()
                        ->title(__('messages.flash.appointment_saved'))
                        ->send();

                    return $data;
                }),
        ];
    }

    protected function viewAction(): Action
    {
        return Actions\ViewAction::make()
            ->modalWidth('sm')
            ->infolist($this->infolist())
            ->modalFooterActions()
            ->modalHeading(__('messages.front_setting.appointment_details'));
    }

    public function infolist()
    {
        return [
            Group::make([
                TextEntry::make('patient.user.full_name')
                    ->label(__('messages.case.patient').':'),
                TextEntry::make('doctor.department.title')
                    ->label(__('messages.appointment.doctor_department').':'),
                TextEntry::make('doctor.user.full_name')
                    ->label(__('messages.case.doctor').':'),
                TextEntry::make('opd_date')
                    ->label(__('messages.appointment.date').':'),
                TextEntry::make('is_completed')
                    ->label(__('messages.common.status').':')
                    ->formatStateUsing(function ($state) {
                        return $state === 1 ? __('messages.appointment.completed') : __('messages.appointment.pending');
                    }),
                TextEntry::make('problem')
                    ->label(__('messages.common.description').':')
                    ->formatStateUsing(fn ($state) => $state ?: __('messages.common.n/a')),
            ]),
        ];
    }
}
