<?php

namespace App\Filament\HospitalAdmin\Clusters\Doctors\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource\Pages;
use App\Models\Appointment;
use App\Models\BirthReport;
use App\Models\DeathReport;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use App\Models\EmployeePayroll;
use App\Models\InvestigationReport;
use App\Models\IpdPatientDepartment;
use App\Models\OperationReport;
use App\Models\PatientAdmission;
use App\Models\PatientCase;
use App\Models\Prescription;
use App\Models\Schedule;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class DoctorResource extends Resource
{
    protected static ?string $model = Doctor::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $cluster = Doctors::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Doctors')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Doctors')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.doctors');
    }

    public static function getLabel(): string
    {
        return __('messages.doctors');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Doctors')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Doctors')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && getModuleAccess('Doctors')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        if ($form->getOperation() === 'edit') {
            $doctorData = $form->model;
            $form->model = User::find($doctorData->user_id);
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->validationAttribute(__('messages.user.first_name'))
                            ->label(__('messages.user.first_name').':')
                            ->placeholder(__('messages.user.first_name'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->validationAttribute(__('messages.user.last_name'))
                            ->label(__('messages.user.last_name').':')
                            ->placeholder(__('messages.user.last_name'))
                            ->maxLength(255),
                        Forms\Components\Select::make('doctor_department_id')
                            ->options(DoctorDepartment::get()->where('tenant_id', getLoggedInUser()->tenant_id)->pluck('title', 'id')->sort())
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->optionsLimit(count(DoctorDepartment::get()->where('tenant_id', getLoggedInUser()->tenant_id)))
                            ->label(__('messages.doctor_department.doctor_department').':')
                            ->placeholder(__('messages.doctor_department.doctor_department'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.doctor_department.doctor_department').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\TextInput::make('email')
                            ->unique('users', 'email', ignoreRecord: true)
                            ->label(__('messages.user.email').':')
                            ->placeholder(__('messages.user.email'))
                            ->email()
                            ->required()
                            ->validationMessages([
                                'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                            ]),
                        TextInput::make('designation')
                            ->label(__('messages.user.designation').': ')
                            ->required()
                            ->validationAttribute(__('messages.user.designation'))
                            ->placeholder(__('messages.user.designation')),
                        PhoneInput::make('phone')
                            ->defaultCountry('IN')
                            ->rules(function ($get) {
                                return [
                                    'phone:AUTO,'.strtoupper($get('prefix_code')),
                                ];
                            })
                            ->validationMessages([
                                'phone' => __('messages.common.invalid_number'),
                            ])
                            ->afterStateHydrated(function ($component, $record, $operation) {
                                if ($operation == 'edit') {
                                    if (! empty($record->phone)) {
                                        $phoneNumber = (empty($record->region_code || ! str_starts_with($record->phone, '+')) ? '+' : $record->region_code).getPhoneNumber($record->phone);
                                    } else {
                                        $phoneNumber = null;
                                    }
                                    $component->state($phoneNumber);
                                }
                            })
                            ->countryStatePath('region_code')
                            ->label(__('messages.user.phone').':'),
                        Hidden::make('region_code'),
                        TextInput::make('qualification')
                            ->label(__('messages.user.qualification').':')
                            ->placeholder(__('messages.user.qualification'))
                            ->validationAttribute(__('messages.user.qualification'))
                            ->required(),
                        DatePicker::make('dob')
                            ->native(false)
                            ->live()
                            ->placeholder(__('messages.user.dob'))
                            ->suffix(fn($state) => $state
                                ? \Carbon\Carbon::parse($state)->age . ' years'
                                : '')
                            ->label(__('messages.user.dob') . ':'),
                        Select::make('blood_group')
                            ->label(__('messages.user.blood_group').':')
                            ->options(
                                getBloodGroups()
                            )
                            ->native(false),
                        Group::make()->schema([
                            Radio::make('gender')
                                ->label(__('messages.user.gender').':')
                                ->required()
                                ->validationAttribute(__('messages.user.gender'))
                                ->default(0)
                                ->options([
                                    0 => __('messages.user.male'),
                                    1 => __('messages.user.female'),
                                ])->columns(2)->columnSpan(2),
                            Toggle::make('status')
                                ->default(1)
                                ->label(__('messages.user.status').':')
                                ->inline(false)
                                ->columnSpan(1),
                        ])->columns(3),
                        TextInput::make('specialist')
                            ->required()
                            ->validationAttribute(__('messages.doctor.specialist'))
                            ->label(__('messages.doctor.specialist').':')
                            ->placeholder(__('messages.doctor.specialist')),
                        TextInput::make('appointment_charge')
                            ->label(__('messages.appointment_charge').':')
                            ->placeholder(__('messages.appointment_charge'))
                            ->numeric()
                            ->minValue(0),
                        Group::make()->schema([
                            Forms\Components\TextInput::make('password')
                                ->revealable()
                                ->visible(function (?string $operation) {
                                    return $operation == 'create';
                                })
                                ->rules(['min:8', 'max:20'])
                                ->confirmed()
                                ->label(__('messages.user.password').':')
                                ->placeholder(__('messages.user.password'))
                                ->required()
                                ->validationAttribute(__('messages.user.password'))
                                ->password()
                                ->maxLength(20),
                            TextInput::make('password_confirmation')
                                ->dehydrated(false)
                                ->visible(function (?string $operation) {
                                    return $operation == 'create';
                                })
                                ->label(__('messages.user.password_confirmation').':')
                                ->placeholder(__('messages.user.password_confirmation'))
                                ->validationAttribute(__('messages.user.password_confirmation'))
                                ->revealable()
                                ->required()
                                ->password()
                                ->maxLength(20),
                        ])->columns(2),
                        Textarea::make('description')
                            ->label(__('messages.doctor_department.description').':')
                            ->rows(4)
                            ->placeholder(__('messages.doctor_department.description')),
                        SpatieMediaLibraryFileUpload::make('user.profile')
                            ->label(__('messages.common.profile').':')
                            ->avatar()
                            ->image()
                            ->disk(config('app.media_disk'))
                            ->collection(User::COLLECTION_PROFILE_PICTURES),
                        Fieldset::make('Address Details')
                            ->schema([
                                TextInput::make('address1')
                                    ->label(__('messages.user.address1').':')
                                    ->placeholder(__('messages.user.address1')),
                                TextInput::make('address2')
                                    ->label(__('messages.user.address2').':')
                                    ->placeholder(__('messages.user.address2')),
                                Group::make()->schema([
                                    TextInput::make('city')
                                        ->label(__('messages.user.city').':')
                                        ->placeholder(__('messages.user.city')),
                                    TextInput::make('zip')
                                        ->label(__('messages.user.zip').':')
                                        ->placeholder(__('messages.user.zip')),
                                ])->columns(2),
                            ]),
                        Fieldset::make(__('messages.setting.social_details'))
                            ->schema([
                                TextInput::make('facebook_url')
                                    ->label(__('messages.facebook_url').':')
                                    ->url()
                                    ->placeholder(__('messages.facebook_url')),
                                TextInput::make('twitter_url')
                                    ->label(__('messages.twitter_url').':')
                                    ->url()
                                    ->placeholder(__('messages.twitter_url')),
                                TextInput::make('instagram_url')
                                    ->label(__('messages.instagram_url').':')
                                    ->url()
                                    ->placeholder(__('messages.instagram_url')),
                                TextInput::make('linkedIn_url')
                                    ->label(__('messages.linkedIn_url').':')
                                    ->url()
                                    ->placeholder(__('messages.linkedIn_url')),
                            ]),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Doctors')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(auth()->user()->tenant_id);

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    SpatieMediaLibraryImageColumn::make('user.profile')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.case.doctor'))->width(50)->height(50)
                        ->sortable(['first_name'])
                        ->defaultImageUrl(function ($record) {
                            if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->user->first_name);
                            }
                        }),
                    TextColumn::make('user.full_name')
                        ->label('')
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->formatStateUsing(function ($state, $record) {
                            return '<span>'.$record->user->full_name.'</span>';
                        })
                        ->html()
                        ->description(function ($record) {
                            return $record->user->email;
                        })
                        ->searchable(['first_name', 'last_name', 'email']),
                    Tables\Columns\TextColumn::make('specialist')
                        ->label(__('messages.doctor.specialist'))
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('user.qualification')
                        ->label(__('messages.user.qualification'))
                        ->sortable()
                        ->hidden(function () {
                            if (auth()->user()->hasRole(['Admin'])) {
                                return false;
                            }

                            return true;
                        })
                        ->default(__('messages.common.n/a')),
                    // TextColumn::make('user.status')
                    // ->hidden(function (){
                    //     if(auth()->user()->hasRole(['Doctor']))
                    //     {
                    //         return false;
                    //     }
                    //     return true;
                    // })
                    //     ->formatStateUsing(function ($record) {
                    //         return $record->user->status == 1 ? __('messages.common.active') : __('messages.common.deactive');
                    //     })
                    //     ->badge()
                    //     ->color(function ($record) {
                    //         return $record->user->status == 1 ? 'success' : 'danger';
                    //     })
                    //     ->label(__('messages.user.status'))
                    //     ->sortable(),
                    TextColumn::make('user.status')
                        ->label(__('messages.user.status'))
                        ->formatStateUsing(function ($record) {
                            return $record->user->status == 1 ? __('messages.common.active') : __('messages.common.deactive');
                        })
                        ->badge()
                        ->color(function ($record) {
                            return $record->user->status == 1 ? 'success' : 'danger';
                        }),
                    // ToggleColumn::make('user.status')
                    // ->hidden(function (){
                    //     if(auth()->user()->hasRole(['Admin']))
                    //     {
                    //         return false;
                    //     }
                    //     return true;
                    // })
                    //     ->label(__('messages.user.status'))
                    //     ->updateStateUsing(function ($record, bool $state) {
                    //         $user = User::find($record->user_id);
                    //         $state ? $user->status = 1 : $user->status = 0;
                    //         $user->save();
                    //         Notification::make()
                    //             ->title(__('messages.common.status_updated_successfully'))
                    //             ->success()
                    //             ->send();
                    //     }),
                ])
                ->filters([
                    // SelectFilter::make('status')
                    //     ->relationship('user', 'status')
                    //     ->label(__('messages.common.status'))
                    //     ->options([
                    //         '' => __('messages.filter.all'),
                    //         1 => __('messages.common.active'),
                    //         0 => __('messages.common.deactive'),
                    //     ])->native(false),
                    Filter::make('status')
                        ->hidden(function () {
                            if (auth()->user()->hasRole(['Admin', 'Receptionist'])) {
                                return false;
                            }

                            return true;
                        })
                        ->form([
                            Select::make(__('messages.user.status'))
                                ->options([
                                    'all' => __('messages.filter.all'),
                                    1 => __('messages.filter.active'),
                                    0 => __('messages.filter.deactive'),
                                ])->default('all')->native(false)
                                ->label(__('messages.user.status').':'),
                        ])->query(function (Builder $query, array $data) {
                            if ($data[__('messages.common.status')] == 'All') {
                                $query->with('user');
                            }
                            if ($data[__('messages.common.status')] == 1) {
                                $query->with('user')->whereHas('user', fn (Builder $query) => $query->where('status', 1));
                            } elseif ($data[__('messages.common.status')] == 0) {
                                $query->with('user')->whereHas('user', fn (Builder $query) => $query->where('status', 0));
                            }
                        }),
                ])
                ->actions([

                    Tables\Actions\ViewAction::make()->color('info')->iconButton()->extraAttributes(['class' => 'hidden'])->action(function ($data, $record) {
                        if (! canAccessRecord($record, $record->id)) {
                            return Notification::make()
                                ->title(__('messages.flash.not_allow_access_record'))
                                ->danger()
                                ->send();
                        }
                    }),

                    Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.doctor_update')),
                    Tables\Actions\DeleteAction::make()->iconButton()
                        ->successNotificationTitle(__('messages.flash.doctor_delete'))
                        ->action(function ($data, $record) {
                            $doctor = Doctor::find($record->id);
                            if (! canAccessRecord(Doctor::class, $doctor->id)) {
                                return Notification::make()
                                    ->title(__('messages.flash.nurse_cant_deleted'))
                                    ->danger()
                                    ->send();
                            }

                            if (getLoggedInUser()->is_default == 1) {
                                return Notification::make()
                                    ->title(__('messages.common.this_action_is_not_allowed_for_default_record'))
                                    ->danger()
                                    ->send();
                            }

                            $doctorModels = [
                                PatientCase::class,
                                PatientAdmission::class,
                                Schedule::class,
                                Appointment::class,
                                BirthReport::class,
                                DeathReport::class,
                                InvestigationReport::class,
                                OperationReport::class,
                                Prescription::class,
                                IpdPatientDepartment::class,
                            ];

                            $result = canDelete($doctorModels, 'doctor_id', $doctor->id);

                            $empPayRollResult = canDeletePayroll(
                                EmployeePayroll::class,
                                'owner_id',
                                $doctor->id,
                                $doctor->user->owner_type
                            );

                            if ($result || $empPayRollResult) {
                                return Notification::make()
                                    ->title(__('messages.flash.doctor_cant_deleted'))
                                    ->danger()
                                    ->send();
                            }

                            $doctor->user()->delete();
                            $doctor->address()->delete();
                            $doctor->delete();

                            return Notification::make()
                                ->title(__('messages.flash.doctor_delete'))
                                ->success()
                                ->send();
                        }),
                ])
                ->actionsColumnLabel((auth()->user()->hasRole(['Admin', 'Receptionist'])) ? __('messages.common.action') : '')
                ->bulkActions([
                    // Tables\Actions\BulkActionGroup::make([
                    //     Tables\Actions\DeleteBulkAction::make(),
                    // ]),
                ])
                ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getDoctorAssociatedData(int $doctorId)
    {
        $data['doctorData'] = Doctor::with([
            'cases.patient.patientUser',
            'patients.patientUser',
            'schedules',
            'payrolls',
            'doctorUser',
            'address',
            'appointments.doctor.doctorUser',
            'appointments.patient.patientUser',
            'appointments.department',
        ])->findOrFail($doctorId);
        $data['appointments'] = $data['doctorData']->appointments;

        return $data;
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
            'index' => Pages\ListDoctors::route('/'),
            'create' => Pages\CreateDoctor::route('/create'),
            'view' => Pages\ViewDoctor::route('/{record}'),
            'edit' => Pages\EditDoctor::route('/{record}/edit'),
        ];
    }
}
