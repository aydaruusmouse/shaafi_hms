<?php

namespace App\Repositories;

use App\Models\DeathReport;
use App\Models\Doctor;
use App\Models\PatientCase;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class DeathReportRepository
 *
 * @version February 18, 2020, 11:10 am UTC
 */
class DeathReportRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'patient_id',
        'case_id',
        'doctor_id',
        'date',
        'description',
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
        return DeathReport::class;
    }

    public function getCases(): array
    {
        $user = Auth::user();
        $casesQuery = PatientCase::where('tenant_id', Auth::user()->tenant_id)
            ->with('patient.patientUser');

        if ($user->hasRole('Doctor')) {
            $casesQuery->where('doctor_id', $user->owner_id);
        }

        $cases = $casesQuery->orderByDesc('id')->get();

        $result = [];
        foreach ($cases as $case) {
            $patientName = $case->patient?->patientUser?->full_name ?? '';
            $result[$case->case_id] = trim($case->case_id.'  '.$patientName);
        }

        return $result;
    }

    public function getDoctors()
    {
        /** @var Doctor $doctors */
        $doctors = Doctor::with('doctorUser')->get()->where('doctorUser.status', '=', 1)->pluck('doctorUser.full_name', 'id')->sort();

        return $doctors;
    }

    public function store(array $input): bool
    {
        try {
            $caseId = $input['case_id'];
            $patientId = PatientCase::whereCaseId($caseId)->first()->patient_id;
            $input['patient_id'] = $patientId;
            $deathReport = DeathReport::create($input);

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function update($input, $deathReport): bool
    {
        try {
            $caseId = $input['case_id'];
            $input['date'] = Carbon::parse($input['date'])->format('Y-m-d H:i:s');
            $patientId = PatientCase::whereCaseId($caseId)->first()->patient_id;
            $input['patient_id'] = $patientId;
            $deathReport->update($input);

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
