<?php

namespace App\Filament\HospitalAdmin\Pages;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource;
use App\Models\PatientAdmission as PatientAdmissionModel;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PatientAdmission extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationIcon = 'fas-user-injured';

    protected static string $view = 'filament.hospital-admin.pages.patient-admission';

    public static function getNavigationLabel(): string
    {
        return __('messages.patient_admissions');
    }

    public static function getLabel(): string
    {
        return __('messages.patient_admissions');
    }

    public static function canAccess(): bool
    {
        if (auth()->user()->hasRole(['Patient']) && getModuleAccess('Patient Admissions')) {
            return true;
        }

        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(PatientAdmissionModel::where('tenant_id', getLoggedInUser()->tenant_id)->where('patient_id', getLoggedInUser()->owner_id))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('patient_admission_id')
                    ->label(__('messages.bill.admission_id'))
                    ->sortable()
                    // ->formatStateUsing(fn($record): View => view(
                    //     'patient_admission.patient_admission_view',
                    //     ['record' => $record],
                    // ))
                    ->searchable()
                    ->color('info'),
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.patient_admission.patient'))
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
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$record->patient->user->full_name.'</a>')
                    ->color('primary')
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.patient_admission.doctor'))
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
                    ->formatStateUsing(fn ($record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'"class="hoverLink">'.$record->doctor->user->full_name.'</a>')
                    ->color('primary')
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('admission_date')
                    ->label(__('messages.patient_admission.admission_date'))
                    ->getStateUsing(function ($record) {
                        if ($record->admission_date) {
                            return \Carbon\Carbon::parse($record->admission_date)->isoFormat('LT').' <br>'.\Carbon\Carbon::parse($record->admission_date)->translatedFormat('jS M, Y');
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->html(true),
                TextColumn::make('discharge_date')
                    ->label(__('messages.patient_admission.discharge_date'))
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        if ($record->discharge_date) {
                            return \Carbon\Carbon::parse($record->discharge_date)->isoFormat('LT').' <br>'.\Carbon\Carbon::parse($record->discharge_date)->translatedFormat('jS M, Y');
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->html(true),
                TextColumn::make('package.name')
                    ->label(__('messages.patient_admission.package'))
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->getStateUsing(fn ($record) => $record->package->name ?? __('messages.common.n/a'))
                    ->formatStateUsing(function ($record) {
                        if (! empty($record->package->id)) {
                            return '<a href="'.PackageResource::getUrl('view', ['record' => $record->package->id]).'"class="hoverLink">'.$record->package->name.'</a>';
                        }

                        return __('messages.common.n/a');
                    })
                    ->color(fn ($record) => ! $record->package ?: 'primary'),
                TextColumn::make('insurance.name')
                    ->label(__('messages.patient_admission.insurance'))
                    ->sortable()
                    ->searchable()
                    ->html()
                    ->formatStateUsing(function ($record) {
                        if (! empty($record->insurance->id)) {
                            return '<a href="'.InsuranceResource::getUrl('view', ['record' => $record->insurance->id]).'"class="hoverLink">'.$record->insurance->name.'</a>';
                        }

                        return __('messages.common.n/a');
                    })
                    ->color(fn ($record) => ! $record->insurance ?: 'primary')
                    ->getStateUsing(fn ($record) => $record->insurance->name ?? __('messages.common.n/a')),
                TextColumn::make('policy_no')
                    ->sortable()
                    ->searchable()
                    ->label(__('messages.patient_admission.policy_no'))
                    ->getStateUsing(fn ($record) => $record->policy_no ?? __('messages.common.n/a')),
                TextColumn::make('status')
                    ->formatStateUsing(function ($record) {
                        return $record->status == 1 ? __('messages.common.active') : __('messages.common.deactive');
                    })
                    ->badge()
                    ->color(function ($record) {
                        return $record->status == 1 ? 'success' : 'danger';
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.common.status'))
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ])->native(false),
            ]);
    }
}
