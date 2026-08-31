<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\AdvancedPayment;
use App\Models\Appointment;
use App\Models\BedAssign;
use App\Models\Bill;
use App\Models\BirthReport;
use App\Models\DeathReport;
use App\Models\Doctor;
use App\Models\EmployeePayroll;
use App\Models\InvestigationReport;
use App\Models\Invoice;
use App\Models\IpdPatientDepartment;
use App\Models\MultiTenant;
use App\Models\OperationReport;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\PatientCase;
use App\Models\Prescription;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Class UserAPIController
 */
class UserAPIController extends AppBaseController
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function editProfile(): JsonResponse
    {

        $user = Auth::user();
        $userData = new ProfileResource($user);

        return $this->sendResponse($userData, 'Profile Data Retrieved successfully.');
    }

    public function updateProfile(UpdateUserProfileRequest $request): JsonResponse
    {
        $input = $request->all();
        $input['region_code'] = regionCode($input['region_code']);
        $updateUser = $this->userRepository->profileApiUpdate($input);
        $newData = new ProfileResource($updateUser);

        return $this->sendResponse($newData, 'Profile Updated successfully');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $input = $request->all();
        try {
            $this->userRepository->changePassword($input);

            return $this->sendSuccess(__('messages.user.password').' '.__('messages.common.updated_successfully'));
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    public function getProfile(): JsonResponse
    {
        $user = User::where('id', getLoggedInUserId())->first();

        return $this->sendResponse($user->prepareData(), 'User profile get successfully');
    }

    public function deleteUser(): JsonResponse
    {
        $user = User::where('id', getLoggedInUserId())->first();

        if (empty($user)) {
            return $this->sendError('User not found.');
        }
        if ($user->hasRole('Super Admin')) {
            return $this->sendError('You can\'t delete this account.');
        }

        if ($user->department_id == 2) {
            $doctorModels = [
                PatientCase::class,
                PatientAdmission::class,
                Schedule::class,
                Appointment::class,
                BirthReport::class,
                DeathReport::class,
                InvestigationReport::class,
                OperationReport::class,
                Prescription::class,
                IpdPatientDepartment::class,
            ];
            $result = canDelete($doctorModels, 'doctor_id', $user->owner_id);
            $empPayRollResult = canDeletePayroll(
                EmployeePayroll::class,
                'owner_id',
                $user->owner_id,
                $user->owner_type
            );
            if ($result || $empPayRollResult) {
                return $this->sendError('This Account cannot delete.');
            }
            Doctor::whereId($user->owner_id)->delete();
        } elseif ($user->department_id == 3) {
            $patientModels = [
                BirthReport::class,
                DeathReport::class,
                InvestigationReport::class,
                OperationReport::class,
                Appointment::class,
                BedAssign::class,
                PatientAdmission::class,
                PatientCase::class,
                Bill::class,
                Invoice::class,
                AdvancedPayment::class,
                Prescription::class,
                IpdPatientDepartment::class,
            ];
            $result = canDelete($patientModels, 'patient_id', $user->owner_id);
            if ($result) {
                return $this->sendError('This Account cannot delete.');
            }
            Patient::whereId($user->owner_id)->delete();
        }
        if (getLoggedInUser()->hasRole('Admin')) {
            $tenant = MultiTenant::where('id', $user->tenant_id);
            Doctor::whereNotNull('id')->where('tenant_id', $user->tenant_id)->delete();
            $tenant->delete();
        }
        $user->clearMediaCollection(User::COLLECTION_PROFILE_PICTURES);
        $user->delete($user->id);

        return $this->sendSuccess(__('messages.flash.user_deleted'));
    }
}
