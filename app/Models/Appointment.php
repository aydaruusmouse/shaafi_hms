<?php

namespace App\Models;

use App\Traits\PopulateTenantID;
use Eloquent as Model;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

/**
 * Class Appointment
 *
 * @version February 13, 2020, 5:52 am UTC
 *
 * @property int $id
 * @property int $patient_id
 * @property int $doctor_id
 * @property int $department_id
 * @property Carbon $opd_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Appointment newModelQuery()
 * @method static Builder|Appointment newQuery()
 * @method static Builder|Appointment query()
 * @method static Builder|Appointment whereCreatedAt($value)
 * @method static Builder|Appointment whereDepartmentId($value)
 * @method static Builder|Appointment whereDoctorId($value)
 * @method static Builder|Appointment whereId($value)
 * @method static Builder|Appointment whereOpdDate($value)
 * @method static Builder|Appointment wherePatientId($value)
 * @method static Builder|Appointment whereUpdatedAt($value)
 *
 * @mixin Model
 *
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\Doctor $doctor
 * @property-read \App\Models\User $patient
 * @property string|null $problem
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Appointment whereProblem($value)
 *
 * @property int $is_completed
 *
 * @method static Builder|Appointment whereIsCompleted($value)
 */
class Appointment extends Model
{
    use BelongsToTenant, PopulateTenantID;

    /**
     * @var string
     */
    public $table = 'appointments';

    const STATUS_ARR = [
        '2' => 'All',
        '0' => 'Pending',
        '1' => 'Completed',
        '3' => 'Cancelled',
        '4' => 'In Queue',
        '5' => 'Check In',
        '9' => 'In Vital',
    ];

    const STATUS_PENDING = 0;

    const STATUS_COMPLETED = 1;

    const STATUS_ALL = 2;

    const STATUS_CANCELLED = 3;
    const STATUS_IN_QUEUE = 4;
    const STATUS_CHECK_IN = 5;
    const STATUS_IN_VITAL = 9;

    const TYPE_STRIPE = 1;

    const TYPE_RAZORPAY = 2;

    const TYPE_PAYPAL = 3;

    const TYPE_CASH = 4;

    const FLUTTERWAVE = 5;

    const CHEQUE = 6;

    const PHONEPE = 7;

    const PAYSTACK = 8;

    const PAYMENT_TYPES = [
        self::TYPE_STRIPE => 'Stripe',
        self::TYPE_RAZORPAY => 'RazorPay',
        self::TYPE_PAYPAL => 'Paypal',
        self::TYPE_CASH => 'Cash',
        self::CHEQUE => 'Cheque',
        self::PHONEPE => 'PhonePe',
    ];

    const EDIT_PAYMENT_TYPES = [
        self::TYPE_CASH => 'Cash',
        self::CHEQUE => 'Cheque',
    ];

    /**
     * @var array
     */
    public $fillable = [
        'patient_id',
        'doctor_id',
        'department_id',
        'opd_date',
        'ticket_number',
        'problem',
        'is_completed',
        'tenant_id',
        'payment_status',
        'payment_type',
        'custom_field',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'patient_id' => 'required',
        'doctor_id' => 'required',
        'department_id' => 'required',
        'opd_date' => 'required',
        'problem' => 'nullable',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'patient_id' => 'integer',
            'doctor_id' => 'integer',
            'department_id' => 'integer',
            'opd_date' => 'datetime',
            'custom_field' => 'array',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(DoctorDepartment::class, 'department_id');
    }

    public function vitalSigns(): HasOne
    {
        return $this->hasOne(VitalSign::class, 'appointment_id');
    }

    public function appointmentTransaction(): HasOne
    {
        return $this->hasOne(AppointmentTransaction::class, 'appointment_id');
    }

