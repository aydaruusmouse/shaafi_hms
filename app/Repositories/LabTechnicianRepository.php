<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\Department;
use App\Models\LabTechnician;
use App\Models\User;
use Exception;
use Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class LabTechnicianRepository
 *
 * @version February 14, 2020, 5:19 am UTC
 */
class LabTechnicianRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'full_name',
        'email',
        'phone',
        'education',
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
        return LabTechnician::class;
    }

    public function store(array $input, bool $mail = true)
    {
        try {
            $input['department_id'] = Department::whereName('Lab Technician')->first()->id;
            $input['password'] = Hash::make($input['password']);
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;

            if (! empty(getSuperAdminSettingValue()['default_language']->value)) {
                $input['language'] = getSuperAdminSettingValue()['default_language']->value;
            }
            $input['tenant_id'] = getLoggedInUser()->tenant_id;
            $user = User::create($input);

            $labTechnician = LabTechnician::create(['user_id' => $user->id, 'tenant_id' => $input['tenant_id']]);

            $ownerId = $labTechnician->id;
            $ownerType = LabTechnician::class;

            if (! empty($address = Address::prepareAddressArray($input))) {
                Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType, 'tenant_id' => $input['tenant_id']]));
            }

            $user->update(['owner_id' => $ownerId, 'owner_type' => $ownerType]);
            $user->assignRole($input['department_id']);

            return $user;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return bool|Builder|Builder[]|Collection|Model
     */
    public function update($labTechnician, $input)
    {
        try {
            $input['status'] = isset($input['status']) ? 1 : 0;

            $user = User::find($labTechnician->user->id);

            /** @var LabTechnician $labTechnician */
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            $labTechnician->user->update($input);
            $labTechnician->update($input);

            if (! empty($labTechnician->address)) {
                if (empty($address = Address::prepareAddressArray($input))) {
                    $labTechnician->address->delete();
                }
                $labTechnician->address->update($input);
            } else {
                if (! empty($address = Address::prepareAddressArray($input)) && empty($labTechnician->address)) {
                    $ownerId = $labTechnician->id;
                    $ownerType = LabTechnician::class;
                    Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
                }
            }

            return $user;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
