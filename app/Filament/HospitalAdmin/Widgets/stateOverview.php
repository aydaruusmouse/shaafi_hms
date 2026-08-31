<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Models\AdvancedPayment;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\Doctor;
use App\Models\Nurse;
use App\Models\Patient;
use App\Models\Payment;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class stateOverview extends BaseWidget
{
    // protected static ?string $heading = 'Test Chart';
    // protected static string $color = 'success';
    // protected int | string | array $columnSpan = 2;
    protected static string $view = 'filament.hospital-admin.widgets.dashboard-state';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    // protected function getStats(): array
    // {
    //     $invoiceAmount = totalAmount();
    //     $billAmount = Bill::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
    //     $paymentAmount = Payment::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
    //     $advancePaymentAmount = AdvancedPayment::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
    //     $doctors = Doctor::whereTenantId(getLoggedInUser()->tenant_id)->count();
    //     $patients = Patient::whereTenantId(getLoggedInUser()->tenant_id)->count();
    //     $nurses = Nurse::whereTenantId(getLoggedInUser()->tenant_id)->count();
    //     $availableBeds = Bed::Where('tenant_id', getLoggedInUser()->tenant_id)->whereIsAvailable(1)->count();

    //     return [
    //         getModuleAccess('Invoices') ? Stat::make(__('messages.dashboard.total_invoices'), formatCurrency($invoiceAmount))->description('32k increase')->descriptionIcon('heroicon-m-arrow-trending-up', IconPosition::Before) : null,
    //         getModuleAccess('Bills') ? Stat::make(__('messages.dashboard.total_bills'), formatCurrency($billAmount)) : null,
    //         getModuleAccess('Payments') ? Stat::make(__('messages.dashboard.total_payments'), formatCurrency($paymentAmount)) : null,
    //         getModuleAccess('Advance Payments') ? Stat::make(__('messages.dashboard.total_advance_payments'), formatCurrency($advancePaymentAmount)) : null,
    //         getModuleAccess('Beds') ? Stat::make(__('messages.dashboard.available_beds'), $availableBeds) : null,
    //         getModuleAccess('Doctors') ? Stat::make(__('messages.dashboard.doctors'), $doctors) : null,
    //         getModuleAccess('Patients') ? Stat::make(__('messages.dashboard.patients'), $patients) : null,
    //         getModuleAccess('Nurses') ? Stat::make(__('messages.nurses'), $nurses) : null,

    //     ];
    // }

    protected function getViewData(): array
    {
        $invoiceAmount = totalAmount();
        $billAmount = Bill::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
        $paymentAmount = Payment::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
        $advancePaymentAmount = AdvancedPayment::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
        $doctors = Doctor::whereTenantId(getLoggedInUser()->tenant_id)->count();
        $patients = Patient::whereTenantId(getLoggedInUser()->tenant_id)->count();
        $nurses = Nurse::whereTenantId(getLoggedInUser()->tenant_id)->count();
        $availableBeds = Bed::Where('tenant_id', getLoggedInUser()->tenant_id)->whereIsAvailable(1)->count();

        return [
            'invoiceAmount' => (formatCurrency($invoiceAmount)),
            'billAmount' => formatCurrency($billAmount),
            'paymentAmount' => formatCurrency($paymentAmount),
            'advancePaymentAmount' => formatCurrency($advancePaymentAmount),
            'doctors' => $doctors,
            'patients' => $patients,
            'nurses' => $nurses,
            'availableBeds' => $availableBeds,
        ];
    }
}
