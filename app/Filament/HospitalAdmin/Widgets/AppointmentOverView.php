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

class AppointmentOverView extends BaseWidget
{
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        $now = Carbon::today();
        $sixDays = $now->copy()->addDays(6);
        $query = Appointment::with(['patient.user', 'doctor.user'])->whereTenantId(getLoggedInUser()->tenant_id)->whereNot('is_completed', 3)->whereBetween('opd_date', [$now, $sixDays])->select('appointments.*')->count();

        return $query > 0 && auth()->user()->hasRole('Admin');
    }

    public function table(Table $table): Table
    {
        $now = Carbon::today();
        $sixDays = $now->copy()->addDays(6);

        return $table
            ->defaultSort('id', 'desc')
            ->heading(__('messages.new_change.upcoming_appointments'))
            ->query(Appointment::with(['patient.user', 'doctor.user'])->whereTenantId(getLoggedInUser()->tenant_id)->whereNot('is_completed', 3)->whereBetween('opd_date', [$now, $sixDays])->select('appointments.*'))
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.case.patient'))
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
                    ->html()
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->color('primary')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a')),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.case.doctor'))
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
                    ->html()
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'"class="hoverLink">'.$record->doctor->doctorUser->full_name.'</a>')
                    ->color('primary')
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a')),
                TextColumn::make('opd_date')
                    ->label(__('messages.appointment.date'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(
                        fn ($state) => '<div class="text-center">'.Carbon::parse($state)->format('g:i A').'</div>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->badge()
                    ->html(),
            ])->paginated([5])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }
}
