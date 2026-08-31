<?php

namespace App\Filament\HospitalAdmin\Clusters\Users\Resources;

use App\Filament\HospitalAdmin\Clusters\Users;
use App\Filament\HospitalAdmin\Clusters\Users\Resources\UserResource\Pages;
use App\Models\Accountant;
use App\Models\AdvancedPayment;
use App\Models\Appointment;
use App\Models\BedAssign;
use App\Models\Bill;
use App\Models\BirthReport;
use App\Models\CaseHandler;
use App\Models\DeathReport;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use App\Models\EmployeePayroll;
use App\Models\InvestigationReport;
use App\Models\Invoice;
use App\Models\IpdPatientDepartment;
use App\Models\LabTechnician;
use App\Models\OperationReport;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\PatientCase;
use App\Models\Pharmacist;
use App\Models\Prescription;
use App\Models\Receptionist;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section as FormsSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = Users::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('messages.users');
    }

    public static function getLabel(): string
    {
        return __('messages.users');
    }

    public static function canCreate(): bool
    {
        if (Auth::user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (Auth::user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (Auth::user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (Auth::user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FormsSection::make()
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('messages.user.first_name').':')
                            ->placeholder(__('messages.user.first_name'))
                            ->validationAttribute(__('messages.user.first_name'))
                            ->required()
                            ->live()
                            ->maxLength(500),
                        TextInput::make('last_name')
                            ->label(__('messages.user.last_name').':')
                            ->placeholder(__('messages.user.last_name'))
                            ->validationAttribute(__('messages.user.last_name'))
                            ->required()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('email')
                            ->label(__('messages.user.email').':')
                            ->placeholder(__('messages.user.email'))
                            ->email()
                            ->validationAttribute(__('messages.user.email'))
                            ->validationMessages([
                                'unique' => __('messages.user.email').' '.__('messages.common.is_already_exists'),
                            ])
                            ->unique('users', 'email', ignoreRecord: true)
                            ->required(),
                        Forms\Components\Select::make('department_id')
                            ->label(__('messages.sms.role').':')
                            ->placeholder(__('messages.sms.select_role'))
                            ->visible(function (?string $operation) {
                                return $operation == 'create';
                            })
                            ->relationship('department', 'name', fn (Builder $query) => $query->where('id', '!=', 10))
                            ->native(false)
                            ->live()
                            ->required()
                            ->validationAttribute(__('messages.sms.role'))
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.sms.role').' '.__('messages.fields.required'),
                            ]),
                        Select::make('doctor_department_id')
                            ->label(__('messages.doctor_department.doctor_department').':')
                            ->placeholder(__('messages.web_appointment.select_department'))
                            ->visible(function (callable $get) {
                                return $get('department_id') == 2;
                            })
                            ->required(function (callable $get) {
                                return $get('department_id') == 2;
                            })
                            ->options(DoctorDepartment::whereTenantId(getLoggedInUser()->tenant_id)->pluck('title', 'id'))
                            ->searchable()
                            ->native(false),
                        DatePicker::make('dob')
                            ->native(false)
                            ->live()
                            ->placeholder(__('messages.user.dob'))
                            ->suffix(fn($state) => $state
                                ? \Carbon\Carbon::parse($state)->age . ' years'
                                : '')
                            ->label(__('messages.user.dob') . ':'),
                        Group::make()->schema([
                            Radio::make('gender')
                                ->label(__('messages.user.gender').':')
                                ->validationAttribute(__('messages.user.gender'))
                                ->required()
                                ->options([
                                    '0' => __('messages.user.male'),
                                    '1' => __('messages.user.female'),
                                ])
                                ->default('0')
                                ->columns(2),

                        ])->columns(2),
                        Forms\Components\TextInput::make('password')
                            ->revealable()
                            ->visible(function (?string $operation) {
                                return $operation == 'create';
                            })
                            ->rules(['min:8', 'max:20'])
                            ->confirmed()
                            ->label(__('messages.user.password'))
                            ->placeholder(__('messages.user.password'))
                            ->validationAttribute(__('messages.user.password'))
                            ->required()
                            ->password()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('password_confirmation')
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
                        Forms\Components\TextInput::make('facebook_url')
                            ->label(__('messages.facebook_url').':')
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->placeholder(__('messages.facebook_url'))
                            ->url(),
                        Forms\Components\TextInput::make('instagram_url')
                            ->label(__('messages.instagram_url').':')
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->placeholder(__('messages.instagram_url'))
                            ->url(),
                        Forms\Components\TextInput::make('twitter_url')
                            ->label(__('messages.twitter_url').':')
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->placeholder(__('messages.twitter_url'))
                            ->url(),
                        Forms\Components\TextInput::make('linkedIn_url')
                            ->label(__('messages.linkedIn_url').':')
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->placeholder(__('messages.linkedIn_url'))
                            ->url(),
                        SpatieMediaLibraryFileUpload::make('profile')
                            ->label(__('messages.common.profile').':')
                            ->avatar()
                            ->disk(config('app.media_disk'))
                            ->hint(__('messages.common.allow_img_text'))
                            ->collection(User::COLLECTION_PROFILE_PICTURES),
                    ])->columns(2),

            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('first_name')
                    ->label(__('messages.user.first_name').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('last_name')
                    ->label(__('messages.user.last_name').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('email')
                    ->label(__('messages.user.email').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('department.name')
                    ->label(__('messages.employee_payroll.role').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('phone')
                    ->label(__('messages.user.phone').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('gender')
                    ->label(__('messages.user.gender').':')
                    ->formatStateUsing(fn ($state) => $state == 0 ? __('messages.user.male') : __('messages.user.female')),
                TextEntry::make('dob')
                    ->label(__('messages.user.dob').':')
                    ->default(__('messages.common.n/a'))
                    ->getStateUsing(fn ($record) => $record->dob ? Carbon::parse($record->dob)->format('jS M, Y') : __('messages.common.n/a')),
                TextEntry::make('status')
                    ->label(__('messages.common.status').':')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state == 1 ? __('messages.common.active') : __('messages.common.deactive'))
                    ->color(function ($record) {
                        return $record->status == 1 ? 'success' : 'danger';
                    }),
                TextEntry::make('created_at')
                    ->since()
                    ->label(__('messages.common.created_at').':'),
                TextEntry::make('updated_at')
                    ->since()
                    ->label(__('messages.common.last_updated').':'),
                SpatieMediaLibraryImageEntry::make('avatar')->collection(User::COLLECTION_PROFILE_PICTURES)->columnSpan(2)->width(100)->height(100)
                    ->defaultImageUrl(function ($record) {
                        if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->full_name);
                        }
                    }),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('department_id', '!=', 10)->where('tenant_id', auth()->user()->tenant_id)->where('id', '!=', auth()->user()->id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->recordAction(null)
            ->recordUrl(null)
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')->collection(User::COLLECTION_PROFILE_PICTURES)->rounded()->label(__('messages.users'))->width(50)->height(50)
                    ->sortable(['first_name'])
                    ->defaultImageUrl(function ($record) {
                        if (! $record->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->full_name);
                        }
                    }),
                TextColumn::make('full_name')
                    ->label('')
                    ->formatStateUsing(fn ($record): View => view(
                        'user.user_view',
                        ['record' => $record],
                    ))
                    ->description(function (User $record) {
                        return $record->email;
                    })
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['first_name', 'last_name', 'email']),
                Tables\Columns\TextColumn::make('department.name')
                    ->label(__('messages.employee_payroll.role'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('email_verified_at')
                    ->label(__('messages.user.email_verified'))
                    ->disabled(fn ($record) => $record->email_verified_at)
                    ->updateStateUsing(function ($record, $state) {
                        $state ? $record->email_verified_at = now() : ' ';
                        Notification::make()
                            ->title(__('messages.flash.email_verified'))
                            ->success()
                            ->send($record);

                        return $record->save();
                    }),
                Tables\Columns\ToggleColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->disabled(fn ($record) => $record->status == 1)
                    ->updateStateUsing(function (User $user, bool $state) {
                        $state ? $user->status = 1 : $user->status = 0;
                        $user->save();
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('messages.common.status').':')
                    ->options([
                        '' => __('messages.filter.all'),
                        '1' => __('messages.filter.active'),
                        '0' => __('messages.filter.deactive'),
                    ])->native(false),
                SelectFilter::make('roles')
                    ->label(__('messages.employee_payroll.role').':')
                    ->native(false)
                    ->relationship('roles', 'name'),
            ])
            // ->recordUrl(null)
            // ->recordAction(null)
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->extraAttributes(['class' => 'hidden']),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.user_deleted'))
                    ->action(function ($record, $action) {
                        $user = User::find($record->id);
                        if (! canAccessRecord(User::class, $user->id)) {
                            return Notification::make()
                                ->title(__('messages.flash.user_not_found'))
                                ->warning()
                                ->send();
                        }
                        if (getLoggedInUser()->is_default == 1) {
                            return Notification::make()
                                ->title(__('messages.common.this_action_is_not_allowed_for_default_record'))
                                ->danger()
                                ->send();
                        }
                        $checkAdmin = User::whereId($user->id)->where('is_admin_default', 1)->exists();
                        if ($checkAdmin) {
                            return Notification::make()
                                ->title(__('messages.common.this_action_is_not_allowed_for_default_record'))
                                ->warning()
                                ->send();
                        }
                        $userRepository = app(UserRepository::class);
                        $userId = $user->id;
                        try {
                            /**
                             * @var User $user
                             */
                            $user = $userRepository->find($userId);

                            if ($user->department_id == 2) {
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
                                $result = canDelete($doctorModels, 'doctor_id', $user->owner_id);
                                $empPayRollResult = canDeletePayroll(
                                    EmployeePayroll::class,
                                    'owner_id',
                                    $user->owner_id,
                                    $user->owner_type
                                );
                                if ($result || $empPayRollResult) {
                                    throw new BadRequestHttpException(
                                        'Doctor can\'t be deleted.',
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                                Doctor::whereId($user->owner_id)->delete();
                            } elseif ($user->department_id == 3) {
                                $patientModels = [
                                    BirthReport::class,
                                    DeathReport::class,
                                    InvestigationReport::class,
                                    OperationReport::class,
                                    Appointment::class,
                                    BedAssign::class,
                                    PatientAdmission::class,
                                    PatientCase::class,
                                    Bill::class,
                                    Invoice::class,
                                    AdvancedPayment::class,
                                    Prescription::class,
                                    IpdPatientDepartment::class,
                                ];
                                $result = canDelete($patientModels, 'patient_id', $user->owner_id);
                                if ($result) {
                                    throw new BadRequestHttpException(
                                        __('messages.flash.Patient_cant_deleted'),
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                                Patient::whereId($user->owner_id)->delete();
                            } elseif ($user->department_id == 4) {
                                $empPayRollResult = canDeletePayroll(
                                    EmployeePayroll::class,
                                    'owner_id',
                                    $user->owner_id,
                                    $user->owner_type
                                );
                                if ($empPayRollResult) {
                                    throw new BadRequestHttpException(
                                        __('messages.flash.nurse_cant_deleted'),
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                            } elseif ($user->department_id == 5) {
                                $empPayRollResult = canDeletePayroll(
                                    EmployeePayroll::class,
                                    'owner_id',
                                    $user->owner_id,
                                    $user->owner_type
                                );
                                if ($empPayRollResult) {
                                    throw new BadRequestHttpException(
                                        __('messages.flash.receptionist_cant_deleted'),
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                                Receptionist::whereId($user->owner_id)->delete();
                            } elseif ($user->department_id == 6) {
                                $empPayRollResult = canDeletePayroll(
                                    EmployeePayroll::class,
                                    'owner_id',
                                    $user->owner_id,
                                    $user->owner_type
                                );
                                if ($empPayRollResult) {
                                    throw new BadRequestHttpException(
                                        __('messages.flash.Pharmacist_cant_deleted'),
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                                Pharmacist::whereId($user->owner_id)->delete();
                            } elseif ($user->department_id == 7) {
                                $empPayRollResult = canDeletePayroll(
                                    EmployeePayroll::class,
                                    'owner_id',
                                    $user->owner_id,
                                    $user->owner_type
                                );
                                if ($empPayRollResult) {
                                    throw new BadRequestHttpException(
                                        __('messages.flash.accountant_cant_delete'),
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                                Accountant::whereId($user->owner_id)->delete();
                            } elseif ($user->department_id == 8) {
                                CaseHandler::whereId($user->owner_id)->delete();
                            } elseif ($user->department_id == 9) {
                                $empPayRollResult = canDeletePayroll(
                                    EmployeePayroll::class,
                                    'owner_id',
                                    $user->owner_id,
                                    $user->owner_type
                                );
                                if ($empPayRollResult) {
                                    throw new BadRequestHttpException(
                                        __('messages.flash.lab_technician_cant_deleted'),
                                        null,
                                        \Illuminate\Http\Response::HTTP_BAD_REQUEST
                                    );
                                }
                                LabTechnician::whereId($user->owner_id)->delete();
                            }

                            $user->clearMediaCollection(User::COLLECTION_PROFILE_PICTURES);
                            $userRepository->delete($userId);
                        } catch (Exception $e) {
                            Notification::make()->danger()->title($e->getMessage())->send();
                            $action->halt();
                        }
                        Notification::make()
                            ->title(__('messages.flash.user_deleted'))
                            ->success()
                            ->send();
                    }),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
