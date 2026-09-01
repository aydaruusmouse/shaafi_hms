<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Expense;
use App\Models\Income;
use App\Models\IpdPatientDepartment;
use App\Models\Nurse;
use App\Models\OpdPatientDepartment;
use App\Models\PathologyTest;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Exception;

/**
 * Class DashboardRepository
 */
class DashboardRepository
{
    /**
     * @throws Exception
     */
    public function getIncomeExpenseReport(array $input): array
    {
        $dates = $this->getDate($input['start_date'], $input['end_date']);

        $incomes = Income::all();
        $expenses = Expense::all();

        // Income report
        $data = [];
        foreach ($dates['dateArr'] as $cDate) {
            $incomeTotal = 0;
            foreach ($incomes as $row) {
                $chartDates = $cDate;
                $incomeDates = trim(substr($row['date'], 0, 10));
                if ($chartDates == $incomeDates) {
                    $incomeTotal += $row['amount'];
                }
            }
            $incomeTotalArray[] = $incomeTotal;
            $dateArray[] = $cDate;
        }

        // Expense report
        foreach ($dates['dateArr'] as $cDate) {
            $expenseTotal = 0;
            foreach ($expenses as $row) {
                $chartDates = $cDate;
                $expenseDates = trim(substr($row['date'], 0, 10));
                if ($chartDates == $expenseDates) {
                    $expenseTotal += $row['amount'];
                }
            }
            $expenseTotalArray[] = $expenseTotal;
        }

        $data['incomeTotal'] = $incomeTotalArray;
        $data['expenseTotal'] = $expenseTotalArray;
        $data['date'] = $dateArray;

        return $data;
    }

    /**
     * @throws Exception
     */
    public function getDate(string $startDate, string $endDate): array
    {
        $dateArr = [];
        $subStartDate = '';
        $subEndDate = '';
        if (! ($startDate && $endDate)) {
            $data = [
                'dateArr' => $dateArr,
                'startDate' => $subStartDate,
                'endDate' => $subEndDate,
            ];

            return $data;
        }
        $end = trim(substr($endDate, 0, 10));
        $start = Carbon::parse($startDate)->toDateString();
        /** @var \Illuminate\Support\Carbon $startDate */
        $startDate = Carbon::createFromFormat('Y-m-d', $start);
        /** @var \Illuminate\Support\Carbon $endDate */
        $endDate = Carbon::createFromFormat('Y-m-d', $end);

        while ($startDate <= $endDate) {
            $dateArr[] = $startDate->copy()->format('Y-m-d');
            $startDate->addDay();
        }
        $start = current($dateArr);
        $endDate = end($dateArr);
        $subStartDate = Carbon::parse($start)->startOfDay()->format('Y-m-d H:i:s');
        $subEndDate = Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');

        $data = [
            'dateArr' => $dateArr,
            'startDate' => $subStartDate,
            'endDate' => $subEndDate,
        ];

        return $data;
    }

