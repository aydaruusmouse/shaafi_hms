<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\Department;
use App\Models\Receptionist;
use App\Models\User;
use Exception;
use Hash;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class ReceptionistRepository
 *
 * @version February 14, 2020, 9:14 am UTC
 */
class ReceptionistRepository extends BaseRepository
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
        return Receptionist::class;
    }

    public function store(array $input, bool $mail = true)
    {
        try {
            $input['department_id'] = Department::whereName('Receptionist')->first()->id;
            $input['password'] = Hash::make($input['password']);
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            if (! empty(getSuperAdminSettingValue()['default_language']->value)) {
                $input['language'] = getSuperAdminSettingValue()['default_language']->value;
            }
            $input['tenant_id'] = getLoggedInUser()->tenant_id;
            $user = User::create($input);
            $receptionist = Receptionist::create(['user_id' => $user->id, 'tenant_id' => $input['tenant_id']]);

            $ownerId = $receptionist->id;
            $ownerType = Receptionist::class;

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

    public function update($receptionist, $input)
    {
        try {
            unset($input['password']);

            $user = User::find($receptionist->user->id);

            /** @var Receptionist $receptionist */
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            $receptionist->user->update($input);
            $receptionist->update($input);

            if (! empty($receptionist->address)) {
                if (empty($address = Address::prepareAddressArray($input))) {
                    $receptionist->address->delete();
                }
                $receptionist->address->update($input);
            } else {
                if (! empty($address = Address::prepareAddressArray($input)) && empty($receptionist->address)) {
                    $ownerId = $receptionist->id;
                    $ownerType = Receptionist::class;
                    Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
                }
            }

            return $user;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
