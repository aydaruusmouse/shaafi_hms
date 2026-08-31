<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\InsuranceResource;
use App\Filament\HospitalAdmin\Clusters\Services\Resources\PackageResource;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\Doctor;
use App\Models\Insurance;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\User;
use App\Repositories\PatientAdmissionRepository;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class PatientAdmissionResource extends Resource
{
    protected static ?string $model = PatientAdmission::class;

    protected static ?string $cluster = Patients::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Patient Admissions')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Patient Admissions')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.patient_admissions');
    }

    public static function getLabel(): string
    {
        return __('messages.patient_admissions');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Case Manager') && ! getModuleAccess('Patient Admissions')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Doctor', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Select::make('patient_id')
                            ->label(__('messages.patient_admission.patient').':')
                            // ->relationship('patient.patientUser', 'first_name', function ($query) {
                            //     PatientAdmission::with('patient.patientUser')->whereTenantId(getLoggedInUser()->tenant_id)
                            //         ->whereHas('patient', function (Builder $query) {
                            //             $query->where('status', 1);
                            //         });
                            // })

                            ->options(Patient::with('patientUser')
                                ->whereTenantId(getLoggedInUser()->tenant_id)
                                ->whereHas('patientUser', function (Builder $query) {
                                    $query->where('status', 1);
                                })->get()->pluck('patientUser.full_name', 'id')->sort())
                            ->searchable()
                            ->required()
                            ->preload()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.patient_admission.patient').' '.__('messages.fields.required'),
                            ]),

                        Select::make('doctor_id')
                            ->label(__('messages.patient_admission.doctor').':')
                            ->options(Doctor::with('doctorUser')->get()->where('doctorUser.tenant_id', '=', getLoggedInUser()->tenant_id)->where('doctorUser.status', '=', 1)->pluck('doctorUser.full_name', 'id')->sort())
                            ->searchable()
                            ->required()
                            ->preload()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.patient_admission.doctor').' '.__('messages.fields.required'),
                            ]),
                        DateTimePicker::make('admission_date')
                            ->label(__('messages.patient_admission.admission_date').':')
                            ->placeholder(__('messages.patient_admission.admission_date'))
                            ->default(now())
                            ->native(false)
                            ->validationAttribute(__('messages.patient_admission.admission_date'))
                            ->required(),
                        DateTimePicker::make('discharge_date')
                            ->label(__('messages.patient_admission.discharge_date').':')
                            ->placeholder(__('messages.patient_admission.discharge_date'))
                            ->native(false)
                            ->minDate(now())
                            ->visible(fn ($context) => $context === 'edit')
                            ->validationAttribute(__('messages.patient_admission.discharge_date')),
                        Select::make('package_id')
                            ->label(__('messages.patient_admission.package').':')
                            ->options(Package::where('tenant_id', '=', getLoggedInUser()->tenant_id)->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('insurance_id')
                            ->label(__('messages.patient_admission.insurance').':')
                            ->options(Insurance::where('tenant_id', '=', getLoggedInUser()->tenant_id)->whereStatus(1)->orderBy('name')->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('bed_id')
                            ->label(__('messages.patient_admission.bed').':')
                            ->options(Bed::where('is_available', 1)->where('tenant_id', '=', getLoggedInUser()->tenant_id)->pluck('name', 'id')->sort())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('policy_no')
                            ->label(__('messages.patient_admission.policy_no').':')
                            ->placeholder(__('messages.patient_admission.policy_no').':'),
                        TextInput::make('agent_name')
                            ->label(__('messages.patient_admission.agent_name').':')
                            ->placeholder(__('messages.patient_admission.agent_name').':'),
                        TextInput::make('guardian_name')
                            ->label(__('messages.patient_admission.guardian_name').':')
                            ->placeholder(__('messages.patient_admission.guardian_name').':'),
                        TextInput::make('guardian_relation')
                            ->label(__('messages.patient_admission.guardian_relation').':')
                            ->placeholder(__('messages.patient_admission.guardian_relation').':'),
                        PhoneInput::make('guardian_contact')
                            ->label(__('messages.patient_admission.guardian_contact').':')
                            ->placeholder(__('messages.patient_admission.guardian_contact').':')
                            ->defaultCountry('IN')
                            ->rules(function ($get) {
                                return [
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->validationAttribute(__('messages.patient_admission.guardian_contact')),
                        TextInput::make('guardian_address')
                            ->label(__('messages.patient_admission.guardian_address').':')
                            ->placeholder(__('messages.patient_admission.guardian_address').':'),
                        Toggle::make('status')
                            ->live()
                            ->default(1)
                            ->label(__('messages.common.status')),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Receptionist']) && ! getModuleAccess('Patient Admissions')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function (Builder $query) {
            $query->whereTenantId(getLoggedInUser()->tenant_id);

            if (auth()->user()->hasRole('Patient')) {
                $query->where('patient_id', auth()->user()->owner_id);
            } elseif (auth()->user()->hasRole('Doctor')) {
                $query->where('doctor_id', auth()->user()->owner_id);
            }

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('patient_admission_id')
                    ->label(__('messages.bill.admission_id'))
                    ->sortable()
                    ->formatStateUsing(fn ($record): View => view(
                        'patient_admission.patient_admission_view',
                        ['record' => $record],
                    ))
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
                    ->sortable(['first_name'])
                    ->url(fn ($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->html()
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$record->patient->user->full_name.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.patient_admission.doctor'))
                    ->circular()
                    ->sortable(['first_name'])
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
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('admission_date')
                    ->label(__('messages.patient_admission.admission_date'))
                    ->badge()
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
                    ->badge()
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
                            return '<a href="'.PackageResource::getUrl('view', ['record' => $record->package->id]).'"class="hoverLink font-bold">'.$record->package->name.'</a>';
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
                ToggleColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->sortable()
                    ->searchable()
                    ->afterStateUpdated(function () {
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
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
            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->extraAttributes(['class' => 'hidden'])->modalWidth('6xl'),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                    $patientAdmission = $record;
                    if (! canAccessRecord($patientAdmission, $patientAdmission->id)) {
                        return Notification::make()
                            ->title(__('messages.flash.patient_admission_not_found'))
                            ->danger()
                            ->send();
                    }

                    if (getLoggedInUser()->hasRole('Doctor')) {
                        $patientAdmissionHasDoctor = PatientAdmission::whereId($patientAdmission->id)->whereDoctorId(getLoggedInUser()->owner_id)->exists();
                        if (! $patientAdmissionHasDoctor) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.patient_admission_not_found'))
                                ->send();
                        }
                    }

                    $patientAdmissionModel = [
                        Bill::class,
                    ];
                    $result = canDelete($patientAdmissionModel, 'patient_admission_id', $patientAdmission->patient_admission_id);
                    if ($result) {
                        Notification::make()
                            ->title(__('messages.flash.patient_admission_cant_deleted'))
                            ->danger()
                            ->send();
                    }

                    if (! empty($patientAdmission->bed_id)) {
                        app(PatientAdmissionRepository::class)->setBedAvailable($patientAdmission->bed_id);
                    }
                    app(PatientAdmissionRepository::class)->delete($patientAdmission->id);

                    return Notification::make()
                        ->title(__('messages.flash.patient_admission_deleted'))
                        ->success()
                        ->send();
                }),
            ])
            ->actionsColumnLabel(__('messages.common.actions'))
            ->recordUrl(null)
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('patient.user.full_name')
                    ->label(__('messages.case.patient').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('doctor.user.full_name')
                    ->label(__('messages.case.doctor').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('patient_admission_id')
                    ->label(__('messages.bill.admission_id').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('admission_date')
                    ->label(__('messages.patient_admission.admission_date').':')
                    ->formatStateUsing(fn ($state) => ! empty($state) && strtotime($state) ? date('jS M,Y g:i A', strtotime($state)) : __('messages.common.n/a'))
                    ->default(__('messages.common.n/a')),
                TextEntry::make('discharge_date')
                    ->label(__('messages.patient_admission.discharge_date').':')
                    ->formatStateUsing(fn ($state) => ! empty($state) && strtotime($state) ? date('jS M,Y g:i A', strtotime($state)) : __('messages.common.n/a'))
                    ->default(__('messages.common.n/a')),
                TextEntry::make('package.name')
                    ->label(__('messages.patient_admission.package').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('insurance.name')
                    ->label(__('messages.patient_admission.insurance').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('bed.name')
                    ->label(__('messages.patient_admission.bed').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('policy_no')
                    ->label(__('messages.patient_admission.policy_no').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('agent_name')
                    ->label(__('messages.patient_admission.agent_name').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('guardian_name')
                    ->label(__('messages.patient_admission.guardian_name').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('guardian_relation')
                    ->label(__('messages.patient_admission.guardian_relation').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('guardian_contact')
                    ->label(__('messages.patient_admission.guardian_contact').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('guardian_address')
                    ->label(__('messages.patient_admission.guardian_address').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('status')
                    ->label(__('messages.common.status').':')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? __('messages.common.active') : __('messages.common.de_active'))
                    ->color(fn ($state) => $state === 1 ? 'success' : 'danger')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('created_at')
                    ->label(__('messages.common.created_on').':')
                    ->since()
                    ->default(__('messages.common.n/a')),
                TextEntry::make('updated_at')
                    ->label(__('messages.common.last_updated').':')
                    ->since()
                    ->default(__('messages.common.n/a')),
            ])->columns(4);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatientAdmissions::route('/'),
            'create' => Pages\CreatePatientAdmission::route('/create'),
            'edit' => Pages\EditPatientAdmission::route('/{record}/edit'),
        ];
    }
}
