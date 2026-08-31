<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            if (Auth::user()->hasRole('Admin')) {
                return redirect()->route('filament.hospitalAdmin.pages.dashboard');
            }
            if (Auth::user()->hasRole('Doctor')) {
                return redirect()->route('filament.hospitalAdmin.reports.resources.birth-reports.index');
            }
            if (Auth::user()->hasRole('Accountant')) {
                return redirect()->route('filament.hospitalAdmin.finance.resources.incomes.index');
            } elseif (Auth::user()->hasRole('Case Manager')) {
                return redirect()->route('filament.hospitalAdmin.doctors');
            } elseif (Auth::user()->hasRole('Super Admin')) {
                return redirect()->route('filament.superAdmin.pages.dashboard');
            } elseif (Auth::user()->hasRole('Receptionist')) {
                return redirect()->route('filament.hospitalAdmin.patients');
            } elseif (Auth::user()->hasRole('Pharmacist')) {
                return redirect()->route('filament.hospitalAdmin.medicine');
            } elseif (Auth::user()->hasRole('Lab Technician')) {
                return redirect()->route('filament.hospitalAdmin.medicine');
            } elseif (Auth::user()->hasRole('Nurse')) {
                return redirect()->route('filament.hospitalAdmin.bed-management');
            } elseif (Auth::user()->hasRole('Patient')) {
                return redirect()->route('filament.hospitalAdmin.pages.dashboard');
            }

        }

        return $next($request);
    }
}