    public static function paymentModeLabel(?int $type): string
    {
        return match ((int) $type) {
            self::TYPE_STRIPE => __('messages.setting.stripe'),
            self::TYPE_RAZORPAY => __('messages.setting.razorpay'),
            self::TYPE_PAYPAL => __('messages.setting.paypal'),
            self::TYPE_CASH => __('messages.transaction_filter.cash'),
            self::FLUTTERWAVE => __('messages.flutterwave.flutterwave'),
            self::CHEQUE => __('messages.cheque'),
            self::PAYSTACK => __('messages.setting.paystack'),
            self::PHONEPE => __('messages.phonepe.phonepe'),
            default => __('messages.common.n/a'),
        };
    }

    public static function paymentModeColor(?int $type): string
    {
        return match ((int) $type) {
            self::TYPE_STRIPE, self::TYPE_PAYPAL => 'primary',
            self::TYPE_RAZORPAY, self::PHONEPE => 'success',
            self::TYPE_CASH, self::FLUTTERWAVE => 'info',
            self::CHEQUE, self::PAYSTACK => 'warning',
            default => 'primary',
        };
    }

    public static function paymentModeOptions(): array
    {
        return [
            self::TYPE_CASH => __('messages.transaction_filter.cash'),
            self::CHEQUE => __('messages.cheque'),
            self::TYPE_STRIPE => __('messages.setting.stripe'),
            self::TYPE_RAZORPAY => __('messages.setting.razorpay'),
            self::TYPE_PAYPAL => __('messages.setting.paypal'),
            self::FLUTTERWAVE => __('messages.flutterwave.flutterwave'),
            self::PHONEPE => __('messages.phonepe.phonepe'),
            self::PAYSTACK => __('messages.setting.paystack'),
        ];
    }

    public function prepareAppointment()
    {
        return [
            'id' => $this->id ?? __('messages.common.n/a'),
            'doctor_name' => $this->doctor->doctorUser->full_name ?? __('messages.common.n/a'),
            'appointment_date' => isset($this->opd_date) ? Carbon::parse($this->opd_date)->format('d M, Y') : __('messages.common.n/a'),
            'appointment_time' => isset($this->opd_date) ? \Carbon\Carbon::parse($this->opd_date)->isoFormat('LT') : __('messages.common.n/a'),
            'doctor_department' => $this->department->title ?? __('messages.common.n/a'),
            'doctor_image_url' => $this->doctor->doctorUser->getApiImageUrlAttribute(),
        ];
    }

    public function prepareAppointmentForDoctor()
    {
        return [
            'id' => $this->id ?? __('messages.common.n/a'),
            'patient_name' => $this->patient->patientUser->full_name ?? __('messages.common.n/a'),
            'appointment_date' => isset($this->opd_date) ? Carbon::parse($this->opd_date)->format('jS M, y') : __('messages.common.n/a'),
            'appointment_time' => isset($this->opd_date) ? \Carbon\Carbon::parse($this->opd_date)->isoFormat('LT') : __('messages.common.n/a'),
            'patient_image' => $this->patient->patientUser->getApiImageUrlAttribute(),
        ];
    }

    public function prepareAppointmentForAdmin()
    {
        return [
            'id' => $this->id ?? __('messages.common.n/a'),
            'patient_id' => $this->patient_id ?? __('messages.common.n/a'),
            'patient_name' => $this->patient->patientUser->full_name ?? __('messages.common.n/a'),
            'patient_image' => $this->patient->patientUser->getApiImageUrlAttribute(),
            'appointment_date' => isset($this->opd_date) ? Carbon::parse($this->opd_date)->format('jS M, y') : __('messages.common.n/a'),
            'appointment_time' => isset($this->opd_date) ? \Carbon\Carbon::parse($this->opd_date)->isoFormat('LT') : __('messages.common.n/a'),
            'doctor_id' => $this->doctor->id ?? __('messages.common.n/a'),
            'is_completed' => self::STATUS_ARR[$this->is_completed] ?? __('messages.common.n/a'),
            'doctor_name' => $this->doctor->doctorUser->full_name ?? __('messages.common.n/a'),
            'doctor_department' => $this->department->title ?? __('messages.common.n/a'),

        ];
    }

