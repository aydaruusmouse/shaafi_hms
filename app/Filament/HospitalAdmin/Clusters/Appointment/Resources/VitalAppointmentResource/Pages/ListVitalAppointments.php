<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\VitalAppointmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\VitalAppointmentResource;
use App\Models\Appointment as AppointmentModel;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVitalAppointments extends ListRecords
{
    protected static string $resource = VitalAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('messages.filter.all'))
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where(function (Builder $query) {
                        $query->where('is_completed', AppointmentModel::STATUS_IN_VITAL)
                            ->orWhereHas('vitalSigns')
                            ->orWhereIn('is_completed', [
                                AppointmentModel::STATUS_PENDING,
                                AppointmentModel::STATUS_IN_QUEUE,
                                AppointmentModel::STATUS_CHECK_IN,
                            ]);
                    });
                }),
            'pending' => Tab::make(__('messages.appointment.pending_vitals'))
                ->modifyQueryUsing(function (Builder $query) {
                    $query->whereDoesntHave('vitalSigns')
                        ->whereIn('is_completed', [
                            AppointmentModel::STATUS_IN_VITAL,
                            AppointmentModel::STATUS_IN_QUEUE,
                            AppointmentModel::STATUS_CHECK_IN,
                            AppointmentModel::STATUS_PENDING,
                        ]);
                }),
            'recorded' => Tab::make(__('messages.appointment.recorded_vitals'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('vitalSigns')),
        ];
    }
}
