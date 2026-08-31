<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\Department;
use App\Models\Pharmacist;
use App\Models\User;
use Exception;
use Hash;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class PharmacistRepository
 *
 * @version February 14, 2020, 9:32 am UTC
 */
class PharmacistRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'user_id',
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
        return Pharmacist::class;
    }

    public function store(array $input, bool $mail = true)
    {
        try {
            $input['department_id'] = Department::whereName('Pharmacist')->first()->id;
            $input['password'] = Hash::make($input['password']);
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            if (! empty(getSuperAdminSettingValue()['default_language']->value)) {
                $input['language'] = getSuperAdminSettingValue()['default_language']->value;
            }
            $input['tenant_id'] = getLoggedInUser()->tenant_id;
            $user = User::create($input);

            $pharmacist = Pharmacist::create(['user_id' => $user->id, 'tenant_id' => $input['tenant_id']]);
            $ownerId = $pharmacist->id;
            $ownerType = Pharmacist::class;

            if (! empty($address = Address::prepareAddressArray($input))) {
                Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType, 'tenant_id' => $input['tenant_id']]));
            }

            $user->update(['owner_id' => $ownerId, 'owner_type' => $ownerType]);
            $user->assignRole($input['department_id']);
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return $user;
    }

    public function update($input, $pharmacist)
    {
        try {
            $user = User::find($pharmacist->user->id);
            /** @var Pharmacist $pharmacist */
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            $pharmacist->user->update($input);
            $pharmacist->update($input);

            if (! empty($pharmacist->address)) {
                if (empty($address = Address::prepareAddressArray($input))) {
                    $pharmacist->address->delete();
                }
                $pharmacist->address->update($input);
            } else {
                if (! empty($address = Address::prepareAddressArray($input)) && empty($pharmacist->address)) {
                    $ownerId = $pharmacist->id;
                    $ownerType = Pharmacist::class;
                    Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
                }
            }
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return $user;
    }
}
