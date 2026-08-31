<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Doctor;
use App\Models\DoctorOPDCharge;
use App\Models\Notification;
use App\Models\OpdPatientDepartment;
use App\Models\Patient;
use App\Models\PatientCase;
use App\Models\Prescription;
use App\Models\Appointment;
use App\Models\VitalSign;
use Exception;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class OpdPatientDepartmentRepository
 *
 * @version September 8, 2020, 6:42 am UTC
 */
class OpdPatientDepartmentRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'patient_id',
        'ipd_number',
        'height',
        'weight',
        'bp',
        'symptoms',
        'notes',
        'admission_date',
        'case_id',
        'is_old_patient',
        'doctor_id',
        'standard_charge',
        'payment_mode',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return OpdPatientDepartment::class;
    }

    /**
     * @return mixed
     */
    public function getAssociatedData()
    {
        $data['patients'] = Patient::with('patientUser')->get()->where('patientUser.status', '=', 1)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck(
            'patientUser.full_name',
            'id'
        )->sort();
        $data['doctors'] = Doctor::with('doctorUser')->get()->where('doctorUser.status', '=', 1)->where('tenant_id', getLoggedInUser()->tenant_id)->pluck(
            'doctorUser.full_name',
            'id'
        )->sort();
        $data['opdNumber'] = $this->model->generateUniqueOpdNumber();
        $data['paymentMode'] = $this->model::PAYMENT_MODES;

        return $data;
    }

    public function getPatientCases(int $patientId): Collection
    {
        $opdPatient = OpdPatientDepartment::pluck('case_id')->toArray();

        return PatientCase::where('patient_id', $patientId)->whereNotIn('id', array_map('intval', $opdPatient))->where('status', 1)->pluck('case_id', 'id');
    }

    public function getDoctorsData(): Collection
    {
        return Doctor::with('doctorUser')->get()->where('doctorUser.status', '=', 1)->pluck('doctorUser.full_name', 'id');
    }

    public function getDoctorsList(): array
    {
        $result = Doctor::with('user')->get()
            ->where('user.status', '=', 1)->pluck('user.full_name', 'id')->toArray();

        $doctors = [];
        foreach ($result as $key => $item) {
            $doctors[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $doctors;
    }

    public function store(array $input)
    {
        try {
            $input['is_old_patient'] = isset($input['is_old_patient']) ? true : false;
            $jsonFields = [];

            foreach ($input as $key => $value) {
                if (strpos($key, 'field') === 0) {
                    $jsonFields[$key] = $value;
                }
            }
            $input['custom_field'] = ! empty($jsonFields) ? $jsonFields : null;
            $opdPatientDepartment = OpdPatientDepartment::create($input);
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return $opdPatientDepartment;
    }

    public function createFromAppointment(Appointment $appointment): OpdPatientDepartment
    {
        $appointment->loadMissing(['doctor', 'vitalSigns']);

        $existingCase = PatientCase::where('patient_id', $appointment->patient_id)
            ->where('doctor_id', $appointment->doctor_id)
            ->where('status', PatientCase::ACTIVE)
            ->latest('id')
            ->first();

        if (! $existingCase) {
            $existingCase = PatientCase::create([
                'case_id' => mb_strtoupper(PatientCase::generateUniqueCaseId()),
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->opd_date,
                'status' => PatientCase::ACTIVE,
                'description' => $appointment->problem,
                'fee' => (float) ($appointment->doctor?->appointment_charge ?? 0),
            ]);
        }

        $existingOpd = OpdPatientDepartment::where('tenant_id', $appointment->tenant_id)
            ->where('patient_id', $appointment->patient_id)
            ->where('case_id', $existingCase->id)
            ->where('doctor_id', $appointment->doctor_id)
            ->where('appointment_date', $appointment->opd_date)
            ->first();

        if ($existingOpd) {
            return $existingOpd;
        }

        $doctorCharge = DoctorOPDCharge::where('doctor_id', $appointment->doctor_id)->first();
        $isOldPatient = OpdPatientDepartment::where('patient_id', $appointment->patient_id)->exists();

        $opd = OpdPatientDepartment::create([
            'patient_id' => $appointment->patient_id,
            'case_id' => $existingCase->id,
            'doctor_id' => $appointment->doctor_id,
            'appointment_date' => $appointment->opd_date,
            'opd_number' => OpdPatientDepartment::generateUniqueOpdNumber(),
            'standard_charge' => (float) ($doctorCharge?->standard_charge ?? $appointment->doctor?->appointment_charge ?? 0),
            'payment_mode' => (int) $appointment->payment_type === Appointment::CHEQUE ? 2 : 1,
            'symptoms' => $appointment->problem,
            'bp' => $appointment->vitalSigns?->blood_pressure,
            'is_old_patient' => $isOldPatient,
            'is_discharge' => false,
            'tenant_id' => $appointment->tenant_id,
        ]);

        $this->createNotification([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
        ]);

        VitalSign::where('appointment_id', $appointment->id)->update([
            'ipd_opd_number' => $opd->opd_number,
            'type' => 'OPD',
            'case_id' => $opd->case_id,
            'patient_id' => $appointment->patient_id,
        ]);

        return $opd;
    }

    public function updateOpdPatientDepartment(array $input, OpdPatientDepartment $opdPatientDepartment): bool
    {
        try {
            $input['standard_charge'] = removeCommaFromNumbers($input['standard_charge']);
            $input['is_old_patient'] = isset($input['is_old_patient']) ? true : false;
            $jsonFields = [];

            foreach ($input as $key => $value) {
                if (strpos($key, 'field') === 0) {
                    $jsonFields[$key] = $value;
                }
            }
            $input['custom_field'] = ! empty($jsonFields) ? $jsonFields : null;
            $opdPatientDepartment->update($input);
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return true;
    }

    public function createNotification(array $input)
    {
        try {
            $patient = Patient::with('patientUser')->where('id', $input['patient_id'])->first();
            $doctor = Doctor::with('doctorUser')->where('id', $input['doctor_id'])->first()->user->fullname;

            if (isset($input['revisit'])) {
                $title = $patient->patientUser->full_name.' you are visited doctor '.$doctor.'.';
            } else {
                $title = $patient->patientUser->full_name.' your OPD record has been created.';
            }
            addNotification([
                Notification::NOTIFICATION_TYPE['OPD Patient'],
                $patient->user_id,
                Notification::NOTIFICATION_FOR[Notification::PATIENT],
                $title,
            ]);
        } catch (Exception $e) {
            FilamentNotification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();
            // throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function getMedicinesCategoriesData(): Collection
    {
        return Category::where('is_active', '=', 1)->pluck('name', 'id');
    }

    public function getMedicineCategoriesList(): array
    {
        $result = Category::where('tenant_id', getLoggedInUser()->tenant_id)->where('is_active', '=', 1)->pluck('name', 'id')->toArray();

        return $result;
    }

    public function getDoseDurationList()
    {
        $result = Prescription::DOSE_DURATION;

        $doseDuration = [];
        foreach ($result as $key => $item) {
            $doseDuration[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $doseDuration;
    }

    public function getDoseIntervalList()
    {
        $result = Prescription::DOSE_INTERVAL;

        $doseInterval = [];
        foreach ($result as $key => $item) {
            $doseInterval[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $doseInterval;
    }

    public function getMealList()
    {
        $result = Prescription::MEAL_ARR;

        $meal = [];
        foreach ($result as $key => $item) {
            $meal[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $meal;
    }
}
