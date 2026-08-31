<?php

namespace App\Filament\HospitalAdmin\Widgets;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAppoinemnt extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('Patient');
    }

    public function getQuery()
    {
        $now = Carbon::today();
        $sixDays = $now->copy()->addDays(6);

        if (getLoggedInUser()->hasRole('Patient')) {
            $query = Appointment::with(['patient.user', 'doctor.user'])->where('patient_id', getLoggedInUser()->owner_id)->where('tenant_id', getLoggedInUser()->tenant_id)->select('appointments.*')->whereBetween('opd_date', [$now, $sixDays])->select('appointments.*');
        } else {
            $query = Appointment::with(['patient.user', 'doctor.user'])->whereBetween('opd_date', [$now, $sixDays])->where('tenant_id', getLoggedInUser()->tenant_id)->select('appointments.*');
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.role.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html(),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.role.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'" class="hoverLink">'.$record->doctor->doctorUser->full_name.'</a>')
                    ->html()
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a')),
                TextColumn::make('doctor.department.title')
                    ->label(__('messages.appointment.doctor_department')),
                TextColumn::make('opd_date')
                    ->label(__('messages.appointment.date')),
            ]);
    }
}
