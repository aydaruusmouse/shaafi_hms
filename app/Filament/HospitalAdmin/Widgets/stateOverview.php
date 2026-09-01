<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedResource;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\AdvancedPaymentResource;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource;
use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentResource;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\OpdPatientResource;
use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\PathologyTestResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Users\Resources\NurseResource;
use App\Models\AdvancedPayment;
use App\Models\Bill;
use App\Models\Payment;
use App\Repositories\DashboardRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class stateOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.hospital-admin.widgets.dashboard-state';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    protected function getViewData(): array
    {
        $stats = app(DashboardRepository::class)->getHospitalDashboardStats();

        $invoiceAmount = totalAmount();
        $billAmount = Bill::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
        $paymentAmount = Payment::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
        $advancePaymentAmount = AdvancedPayment::whereTenantId(getLoggedInUser()->tenant_id)->sum('amount');
        $currency = getCurrencySymbol();

        return array_merge($stats, [
            'invoiceAmount' => $currency.' '.formatCurrency($invoiceAmount),
            'billAmount' => $currency.' '.formatCurrency($billAmount),
            'paymentAmount' => $currency.' '.formatCurrency($paymentAmount),
            'advancePaymentAmount' => $currency.' '.formatCurrency($advancePaymentAmount),
            'todayPaymentsFormatted' => $currency.' '.formatCurrency($stats['todayPayments'] ?? 0),
            'urls' => [
                'appointments' => $this->safeUrl(AppointmentResource::class),
                'opd' => $this->safeUrl(OpdPatientResource::class),
                'ipd' => $this->safeUrl(IpdPatientResource::class),
                'patients' => $this->safeUrl(PatientResource::class),
                'doctors' => $this->safeUrl(DoctorResource::class),
                'nurses' => $this->safeUrl(NurseResource::class),
                'beds' => $this->safeUrl(BedResource::class),
                'pathology' => $this->safeUrl(PathologyTestResource::class),
                'invoices' => $this->safeUrl(InvoiceResource::class),
                'bills' => $this->safeUrl(BillResource::class),
                'payments' => $this->safeUrl(PaymentResource::class),
                'advancePayments' => $this->safeUrl(AdvancedPaymentResource::class),
            ],
        ]);
    }

    private function safeUrl(string $resource): ?string
    {
        try {
            return $resource::getUrl('index');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
