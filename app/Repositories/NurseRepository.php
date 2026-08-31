<?php

namespace App\Repositories;

use App\Models\Address;
use App\Models\Department;
use App\Models\Nurse;
use App\Models\User;
use Exception;
use Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class NurseRepository
 *
 * @version February 13, 2020, 11:18 am UTC
 */
class NurseRepository extends BaseRepository
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
        return Nurse::class;
    }

    public function store(array $input, bool $mail = true)
    {
        try {
            $input['department_id'] = Department::whereName('Nurse')->first()->id;
            $input['password'] = Hash::make($input['password']);
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;

            $input['tenant_id'] = getLoggedInUser()->tenant_id;
            $user = User::create($input);

            $nurse = Nurse::create(['user_id' => $user->id]);
            $ownerId = $nurse->id;
            $ownerType = Nurse::class;

            if (! empty($address = Address::prepareAddressArray($input))) {
                Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
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
    public function update($nurse, $input)
    {
        try {
            $user = User::find($nurse->user->id);
            /** @var Nurse $nurse */
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            $nurse->user->update($input);
            $nurse->update($input);

            if (! empty($nurse->address)) {
                if (empty($address = Address::prepareAddressArray($input))) {
                    $nurse->address->delete();
                }
                $nurse->address->update($input);
            } else {
                if (! empty($address = Address::prepareAddressArray($input)) && empty($nurse->address)) {
                    $ownerId = $nurse->id;
                    $ownerType = Nurse::class;
                    Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
                }
            }

            return $user;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
