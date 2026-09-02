@php
    $latest = $chart['latest'] ?? null;
    $deviceOptions = \App\Models\IpdOxygenMonitoring::deviceOptions();
@endphp

<div class="space-y-4">
    @if ($latest)
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-3">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ __('messages.ipd_oxygen_monitoring.title') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('messages.ipd_oxygen_monitoring.chart_description') }}
                </p>
            </div>

            <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <div class="text-xs text-gray-500">{{ __('messages.ipd_oxygen_monitoring.latest_spo2') }}</div>
                    <div class="text-lg font-semibold" style="color: {{ $chart['points'][array_key_last($chart['points'])]['color'] ?? '#111827' }}">
                        {{ $latest->spo2 !== null ? $latest->spo2.'%' : __('messages.common.n/a') }}
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <div class="text-xs text-gray-500">{{ __('messages.ipd_oxygen_monitoring.delivery_device') }}</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $deviceOptions[$latest->delivery_device] ?? __('messages.common.n/a') }}
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <div class="text-xs text-gray-500">{{ __('messages.ipd_oxygen_monitoring.flow_rate') }}</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $latest->flow_rate !== null ? rtrim(rtrim(number_format((float) $latest->flow_rate, 2, '.', ''), '0'), '.').' L/min' : __('messages.common.n/a') }}
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800">
                    <div class="text-xs text-gray-500">{{ __('messages.ipd_oxygen_monitoring.fio2') }}</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $latest->fio2 !== null ? $latest->fio2.'%' : __('messages.common.n/a') }}
                    </div>
                </div>
            </div>

            <svg viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" class="h-56 w-full" role="img" aria-label="{{ __('messages.ipd_oxygen_monitoring.title') }}">
                <rect
                    x="{{ $chart['left'] }}"
                    y="{{ $chart['targetYMin'] }}"
                    width="{{ $chart['plotWidth'] }}"
                    height="{{ max(0, $chart['targetYMax'] - $chart['targetYMin']) }}"
                    fill="#10b981"
                    fill-opacity="0.12"
                />

                @foreach ($chart['grid'] as $tick)
                    <line
                        x1="{{ $chart['left'] }}"
                        y1="{{ $tick['y'] }}"
                        x2="{{ $chart['left'] + $chart['plotWidth'] }}"
                        y2="{{ $tick['y'] }}"
                        stroke="{{ $tick['emphasis'] ? '#cbd5e1' : '#e2e8f0' }}"
                        stroke-dasharray="{{ in_array($tick['value'], [88, 94], true) ? '4 4' : '0' }}"
                    />
                    <text x="{{ $chart['left'] - 8 }}" y="{{ $tick['y'] + 4 }}" text-anchor="end" font-size="10" fill="#64748b">{{ $tick['value'] }}</text>
                @endforeach

                @if ($chart['polyline'] !== '')
                    <polyline
                        points="{{ $chart['polyline'] }}"
                        fill="none"
                        stroke="#6571ff"
                        stroke-width="2.5"
                        stroke-linejoin="round"
                        stroke-linecap="round"
                    />
                @endif

                @foreach ($chart['points'] as $point)
                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4.5" fill="{{ $point['color'] }}" stroke="#ffffff" stroke-width="1.5">
                        <title>{{ $point['label'] }} — {{ $point['spo2'] }}%</title>
                    </circle>
                @endforeach

                @if (count($chart['points']))
                    @php
                        $first = $chart['points'][0];
                        $lastPoint = $chart['points'][array_key_last($chart['points'])];
                    @endphp
                    <text x="{{ $first['x'] }}" y="{{ $chart['height'] - 10 }}" text-anchor="start" font-size="10" fill="#64748b">{{ $first['label'] }}</text>
                    @if (count($chart['points']) > 1)
                        <text x="{{ $lastPoint['x'] }}" y="{{ $chart['height'] - 10 }}" text-anchor="end" font-size="10" fill="#64748b">{{ $lastPoint['label'] }}</text>
                    @endif
                @endif
            </svg>
        </div>
    @endif

    {{ $this->table }}
</div>
