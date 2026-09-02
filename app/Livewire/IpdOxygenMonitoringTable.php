<?php

namespace App\Livewire;

use App\Models\IpdOxygenMonitoring;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdOxygenMonitoringTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function formFields(): array
    {
        return [
            Hidden::make('ipd_patient_department_id')->default($this->id),
            DateTimePicker::make('recorded_at')
                ->label(__('messages.ipd_oxygen_monitoring.recorded_at'))
                ->native(false)
                ->default(now())
                ->required(),
            TextInput::make('spo2')
                ->label(__('messages.ipd_oxygen_monitoring.spo2'))
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->required(),
            Select::make('delivery_device')
                ->label(__('messages.ipd_oxygen_monitoring.delivery_device'))
                ->options(IpdOxygenMonitoring::deviceOptions())
                ->native(false)
                ->live()
                ->required(),
            TextInput::make('flow_rate')
                ->label(__('messages.ipd_oxygen_monitoring.flow_rate'))
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('L/min')
                ->visible(fn (Get $get) => $get('delivery_device') && $get('delivery_device') !== IpdOxygenMonitoring::DEVICE_ROOM_AIR),
            TextInput::make('fio2')
                ->label(__('messages.ipd_oxygen_monitoring.fio2'))
                ->numeric()
                ->minValue(21)
                ->maxValue(100)
                ->suffix('%')
                ->visible(fn (Get $get) => $get('delivery_device') && $get('delivery_device') !== IpdOxygenMonitoring::DEVICE_ROOM_AIR),
            TextInput::make('respiratory_rate')
                ->label(__('messages.ipd_oxygen_monitoring.respiratory_rate'))
                ->numeric()
                ->minValue(1)
                ->maxValue(80),
            Select::make('target_spo2')
                ->label(__('messages.ipd_oxygen_monitoring.target_spo2'))
                ->options(IpdOxygenMonitoring::targetOptions())
                ->native(false)
                ->default(IpdOxygenMonitoring::TARGET_STANDARD),
            Select::make('nurse_id')
                ->label(__('messages.ipd_patient_nurse_note.nurse'))
                ->options(fn () => getNurseSelectOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->default(fn () => auth()->user()?->hasRole('Nurse') ? auth()->user()->owner_id : null)
                ->required(),
            Textarea::make('notes')
                ->label(__('messages.ipd_oxygen_monitoring.notes'))
                ->rows(2),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('messages.ipd_oxygen_monitoring.readings'))
            ->description(__('messages.ipd_oxygen_monitoring.description'))
            ->paginated([10, 25, 50])
            ->headerActions([
                Actions\CreateAction::make()
                    ->createAnother(false)
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Vital Information', 'create'))
                    ->form($this->formFields())
                    ->modalHeading(__('messages.ipd_oxygen_monitoring.new_record'))
                    ->label(__('messages.ipd_oxygen_monitoring.new_record'))
                    ->mutateFormDataUsing(fn (array $data) => $this->prepareData($data))
                    ->successNotificationTitle(__('messages.ipd_oxygen_monitoring.saved')),
            ])
            ->query(
                IpdOxygenMonitoring::query()
                    ->where('ipd_patient_department_id', $this->id)
                    ->orderByDesc('recorded_at')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('recorded_at')
                    ->label(__('messages.ipd_oxygen_monitoring.recorded_at'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y h:i A') : __('messages.common.n/a'))
                    ->sortable(),
                TextColumn::make('spo2')
                    ->label(__('messages.ipd_oxygen_monitoring.spo2'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state !== null ? $state.'%' : __('messages.common.n/a'))
                    ->color(fn ($state) => IpdOxygenMonitoring::spo2Color($state !== null ? (int) $state : null)),
                TextColumn::make('delivery_device')
                    ->label(__('messages.ipd_oxygen_monitoring.delivery_device'))
                    ->formatStateUsing(fn ($state) => IpdOxygenMonitoring::deviceOptions()[$state] ?? __('messages.common.n/a')),
                TextColumn::make('flow_rate')
                    ->label(__('messages.ipd_oxygen_monitoring.flow_rate'))
                    ->formatStateUsing(fn ($state) => $state !== null ? rtrim(rtrim(number_format((float) $state, 2, '.', ''), '0'), '.').' L/min' : __('messages.common.n/a')),
                TextColumn::make('fio2')
                    ->label(__('messages.ipd_oxygen_monitoring.fio2'))
                    ->formatStateUsing(fn ($state) => $state !== null ? $state.'%' : __('messages.common.n/a')),
                TextColumn::make('respiratory_rate')
                    ->label(__('messages.ipd_oxygen_monitoring.rr_short'))
                    ->formatStateUsing(fn ($state) => $state !== null ? $state : __('messages.common.n/a')),
                TextColumn::make('target_spo2')
                    ->label(__('messages.ipd_oxygen_monitoring.target_spo2'))
                    ->formatStateUsing(fn ($state) => IpdOxygenMonitoring::targetOptions()[$state] ?? ($state ?: __('messages.common.n/a'))),
                TextColumn::make('nurse.user.full_name')
                    ->label(__('messages.ipd_patient_nurse_note.nurse'))
                    ->default(__('messages.common.n/a'))
                    ->searchable(),
                TextColumn::make('notes')
                    ->label(__('messages.ipd_oxygen_monitoring.notes'))
                    ->limit(40)
                    ->default(__('messages.common.n/a')),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->iconButton()
                    ->modalWidth('lg')
                    ->visible(fn () => hasModulePermission('IPD Vital Information', 'edit'))
                    ->form($this->formFields())
                    ->mutateFormDataUsing(fn (array $data) => $this->prepareData($data))
                    ->successNotificationTitle(__('messages.ipd_oxygen_monitoring.updated')),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(fn () => hasModulePermission('IPD Vital Information', 'delete'))
                    ->successNotificationTitle(__('messages.ipd_oxygen_monitoring.deleted')),
            ])
            ->emptyStateHeading(__('messages.ipd_oxygen_monitoring.empty'))
            ->emptyStateDescription(__('messages.ipd_oxygen_monitoring.empty_desc'));
    }

    public function chartData(): array
    {
        $readings = IpdOxygenMonitoring::query()
            ->where('ipd_patient_department_id', $this->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->limit(48)
            ->get()
            ->reverse()
            ->values();

        $latest = $readings->last();
        $width = 800;
        $height = 220;
        $left = 48;
        $right = 16;
        $top = 16;
        $bottom = 36;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $minSpo2 = 70;
        $maxSpo2 = 100;

        $yFor = function ($spo2) use ($top, $plotHeight, $minSpo2, $maxSpo2) {
            $clamped = max($minSpo2, min($maxSpo2, (float) $spo2));

            return $top + (($maxSpo2 - $clamped) / ($maxSpo2 - $minSpo2)) * $plotHeight;
        };

        $points = [];
        $count = $readings->count();

        foreach ($readings as $index => $reading) {
            $x = $count === 1
                ? $left + ($plotWidth / 2)
                : $left + ($index / max($count - 1, 1)) * $plotWidth;

            $points[] = [
                'x' => round($x, 1),
                'y' => round($yFor($reading->spo2 ?? $minSpo2), 1),
                'spo2' => $reading->spo2,
                'label' => $reading->recorded_at?->format('j M H:i') ?? '',
                'color' => match (IpdOxygenMonitoring::spo2Color($reading->spo2 !== null ? (int) $reading->spo2 : null)) {
                    'success' => '#10b981',
                    'warning' => '#f59e0b',
                    'danger' => '#ef4444',
                    default => '#94a3b8',
                },
            ];
        }

        $target = $latest?->target_spo2 ?: IpdOxygenMonitoring::TARGET_STANDARD;
        [$targetMin, $targetMax] = array_pad(array_map('intval', explode('-', (string) $target)), 2, 98);
        $gridValues = [100, 98, 94, 92, 88, 80, 70];

        return [
            'latest' => $latest,
            'width' => $width,
            'height' => $height,
            'left' => $left,
            'plotWidth' => $plotWidth,
            'points' => $points,
            'polyline' => collect($points)->map(fn ($point) => $point['x'].','.$point['y'])->implode(' '),
            'targetYMin' => round($yFor($targetMax), 1),
            'targetYMax' => round($yFor($targetMin), 1),
            'grid' => collect($gridValues)->map(fn ($tick) => [
                'value' => $tick,
                'y' => round($yFor($tick), 1),
                'emphasis' => in_array($tick, [88, 92, 94, 98], true),
            ])->all(),
        ];
    }

    public function render()
    {
        return view('livewire.ipd-oxygen-monitoring-table', [
            'chart' => $this->chartData(),
        ]);
    }

    private function prepareData(array $data): array
    {
        $data['ipd_patient_department_id'] = $this->id;

        if (($data['delivery_device'] ?? null) === IpdOxygenMonitoring::DEVICE_ROOM_AIR) {
            $data['flow_rate'] = null;
            $data['fio2'] = 21;
        }

        return $data;
    }
}
