<?php

namespace App\Repositories;

use App;
use App\Models\Bed;
use App\Models\BedAssign;
use App\Models\BedType;
use App\Models\Category;
use App\Models\Doctor;
use App\Models\IpdPatientDepartment;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\PatientCase;
use App\Models\Prescription;
use App\Models\Setting;
use Exception;
use Filament\Notifications\Notification as NotificationsNotification;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class IpdPatientDepartmentRepository
 *
 * @version September 8, 2020, 6:42 am UTC
 */
class IpdPatientDepartmentRepository extends BaseRepository
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
        'bed_group_id',
        'bed_id',
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
        return IpdPatientDepartment::class;
    }

    /**
     * @return mixed
     */
    public function getAssociatedData()
    {

        $data['patients'] = Patient::with('patientUser')->where('tenant_id', getLoggedInUser()->tenant_id)->get()->where('patientUser.status', '=', 1)
            ->mapWithKeys(function ($patient) {
                return [$patient->id => formatPatientOptionLabel($patient)];
            })
            ->sort();
        $data['doctors'] = Doctor::with('doctorUser')->where('tenant_id', getLoggedInUser()->tenant_id)->get()->where('doctorUser.status', '=', 1)->where('doctorUser.tenant_id', getLoggedInUser()->tenant_id)->pluck(
            'doctorUser.full_name',
            'id'
        )->sort();
        // $data['patients'] = Patient::with('patientUser')->get()->where('patientUser.status', '=', 1)->pluck(
        //     'patientUser.full_name',
        //     'id'
        // )->sort();
        // $data['doctors'] = Doctor::with('doctorUser')->get()->where('doctorUser.status', '=', 1)->pluck(
        //     'doctorUser.full_name',
        //     'id'
        // )->sort();
        $data['bedTypes'] = BedType::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('title', 'id')->toArray();

        natcasesort($data['bedTypes']);
        $data['ipdNumber'] = $this->model->generateUniqueIpdNumber();

        return $data;
    }

    public function getPatientCases(int $patientId): Collection
    {
        // $ipdPatient = IpdPatientDepartment::pluck('case_id')->toArray();
        $patientCase = PatientCase::where('patient_id', $patientId)->where('status', 1)->get()->pluck('case_id', 'id');

        return $patientCase;
    }

    public function getPatientBeds(int $bedTypeId, $isEdit, $bedId, $ipdPatientBedTypeId): Collection
    {
        $beds = null;

        if (! $isEdit) {
            $beds = Bed::orderBy('name')->where('bed_type', $bedTypeId)->where('is_available', 1)->pluck('name', 'id');
        } else {
            $beds = Bed::orderBy('name')->where('bed_type', $bedTypeId)->where('is_available', 1);
            if ($bedTypeId == $ipdPatientBedTypeId) {
                $beds->orWhere('id', $bedId);
            }
            $beds = $beds->pluck('name', 'id');
        }

        return $beds;
    }

    public function getDoctorsData(): Collection
    {
        return Doctor::with('doctorUser')->get()->where('doctorUser.status', '=', 1)->pluck('doctorUser.full_name', 'id');
    }

    public function getPatientsData(): Collection
    {
        return Patient::with('patientUser')->get()->where('patientUser.status', '=', 1)->pluck('patientUser.full_name', 'id');
    }

    public function getDoctorsList(): array
    {
        $result = Doctor::with('doctorUser')->get()
            ->where('doctorUser.status', '=', 1)->pluck('doctorUser.full_name', 'id')->toArray();

        $doctors = [];
        foreach ($result as $key => $item) {
            $doctors[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $doctors;
    }

    public function getMedicinesCategoriesData(): Collection
    {
        $result = Category::where('tenant_id', getLoggedInUser()->tenant_id)->where('is_active', '=', 1)->pluck('name', 'id')->toArray();

        return $result;
    }

    public function getMedicineCategoriesList(): array
    {
        $result = Category::where('tenant_id', getLoggedInUser()->tenant_id)->where('is_active', '=', 1)->pluck('name', 'id')->toArray();

        return $result;
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
            $input['ipd_number'] = $input['ipd_number'] ?? IpdPatientDepartment::generateUniqueIpdNumber();
            $input['admission_id'] = $input['admission_id'] ?? IpdPatientDepartment::generateUniqueAdmissionId();
            $ipdPatientDepartment = IpdPatientDepartment::create($input);

            $bedAssignData = [
                'bed_id' => $input['bed_id'],
                'patient_id' => $input['patient_id'],
                'case_id' => $ipdPatientDepartment->patientCase->case_id,
                'assign_date' => $input['admission_date'],
                'ipd_patient_department_id' => $ipdPatientDepartment->id,
                'status' => true,
            ];
            /** @var BedAssignRepository $bedAssign */
            $bedAssign = App::make(BedAssignRepository::class);
            $bedAssign->store($bedAssignData);
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return $ipdPatientDepartment;
    }

    public function updateIpdPatientDepartment(array $input, IpdPatientDepartment $ipdPatientDepartment): bool
    {
        try {

            $input['is_old_patient'] = isset($input['is_old_patient']) ? true : false;
            $bedId = $ipdPatientDepartment->bed_id;
            $jsonFields = [];

            foreach ($input as $key => $value) {
                if (strpos($key, 'field') === 0) {
                    $jsonFields[$key] = $value;
                }
            }
            $input['custom_field'] = ! empty($jsonFields) ? $jsonFields : null;
            if (empty($input['admission_id'])) {
                $input['admission_id'] = $ipdPatientDepartment->admission_id ?: IpdPatientDepartment::generateUniqueAdmissionId();
            }

            /** @var IpdPatientDepartment $ipdPatientDepartment */
            $ipdPatientDepartment = $this->update($input, $ipdPatientDepartment->id);

            $bedAssignData = [
                'bed_id' => $input['bed_id'],
                'patient_id' => $input['patient_id'],
                'case_id' => $ipdPatientDepartment->patientCase->case_id,
                'assign_date' => $input['admission_date'],
                'status' => true,
            ];

            /** @var BedAssign $bedAssign */
            $bedAssignUpdate = BedAssign::whereBedId($bedId)->latest()->first();

            // if (! empty($bedAssignUpdate)) {
            //     /** @var BedAssignRepository $bedAssign */
            //     $bedAssign = App::make(BedAssignRepository::class);
            //     $bedAssign->update($bedAssignData, $bedAssignUpdate);
            // }
            $bedAssign = App::make(BedAssignRepository::class);

            if (empty($bedAssignUpdate)) {
                $bedAssigns = BedAssign::create($bedAssignData);
                BedAssign::where('id', $bedAssigns->id)->update(['ipd_patient_department_id' => $ipdPatientDepartment->id]);
            } else {
                $bedAssign->update($bedAssignData, $bedAssignUpdate);
            }
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function deleteIpdPatientDepartment(IpdPatientDepartment $ipdPatientDepartment): bool
    {
        $ipdPatientDepartment->bed->update(['is_available' => 1]);
        if ($ipdPatientDepartment->bedAssign) {
            BedAssign::where('tenant_id', getLoggedInUser()->tenant_id)->where('id', $ipdPatientDepartment->bedAssign->id)->delete();
        }
        $ipdPatientDepartment->delete();

        return true;
    }

    public function dischargePatient(IpdPatientDepartment $ipdPatientDepartment, array $data): void
    {
        $ipdPatientDepartment->update(array_merge([
            'is_discharge' => true,
        ], \App\Support\DischargeSummaryForm::payload($data)));

        BedAssign::where('ipd_patient_department_id', $ipdPatientDepartment->id)
            ->where('status', 1)
            ->update([
                'status' => 0,
                'discharge_date' => $data['discharge_date'],
            ]);

        $ipdPatientDepartment->refresh();
        if ($ipdPatientDepartment->bed) {
            $ipdPatientDepartment->bed->update(['is_available' => 1]);
        }
    }

    /**
     * @return mixed
     */
    public function getSyncListForCreate()
    {
        $data['setting'] = Setting::pluck('value', 'key');

        return $data;
    }

    public function createNotification(array $input)
    {
        try {
            $patient = Patient::with('patientUser')->where('id', $input['patient_id'])->first();
            addNotification([
                Notification::NOTIFICATION_TYPE['IPD Patient'],
                $patient->user_id,
                Notification::NOTIFICATION_FOR[Notification::PATIENT],
                $patient->patientUser->full_name.' your IPD record has been created.',
            ]);
        } catch (Exception $e) {
            NotificationsNotification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();
            // throw new UnprocessableEntityHttpException($e->getMessage());
        }
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
