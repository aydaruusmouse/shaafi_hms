<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\LiveConsultation;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class patientWidgets extends BaseWidget
{
    protected static string $view = 'filament.hospital-admin.widgets.patient-state';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Patient');
    }
    // protected function getStats(): array
    // {

    //     $totalAppointments = Appointment::where('patient_id', auth()->user()->owner_id)->where('tenant_id', auth()->user()->tenant_id)->count();

    //     $todayAppointments = Appointment::where('patient_id', auth()->user()->owner_id)->where('tenant_id', auth()->user()->tenant_id)->whereBetween('opd_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->count();

    //     $totalMeeting = LiveConsultation::where('patient_id', auth()->user()->owner_id)->where('tenant_id', auth()->user()->tenant_id)->count();

    //     $billedAmmount = Bill::wherePatientId(auth()->user()->owner_id)->where('status', 1)->sum('amount');

    //     $currencySymbol = getCurrencySymbol();

    //     return [
    //         Stat::make(__('messages.patient.total_appointments'), formatCurrency($totalAppointments)),
    //         Stat::make(__('messages.lunch_break.todays_appointments'), formatCurrency($todayAppointments)),
    //         Stat::make(__('messages.lunch_break.total_meetings'), $totalMeeting),
    //         Stat::make(__('messages.dashboard.total_bills'), $currencySymbol . formatCurrency($billedAmmount)),
    //     ];
    // }

    protected function getViewData(): array
    {

        $totalAppointments = Appointment::where('patient_id', auth()->user()->owner_id)->where('tenant_id', auth()->user()->tenant_id)->count();
        $todayAppointments = Appointment::where('patient_id', auth()->user()->owner_id)->where('tenant_id', auth()->user()->tenant_id)->whereBetween('opd_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->count();
        $totalMeeting = LiveConsultation::where('patient_id', auth()->user()->owner_id)->where('tenant_id', auth()->user()->tenant_id)->count();
        $billedAmmount = Bill::wherePatientId(auth()->user()->owner_id)->where('status', 1)->sum('amount');
        $currencySymbol = getCurrencySymbol();

        return [
            'totalAppointments' => formatCurrency($totalAppointments),
            'todayAppointments' => formatCurrency($todayAppointments),
            'totalMeeting' => $totalMeeting,
            'billedAmmount' => formatCurrency($billedAmmount),
            'currencySymbol' => $currencySymbol,
        ];
    }
}