    public function getHospitalDashboardStats(): array
    {
        static $stats = null;
        if ($stats !== null) {
            return $stats;
        }
        $tenantId = getLoggedInUser()->tenant_id;
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $weekStart = Carbon::today()->subDays(6)->startOfDay();

        $totalBeds = Bed::where('tenant_id', $tenantId)->count();
        $availableBeds = Bed::where('tenant_id', $tenantId)->where('is_available', 1)->count();
        $occupiedBeds = max(0, $totalBeds - $availableBeds);

        $appointmentTrend = Appointment::where('tenant_id', $tenantId)
            ->where('is_completed', '!=', Appointment::STATUS_CANCELLED)
            ->whereBetween('opd_date', [$weekStart, $todayEnd])
            ->selectRaw('DATE(opd_date) as day, COUNT(*) as total')
            ->groupByRaw('DATE(opd_date)')
            ->pluck('total', 'day');

        $opdTrend = OpdPatientDepartment::where('tenant_id', $tenantId)
            ->whereBetween('appointment_date', [$weekStart, $todayEnd])
            ->selectRaw('DATE(appointment_date) as day, COUNT(*) as total')
            ->groupByRaw('DATE(appointment_date)')
            ->pluck('total', 'day');

        $ipdTrend = IpdPatientDepartment::where('tenant_id', $tenantId)
            ->whereBetween('admission_date', [$weekStart, $todayEnd])
            ->selectRaw('DATE(admission_date) as day, COUNT(*) as total')
            ->groupByRaw('DATE(admission_date)')
            ->pluck('total', 'day');

        $labels = [];
        $appointmentData = [];
        $opdData = [];
        $ipdData = [];
        foreach (CarbonPeriod::create($weekStart, $todayEnd) as $date) {
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('D d');
            $appointmentData[] = (int) ($appointmentTrend[$key] ?? 0);
            $opdData[] = (int) ($opdTrend[$key] ?? 0);
            $ipdData[] = (int) ($ipdTrend[$key] ?? 0);
        }

        $data = [
            'greetingName' => getLoggedInUser()->first_name ?? getLoggedInUser()->full_name,
            'todayLabel' => Carbon::now()->format('l, jS M Y'),
            'patients' => Patient::where('tenant_id', $tenantId)->count(),
            'doctors' => Doctor::where('tenant_id', $tenantId)->count(),
            'nurses' => Nurse::where('tenant_id', $tenantId)->count(),
            'totalBeds' => $totalBeds,
            'availableBeds' => $availableBeds,
            'occupiedBeds' => $occupiedBeds,
            'occupancyPercent' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0,
            'todayAppointments' => Appointment::where('tenant_id', $tenantId)
                ->whereBetween('opd_date', [$todayStart, $todayEnd])
                ->where('is_completed', '!=', Appointment::STATUS_CANCELLED)
                ->count(),
            'todayOpd' => OpdPatientDepartment::where('tenant_id', $tenantId)
                ->whereBetween('appointment_date', [$todayStart, $todayEnd])
                ->count(),
            'todayIpd' => IpdPatientDepartment::where('tenant_id', $tenantId)
                ->whereBetween('admission_date', [$todayStart, $todayEnd])
                ->count(),
            'activeOpd' => OpdPatientDepartment::where('tenant_id', $tenantId)->where('is_discharge', 0)->count(),
            'activeIpd' => IpdPatientDepartment::where('tenant_id', $tenantId)->where('is_discharge', 0)->count(),
            'pendingLabTests' => PathologyTest::where('tenant_id', $tenantId)->where('status', 0)->count(),
            'todayPayments' => Payment::where('tenant_id', $tenantId)
                ->whereBetween('payment_date', [$todayStart, $todayEnd])
                ->sum('amount'),
            'appointmentTrendLabels' => $labels,
            'appointmentTrendData' => $appointmentData,
            'opdTrendData' => $opdData,
            'ipdTrendData' => $ipdData,
        ];

        return $stats = $data;
    }

    /**
     * @return int[]
     */
    public function getTotalActiveDeActiveHospitalPlans(): array
    {
        $activePlansCount = 0;
        $deActivePlansCount = 0;
        $subscriptions = Subscription::whereStatus(Subscription::ACTIVE)->get();
        foreach ($subscriptions as $sub) {
            if (! $sub->isExpired()) {   // active plans
                $activePlansCount++;
            } else {
                $deActivePlansCount++;
            }
        }

        return ['activePlansCount' => $activePlansCount, 'deActivePlansCount' => $deActivePlansCount];
    }

    public function totalFilterDay($formatStartDate, $formatEndDate): array
    {
        $transactions = Transaction::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total_amount'))
            ->where('status', Transaction::APPROVED)
            ->whereBetween('created_at', [$formatStartDate, $formatEndDate])
            ->groupBy('date')
            ->get();

        $transactionMap = [];
        foreach ($transactions as $transaction) {
            $transactionMap[$transaction->date] = $transaction->total_amount;
        }

        $period = CarbonPeriod::create($formatStartDate, $formatEndDate);
        $dateArr = [];
        $income = [];

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $dateArr[] = $date->format('d-m-y');
            $income[] = $transactionMap[$dateKey] ?? 0;
        }

        $data['days'] = $dateArr;
        $data['income'] = [
            'label' => trans('messages.income', [], getLoggedInUser()->language),
            'data' => $income,
            'fill' => 'false',
            'borderColor' => 'rgb(153, 102, 255)',
            'backgroundColor' => 'rgba(153, 102, 255, 0.2)',
            'borderWidth' => 1,
            'tension' => 0.4,
        ];

        return $data;
    }

    /**
     * @return int|mixed
     */
    public function totalFilterReport($date)
    {
        return Transaction::where('status', Transaction::APPROVED)->whereDate('created_at', $date)->sum('amount');
    }
}
