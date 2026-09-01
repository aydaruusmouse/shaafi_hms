<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Repositories\DashboardRepository;
use Filament\Widgets\ChartWidget;

class AppointmentTrendChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin');
    }

    public function getHeading(): string
    {
        return __('messages.dashboard.activity_last_7_days');
    }

    protected function getData(): array
    {
        $stats = app(DashboardRepository::class)->getHospitalDashboardStats();

        return [
            'datasets' => [
                [
                    'label' => __('messages.appointments'),
                    'data' => $stats['appointmentTrendData'],
                    'backgroundColor' => 'rgba(101, 113, 255, 0.18)',
                    'borderColor' => '#6571ff',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                    'pointBackgroundColor' => '#6571ff',
                    'pointRadius' => 3,
                ],
                [
                    'label' => __('messages.opd_patients'),
                    'data' => $stats['opdTrendData'],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => false,
                    'pointRadius' => 3,
                ],
                [
                    'label' => __('messages.ipd_patients'),
                    'data' => $stats['ipdTrendData'],
                    'backgroundColor' => 'rgba(249, 115, 22, 0.12)',
                    'borderColor' => '#f97316',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => false,
                    'pointRadius' => 3,
                ],
            ],
            'labels' => $stats['appointmentTrendLabels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
