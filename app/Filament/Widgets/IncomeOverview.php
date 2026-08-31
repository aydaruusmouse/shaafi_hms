<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DB;
use Filament\Widgets\ChartWidget;

class IncomeOverview extends ChartWidget
{
    public function getHeading(): string
    {
        return __('messages.dashboard.income_report');
    }

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '400px';

    protected static ?int $sort = 2;

    public ?string $filter = 'this_month';

    public function totalFilterDay($startDate, $endDate): array
    {
        $transactions = Transaction::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total_amount'))
            ->where('status', Transaction::APPROVED)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->groupBy('date')
            ->get();

        $transactionMap = [];
        foreach ($transactions as $transaction) {
            $transactionMap[$transaction->date] = $transaction->total_amount;
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $dateArr = [];
        $income = [];

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $dateArr[] = $date->format('d-m-y');
            $income[] = $transactionMap[$dateKey] ?? 0;
        }

        $data['days'] = $dateArr;
        $data['income'] = [
            'label' => trans('messages.income').' ('.superAdminCurrency().')',
            'data' => $income,
            'borderWidth' => 1,

        ];

        return $data;
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $start_date = null;
        $end_date = null;

        if ($activeFilter == 'today') {
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
        } elseif ($activeFilter == 'yesterday') {
            $start_date = Carbon::yesterday()->format('Y-m-d');
            $end_date = Carbon::today()->format('Y-m-d');
        } elseif ($activeFilter == 'last_7_days') {
            $start_date = Carbon::now()->subDays(7)->format('Y-m-d');
            $end_date = Carbon::today()->format('Y-m-d');
        } elseif ($activeFilter == 'last_30_days') {
            $start_date = Carbon::now()->subDays(30)->format('Y-m-d');
            $end_date = Carbon::today()->format('Y-m-d');
        } elseif ($activeFilter == 'this_month') {
            $start_date = Carbon::now()->startOfMonth()->format('Y-m-d');
            $end_date = Carbon::today()->format('Y-m-d');
        } elseif ($activeFilter == 'last_month') {
            $start_date = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
            $end_date = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        }

        if ($start_date && $end_date) {
            $report = $this->totalFilterDay($start_date, $end_date);
        } else {
            $report = [
                'days' => [],
                'income' => [
                    'label' => 'Income',
                    'data' => [],
                ],
            ];
        }

        return [
            'datasets' => [$report['income']],
            'labels' => $report['days'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'today' => __('messages.appointment.today'),
            'yesterday' => __('messages.appointment.yesterday'),
            'last_7_days' => __('messages.appointment.last_7_days'),
            'last_30_days' => __('messages.appointment.last_30_days'),
            'this_month' => __('messages.appointment.this_month'),
            'last_month' => __('messages.appointment.last_month'),
        ];
    }
}
