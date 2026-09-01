<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Repositories\DashboardRepository;
use Filament\Widgets\ChartWidget;

class HospitalCensusChart extends ChartWidget
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
        return __('messages.dashboard.bed_occupancy');
    }

    protected function getData(): array
    {
        $stats = app(DashboardRepository::class)->getHospitalDashboardStats();

        if ((int) $stats['totalBeds'] === 0) {
            return [
                'datasets' => [
                    [
                        'label' => __('messages.dashboard.bed_occupancy'),
                        'data' => [1],
                        'backgroundColor' => ['#e5e7eb'],
                        'borderWidth' => 0,
                    ],
                ],
                'labels' => [__('messages.common.no_data_found')],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => __('messages.dashboard.bed_occupancy'),
                    'data' => [
                        $stats['occupiedBeds'],
                        $stats['availableBeds'],
                    ],
                    'backgroundColor' => [
                        '#ef4444',
                        '#10b981',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => [
                __('messages.dashboard.occupied_beds'),
                __('messages.dashboard.available_beds'),
            ],
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
            'cutout' => '65%',
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
