<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Admin', 'Doctor', 'Receptionist', 'Patient']);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->hasRole(['Admin', 'Doctor', 'Receptionist', 'Patient']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['Admin', 'Doctor', 'Receptionist']);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasRole(['Admin', 'Doctor', 'Receptionist']);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->hasRole(['Admin', 'Doctor', 'Receptionist']);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->hasRole(['Admin']);
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->hasRole(['Admin']);
    }
}
