<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user && $user->hasVerifiedEmail() && $user->status == 1) {
            $role = $user->roles()->first();
            if ($role && $role->name == User::ROLE_SUPER_ADMIN) {
                return redirect()->route('filament.superAdmin.pages.dashboard');
            }
            if ($role && $role->name == 'Doctor' && getModuleAccess('Birth Reports') && getModuleAccess('Death Reports') && getModuleAccess('Investigation Reports') && getModuleAccess('Operation Reports')) {
                return redirect()->route('filament.hospitalAdmin.reports.resources.birth-reports.index');
            } elseif ($role && $role->name == 'Doctor') {
                return redirect()->route('filament.hospitalAdmin.doctors.resources.doctors.index');
            }
            if ($role && $role->name == 'Accountant' && getModuleAccess('Expense') && getModuleAccess('Income')) {
                return redirect()->route('filament.hospitalAdmin.finance.resources.incomes.index');
            } elseif ($role && $role->name == 'Accountant') {
                return redirect()->route('filament.hospitalAdmin.doctors.resources.doctors.index');
            }

            if ($role && $role->name == User::ROLE_ADMIN) {
                return redirect()->route('filament.hospitalAdmin.pages.dashboard');
            }
            if ($role && $role->name == 'Case Manager') {
                return redirect()->route('filament.hospitalAdmin.doctors');
            }
            if ($role && $role->name == 'Receptionist') {
                return redirect()->route('filament.hospitalAdmin.patients');
            }
            if ($role && $role->name == 'Pharmacist' && getModuleAccess('Medicines') && getModuleAccess('Medicine Categories') && getModuleAccess('Medicine Brands')) {
                return redirect()->route('filament.hospitalAdmin.medicine');
            } elseif ($role && $role->name == 'Pharmacist') {
                return redirect()->route('filament.hospitalAdmin.doctors.resources.doctors.index');
            }
            if ($role && $role->name == 'Lab Technician' && getModuleAccess('Medicines') && getModuleAccess('Medicine Categories') && getModuleAccess('Medicine Brands')) {
                return redirect()->route('filament.hospitalAdmin.medicine');
            } elseif ($role && $role->name == 'Lab Technician') {
                return redirect()->route('filament.hospitalAdmin.doctors.resources.doctors.index');
            }
            if ($role && $role->name == 'Nurse') {
                return redirect()->route('filament.hospitalAdmin.bed-management');
            }
            if ($role && $role->name == 'Patient') {
                return redirect()->route('filament.hospitalAdmin.pages.dashboard');
            }
        } else {
            return redirect()->route('verification.notice');
        }
    }
}
