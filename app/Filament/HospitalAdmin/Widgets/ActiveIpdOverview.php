<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Filament\HospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\IpdPatientDepartment;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveIpdOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Admin') && getModuleAccess('IPD Patients');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('messages.dashboard.active_inpatients'))
            ->query(
                IpdPatientDepartment::query()
                    ->with(['patient.user', 'doctor.user', 'bed'])
                    ->where('tenant_id', getLoggedInUser()->tenant_id)
                    ->where('is_discharge', 0)
                    ->latest('admission_date')
            )
            ->columns([
                TextColumn::make('ipd_number')
                    ->label(__('messages.ipd_patient.ipd_number'))
                    ->badge()
                    ->color('warning')
                    ->url(fn ($record) => IpdPatientResource::getUrl('view', ['record' => $record->id])),
                TextColumn::make('patient.user.full_name')
                    ->label(__('messages.case.patient'))
                    ->color('primary')
                    ->url(fn ($record) => $record->patient_id ? PatientResource::getUrl('view', ['record' => $record->patient_id]) : null),
                TextColumn::make('bed.name')
                    ->label(__('messages.bed_assign.bed'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('doctor.user.full_name')
                    ->label(__('messages.case.doctor'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('admission_date')
                    ->label(__('messages.ipd_patient.admission_date'))
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('jS M, Y') : __('messages.common.n/a')),
            ])
            ->paginated([5])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