    public static function getForm()
    {
        return [
            Grid::make(2)->schema([
                Select::make('patient_id')
                    ->label(__('messages.document.patient').': ')
                    ->placeholder(__('messages.document.select_patient'))
                    ->relationship('patient.patientUser', 'first_name', fn (Builder $query) => $query->where('status', true)->where('department_id', 3))
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.document.patient').' '.__('messages.fields.required'),
                    ]),
                Select::make('doctor_id')
                    ->label(__('messages.prescription.doctor').': ')
                    ->placeholder(__('messages.web_home.select_doctor'))
                    ->relationship('doctor.doctorUser', 'first_name', fn (Builder $query) => $query->where('status', true)->where('department_id', 2))
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.prescription.doctor').' '.__('messages.fields.required'),
                    ]),
                DatePicker::make('opd_date')
                    ->label(__('messages.appointment.date').': ')
                    ->required()
                    ->live()
                    ->native(false)
                    ->readOnly()
                    ->disabled(),
                Toggle::make('payment_status')
                    ->label(__('messages.common.status'))
                    ->extraAlpineAttributes(['class' => 'mt-5']),
                ToggleButtons::make('time')
                    ->label(__('messages.available_slots').':')
                    ->visibleOn('create')
                    ->hiddenOn('edit')
                    ->inline()
                    ->options(function (Get $get) {
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
                                $availableFrom = Carbon::now()->addMinutes($perPatientTime->per_patient_time)->ceilMinute()->format('H:i:s');
                            } else {
                                $availableFrom = $scheduleDay->first()->available_from;
                            }

                            $doctorStartTime = $date.' '.$availableFrom;
                            $doctorEndTime = $date.' '.$scheduleDay->first()->available_to;

                            if (Carbon::parse($doctorEndTime)->isBefore(Carbon::now())) {
                                // Notification::make()
                                //     ->title(__('js.doctor_schedule_not_available_on_this_date'))
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
                                    $startBreakTime = Carbon::parse($date.' '.$break->break_from);
                                    $endBreakTime = Carbon::parse($date.' '.$break->break_to);

                                    $appointmentBreakIntervals = [];
                                    while ($startBreakTime < $endBreakTime) {
                                        $appointmentIntervals[] = $startBreakTime->format('H:i');
                                        $startBreakTime->addMinutes(1);
                                    }

                                    // ??
                                    $appointmentIntervals = array_filter($appointmentIntervals, function ($slot) use ($appointmentBreakIntervals) {
                                        return ! in_array($slot, $appointmentBreakIntervals);
                                    });
                                }
                            }

                            if (count($appointmentIntervals) > 0) {
                                $timeSlots = [];
                                foreach ($appointmentIntervals as $timeSlot) {
                                    $timeSlots[$timeSlot] = $timeSlot;
                                }

                                $availableTo = $scheduleDay->first()->available_to;

                                $scheduleTimeHtml = $dayName.'['.$availableFrom.' - '.$availableTo.']';

                                echo '<div class="schedule-time">'.$scheduleTimeHtml.'</div><br>';

                                return $timeSlots;
                            }

                            if ($availableFrom != '00:00:00' && $scheduleDay->first()->available_to != '00:00:00' && $doctorStartTime != $doctorEndTime) {
                                // ??
                            } else {
                                Notification::make()
                                    ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date').': ')
                                    ->warning()
                                    ->send();

                                return [];
                            }
                        } else {
                            Notification::make()
                                ->title(__('messages.appointment.doctor_schedule_not_available_on_this_date').': ')
                                ->warning()
                                ->send();

                            return [];
                        }
                    })
                    ->visible(function (Get $get) {
                        return $get('opd_date') != null;
                    })
                    ->required(),
                Textarea::make('problem')
                    ->label(__('messages.appointment.description').':')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ];
    }
}
