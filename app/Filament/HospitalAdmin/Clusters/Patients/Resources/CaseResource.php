<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseResource\Pages;
use App\Models\BedAssign;
use App\Models\BirthReport;
use App\Models\DeathReport;
use App\Models\Doctor;
use App\Models\IpdPatientDepartment;
use App\Models\OperationReport;
use App\Models\Patient;
use App\Models\PatientCase;
use App\Models\User;
use App\Repositories\PatientCaseRepository;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class CaseResource extends Resource
{
    protected static ?string $model = PatientCase::class;

    protected static ?string $cluster = Patients::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Cases')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Cases')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        if (auth()->user()->hasRole('Patient')) {
            return __('messages.patients_cases');
        }

        return __('messages.cases');
    }

    public static function getLabel(): string
    {
        if (auth()->user()->hasRole('Patient')) {
            return __('messages.patients_cases');
        }

        return __('messages.cases');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole('Case Manager') && ! getModuleAccess('Cases')) {
            return false;
        } elseif (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist', 'Patient'])) {
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
                            ->label(__('messages.case.patient').':')
                            ->options(Patient::with('patientUser')->get()->where('patientUser.tenant_id', '=', getLoggedInUser()->tenant_id)->where('patientUser.status', '=', 1)->pluck('patientUser.full_name', 'id')->sort())
                            ->placeholder(__('messages.document.select_patient'))
                            ->native()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.case.patient').' '.__('messages.fields.required'),
                            ]),
                        Select::make('doctor_id')
                            ->label(__('messages.case.doctor').':')
                            ->options(Doctor::with('doctorUser')->get()->where('doctorUser.tenant_id', '=', getLoggedInUser()->tenant_id)->where('doctorUser.status', '=', 1)->pluck('doctorUser.full_name', 'id')->sort())
                            ->placeholder(__('messages.web_home.select_doctor'))
                            ->native()
                            ->searchable()
                            ->preload()

                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.case.doctor').' '.__('messages.fields.required'),
                            ]),
                        DateTimePicker::make('date')
                            ->label(__('messages.case.case_date').':')
                            ->native(false)
                            ->default(now())
                            ->validationAttribute(__('messages.case.case_date'))
                            ->required(),
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
                            ->label(__('messages.user.phone').':'),
                        Toggle::make('status')
                            ->live()
                            ->default(1)
                            ->label(__('messages.common.status')),
                        TextInput::make('fee')
                            ->label(__('messages.case.fee').':')
                            ->placeholder(__('messages.case.fee'))
                            ->numeric()
                            ->minValue(1)
                            ->validationAttribute(__('messages.case.fee'))
                            ->required(),
                        Textarea::make('description')
                            ->rows(4)
                            ->label(__('messages.case.description').':'),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Case Manager', 'Receptionist', 'Patient']) && ! getModuleAccess('Cases')) {
            abort(404);
        }

        return
            $table = $table->modifyQueryUsing(function (Builder $query) {
                $query->whereTenantId(getLoggedInUser()->tenant_id);

                if (auth()->user()->hasRole(['Patient'])) {
                    $patientID = Patient::where('user_id', getLoggedInUserId())->first();

                    $query->where('patient_id', $patientID->id);
                }

                return $query;
            })
                ->paginated([10, 25, 50])
                ->defaultSort('id', 'desc')
                ->columns([
                    TextColumn::make('case_id')
                        ->label(__('messages.case.case_id'))
                        ->searchable()
                        ->color('info')
                        ->sortable()
                        ->formatStateUsing(fn ($record): View => view(
                            'case.case_view',
                            ['record' => $record],
                        )),
                    SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                        ->label(__('messages.role.patient'))
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
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                        ->description(fn ($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                        ->searchable(['users.first_name', 'users.last_name']),
                    SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                        ->label(__('messages.case.doctor'))
                        ->circular()
                        ->defaultImageUrl(function ($record) {
                            if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                                return getUserImageInitial($record->id, $record->doctor->user->full_name);
                            }
                        })
                        ->sortable(['first_name'])

                        ->url(function ($record) {
                            if (auth()->user()->hasRole('Patient')) {
                                return '';
                            }

                            return DoctorResource::getUrl('view', ['record' => $record->doctor->id]);
                        })
                        ->collection('profile')
                        ->width(50)->height(50),
                    TextColumn::make('doctor.doctorUser.full_name')
                        ->label('')
                        ->html()
                        ->color('primary')
                        ->weight(FontWeight::SemiBold)
                        ->formatStateUsing(function ($record) {
                            if (auth()->user()->hasRole('Patient')) {
                                return $record->doctor->doctorUser->full_name;
                            }

                            return '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'"class="hoverLink">'.$record->doctor->doctorUser->full_name.'</a>';
                        })
                        ->description(fn ($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                        ->searchable(['users.first_name', 'users.last_name']),
                    TextColumn::make('date')
                        ->sortable()
                        ->label(__('messages.case.case_date'))
                        ->view('tables.columns.date_time'),
                    TextColumn::make('fee')
                        ->label(__('messages.case.fee'))
                        ->sortable()
                        ->searchable()
                        ->getStateUsing(fn ($record) => getCurrencyFormat($record->fee) ?? __('messages.common.n/a')),
                    ToggleColumn::make('status')
                        ->label(__('messages.common.status'))
                        ->afterStateUpdated(function ($record) {
                            $record->status;

                            return Notification::make()
                                ->success()
                                ->title(__('messages.common.status_updated_successfully'))
                                ->send();
                        }),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->label(__('messages.common.status'))
                        ->options([
                            '' => __('messages.filter.all'),
                            '1' => __('messages.common.active'),
                            '0' => __('messages.common.deactive'),
                        ])
                        ->native(false),
                ])
                ->recordAction(null)
                ->recordUrl(null)
                ->actions([
                    Tables\Actions\ViewAction::make()->color('info')->iconButton()->extraAttributes(['class' => 'hidden']),
                    Tables\Actions\EditAction::make()->iconButton(),
                    Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                        if (! canAccessRecord(PatientCase::class, $record->id)) {
                            // return $this->sendError(__('messages.flash.patient_case_not_found'));
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.patient_case_not_found'))
                                ->send();
                        }

                        $patientCaseModel = [
                            BedAssign::class,
                            BirthReport::class,
                            DeathReport::class,
                            OperationReport::class,
                            IpdPatientDepartment::class,
                        ];

                        $result = canDelete($patientCaseModel, 'case_id', $record->case_id);
                        if ($result) {
                            return $this->sendError(__('messages.flash.case_cant_deleted'));
                        }
                        app(PatientCaseRepository::class)->delete($record->id);

                        Notification::make()
                            ->success()
                            ->title(__('messages.flash.case_deleted'))
                            ->send();
                    }),
                ])
                ->actionsColumnLabel((auth()->user()->hasRole('Patient')) ? '' : __('messages.common.action'))
                ->bulkActions([
                    // Tables\Actions\BulkActionGroup::make([
                    //     Tables\Actions\DeleteBulkAction::make(),
                    // ]),
                ])
                ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            TextEntry::make('case_id')
                ->label(__('messages.operation_report.case_id').':')
                ->prefix('#')
                ->badge(),
            TextEntry::make('patient.patientUser.full_name')
                ->label(__('messages.case.patient').':')
                ->default(__('messages.common.n/a')),
            TextEntry::make('phone')
                ->label(__('messages.case.phone').':')
                ->default(__('messages.common.n/a')),
            TextEntry::make('doctor.doctorUser.full_name')
                ->label(__('messages.case.doctor').':')
                ->default(__('messages.common.n/a')),
            TextEntry::make('date')
                ->label(__('messages.case.case_date').':')
                ->getStateUsing(fn ($record) => $record->created_at->translatedFormat('jS M,Y g:i A') ?? __('messages.common.n/a')),
            TextEntry::make('fee')
                ->label(__('messages.case.fee').':'),
            TextEntry::make('created_at')
                ->label(__('messages.common.created_at').':')
                ->getStateUsing(fn ($record) => $record->created_at->diffForHumans() ?? __('messages.common.n/a')),
            TextEntry::make('updated_at')
                ->label(__('messages.common.last_updated').':')
                ->getStateUsing(fn ($record) => $record->updated_at->diffForHumans() ?? __('messages.common.n/a')),
            TextEntry::make('status')
                ->label(__('messages.common.status').':')
                ->getStateUsing(function ($record) {
                    return $record->status == 1 ? __('messages.common.active') : __('messages.common.de_active');
                })
                ->color(function ($record) {
                    return $record->status == 1 ? 'success' : 'danger';
                })
                ->badge(),
            TextEntry::make('description')
                ->label(__('messages.common.description').':')
                ->default(__('messages.common.n/a')),
        ])->columns(2);
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
            'index' => Pages\ListCases::route('/'),
            'create' => Pages\CreateCase::route('/create'),
            'edit' => Pages\EditCase::route('/{record}/edit'),
            // 'view' => Pages\ViewCase::route('/{record}'),
        ];
    }
}
