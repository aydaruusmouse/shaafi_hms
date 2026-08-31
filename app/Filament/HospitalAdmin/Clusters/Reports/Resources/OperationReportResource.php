<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Reports;
use App\Filament\HospitalAdmin\Clusters\Reports\Resources\OperationReportResource\Pages;
use App\Models\Doctor;
use App\Models\OperationReport;
use App\Models\Patient;
use App\Models\User;
use App\Repositories\BirthReportRepository;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OperationReportResource extends Resource
{
    protected static ?string $model = OperationReport::class;

    protected static ?string $cluster = Reports::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Operation Reports')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Operation Reports')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.operation_reports');
    }

    public static function getLabel(): string
    {
        return __('messages.operation_reports');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Operation Reports')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Operation Reports')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Operation Reports')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('case_id')
                    ->required()
                    ->label(__('messages.death_report.case_id'))
                    ->searchable()
                    ->preload()
                    ->optionsLimit(500)
                    ->options(function () {
                        $bedAssignRepo = app(BirthReportRepository::class);

                        return $bedAssignRepo->getCases();
                    })
                    ->native(false)
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.death_report.case_id').' '.__('messages.fields.required'),
                    ]),

                Select::make('doctor_id')
                    ->label(__('messages.role.doctor'))
                    ->required()
                    ->hidden(! auth()->user()->hasRole('Admin'))
                    ->searchable()
                    ->options(function () {
                        $bedAssignRepo = app(BirthReportRepository::class);

                        return $bedAssignRepo->getDoctors();
                    })
                    ->native(false)
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.role.doctor').' '.__('messages.fields.required'),
                    ]),

                DateTimePicker::make('date')
                    ->required()
                    ->validationAttribute(__('messages.death_report.date'))
                    ->label(__('messages.death_report.date'))
                    ->default(now())
                    ->placeholder(__('messages.death_report.date'))
                    ->native(false),

                Textarea::make('description')
                    ->label(__('messages.death_report.description'))
                    ->placeholder(__('messages.death_report.description')),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && ! getModuleAccess('Operation Reports')) {
            abort(404);
        }
        $table = $table->modifyQueryUsing(function ($query) {
            $query->orderBy('id', 'desc');

            if (! getLoggedinDoctor()) {
                $query = OperationReport::with('patient', 'doctor', 'caseFromOperationReport');
            } else {
                $doctorId = Doctor::where('user_id', getLoggedInUserId())->first();
                $query = OperationReport::with('patient', 'doctor', 'caseFromOperationReport')->where(
                    'doctor_id',
                    $doctorId->id
                );
                // dump($query->get());
            }
            if (getLoggedinPatient()) {
                $patientId = Patient::where('user_id', getLoggedInUserId())->first();
                $query = OperationReport::with('patient', 'doctor', 'caseFromOperationReport')->where(
                    'patient_id',
                    $patientId->id
                );
            }

            return $query->whereTenantId(auth()->user()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('case_id')
                    ->label(__('messages.operation_report.case_id'))
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->url(fn ($record) => OperationReportResource::getUrl('viewCase', ['record' => $record->caseFromOperationReport->id])),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.case.patient'))
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
                TextColumn::make('patient.user.full_name')
                    ->label('')
                    ->description(function ($record) {
                        return $record->patient->user->email;
                    })
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['first_name', 'last_name', 'email']),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.case.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? 'N/A')
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('date')
                    ->label(__('messages.case.date'))
                    ->sortable()
                    ->badge()
                    ->extraAttributes(['class' => 'text-center', 'text-sm'])
                    ->getStateUsing(function ($record) {
                        if ($record->date) {
                            return \Carbon\Carbon::parse($record->date)->isoFormat('LT').' <br>'.\Carbon\Carbon::parse($record->date)->translatedFormat('jS M, Y');
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->html(true),
            ])
            ->filters([
                //
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton()->modalWidth('md')->modalWidth('xl'),
                Tables\Actions\EditAction::make()->iconButton()->modalWidth('md')->modalWidth('xl')->successNotificationTitle(__('messages.flash.operation_report_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function ($record) {
                        if (! canAccessRecord($record::class, $record->id)) {
                            return Notification::make()
                                ->title(__('messages.flash.operation_report_not_found'))
                                ->danger()
                                ->send();
                        }

                        if (getLoggedInUser()->hasRole('Doctor')) {
                            $patientCaseHasDoctor = OperationReport::whereId($record->id)->whereDoctorId(getLoggedInUser()->owner_id)->exists();
                            if (! $patientCaseHasDoctor) {
                                return Notification::make()
                                    ->title(__('messages.flash.operation_report_not_found'))
                                    ->danger()
                                    ->send();
                            }
                        }

                        $record->delete();

                        return Notification::make()
                            ->title(__('messages.flash.operation_report_deleted'))
                            ->success()
                            ->send();
                    }),

            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->recordUrl(null)
            ->recordAction(null)
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'viewCase' => Pages\ViewOperationReportCase::route('/{record}/view-case'),
            'view' => Pages\ViewOperationReports::route('/{record}'),
            'index' => Pages\ManageOperationReports::route('/'),
        ];
    }
}
