<?php

namespace App\Repositories;

use App\Models\Accountant;
use App\Models\Address;
use App\Models\Department;
use App\Models\User;
use Exception;
use Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class AccountantRepository
 *
 * @version February 17, 2020, 5:34 am UTC
 */
class AccountantRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'full_name',
        'email',
        'phone',
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
        return Accountant::class;
    }

    public function store(array $input, bool $mail = true)
    {
        try {
            $input['department_id'] = Department::whereName('Accountant')->first()->id;
            $input['password'] = Hash::make($input['password']);
            /** @var User $user */
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            if (! empty(getSuperAdminSettingValue()['default_language']->value)) {
                $input['language'] = getSuperAdminSettingValue()['default_language']->value;
            }
            $input['tenant_id'] = getLoggedInUser()->tenant_id;
            $user = User::create($input);
            if ($mail) {
                $user->sendEmailVerificationNotification();
            }
            $accountant = Accountant::create(['user_id' => $user->id, 'tenant_id' => $input['tenant_id']]);
            $ownerId = $accountant->id;
            $ownerType = Accountant::class;

            if (! empty($address = Address::prepareAddressArray($input))) {
                Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType, 'tenant_id' => $input['tenant_id']]));
            }

            $user->update(['owner_id' => $ownerId, 'owner_type' => $ownerType]);
            $user->assignRole($input['department_id']);

            return $accountant;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return bool|Builder|Builder[]|Collection|Model
     */
    public function update($accountant, $input)
    {
        try {

            /** @var User $user */
            $user = User::find($accountant->user->id);

            /** @var Accountant $accountant */
            // $input['phone'] = preparePhoneNumber($input, 'phone');
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            $accountant->user->update($input);
            $accountant->update($input);

            if (! empty($accountant->address)) {
                if (empty($address = Address::prepareAddressArray($input))) {
                    $accountant->address->delete();
                }
                $accountant->address->update($input);
            } else {
                if (! empty($address = Address::prepareAddressArray($input)) && empty($accountant->address)) {
                    $ownerId = $accountant->id;
                    $ownerType = Accountant::class;
                    Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
                }
            }

            return $accountant;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
