<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources;

use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Reports;
use App\Filament\HospitalAdmin\Clusters\Reports\Resources\DeathReportResource\Pages;
use App\Models\DeathReport;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientCase;
use App\Models\User;
use App\Repositories\BirthReportRepository;
use Carbon\Carbon;
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
use Illuminate\Support\Facades\Auth;

class DeathReportResource extends Resource
{
    protected static ?string $model = DeathReport::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Reports::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Death Reports')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Death Reports')) {
            return false;
        }

        return true;
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Death Reports')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Death Reports')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Death Reports')) {
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
                    ->placeholder(__('messages.death_report.date'))
                    ->maxDate(now())
                    ->default(Carbon::now())
                    ->native(false),

                Textarea::make('description')
                    ->label(__('messages.death_report.description'))
                    ->placeholder(__('messages.death_report.description')),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && ! getModuleAccess('Death Reports')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', Auth::user()->tenant_id);
            if (getLoggedinDoctor()) {
                $doctorId = Doctor::where('user_id', getLoggedInUserId())->first();
                $query = $query->where('doctor_id', $doctorId->id);
            }

            if (getLoggedinPatient()) {
                $patientId = Patient::where('user_id', getLoggedInUserId())->first();
                $query = $query->where('patient_id', $patientId->id);
            }

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('case_id')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => DeathReportResource::getUrl('viewCase', ['record' => $record->caseFromDeathReport->id])),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.invoice.patient'))
                    ->circular()
                    ->sortable(['first_name'])
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
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
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? 'N/A')
                    ->searchable(['users.first_name', 'users.last_name']),

                Tables\Columns\TextColumn::make('date')
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->badge()
                    ->html()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()
                    ->action(function ($record, array $data) {

                        $patientId = PatientCase::select('patient_id')->whereCaseId($data['case_id'])->where('tenant_id', Auth::user()->tenant_id)->first();
                        $data['patient_id'] = $patientId->patient_id;

                        $birthReport = DeathReport::find($record->id);

                        $birthReport->update($data);

                        return
                            Notification::make()
                                ->title(__('messages.flash.death_report_updated'))
                                ->success()
                                ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function ($record) {
                        if (! canAccessRecord(DeathReport::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.death_report_not_found'))
                                ->send();
                        }

                        if (getLoggedInUser()->hasRole('Doctor')) {
                            $patientCaseHasDoctor = DeathReport::whereId($record->id)->whereDoctorId(getLoggedInUser()->owner_id)->exists();
                            if (! $patientCaseHasDoctor) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.death_report_not_found'))
                                    ->send();
                            }
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.death_report_deleted'))
                            ->send();
                    }),
            ])->actionsColumnLabel('Action')
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'viewCase' => Pages\ViewDeathReportCase::route('/{record}/view-case'),
            'view' => Pages\ViewDeathReports::route('/{record}'),
            'index' => Pages\ManageDeathReports::route('/'),
        ];
    }
}
