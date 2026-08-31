<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Reports;
use App\Filament\HospitalAdmin\Clusters\Reports\Resources\InvestigationReportResource\Pages;
use App\Models\Doctor;
use App\Models\InvestigationReport;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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

class InvestigationReportResource extends Resource
{
    protected static ?string $model = InvestigationReport::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Reports::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Investigation Reports')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin']) && ! getModuleAccess('Investigation Reports')) {
            return false;
        }

        return true;
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Investigation Reports')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Investigation Reports')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && getModuleAccess('Investigation Reports')) {
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
        if (! getLoggedInUser()->hasRole('Doctor')) {
            $doctorId =
                Forms\Components\Select::make('doctor_id')
                    ->label(__('messages.investigation_report.doctor').':')
                    ->options(Doctor::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                    ->native(false)
                    ->searchable()
                    ->optionsLimit(count(Doctor::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()))
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.investigation_report.doctor').' '.__('messages.fields.required'),
                    ]);
        } else {
            $doctorId = Hidden::make('doctor_id')->default(Doctor::where([
                'tenant_id' => getLoggedInUser()->tenant_id,
                'user_id' => getLoggedInUserId(),
            ])->value('id'));
        }

        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->validationAttribute(__('messages.investigation_report.title'))
                            ->label(__('messages.investigation_report.title').':')
                            ->placeholder(__('messages.investigation_report.title'))
                            ->maxLength(191),

                        Forms\Components\Select::make('patient_id')
                            ->label(__('messages.investigation_report.patient').':')
                            ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                            ->required()
                            ->native(false)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.investigation_report.patient').' '.__('messages.fields.required'),
                            ]),

                        $doctorId,
                        Forms\Components\DateTimePicker::make('date')
                            ->label(__('messages.investigation_report.date').':')
                            ->native(false)
                            ->default(now())
                            ->placeholder(__('messages.investigation_report.date'))
                            ->validationAttribute(__('messages.investigation_report.date'))
                            ->required(),

                        SpatieMediaLibraryFileUpload::make('Attachment')
                            ->label(__('messages.investigation_report.attachment').':')
                            ->disk(config('app.media_disk'))
                            ->collection(InvestigationReport::COLLECTION_REPORTS),

                        Forms\Components\Select::make('status')
                            ->required()
                            ->validationAttribute(__('messages.common.status'))
                            ->label(__('messages.common.status'))
                            ->options(InvestigationReport::STATUS_ARR)
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label(__('messages.investigation_report.description').':')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Patient']) && ! getModuleAccess('Investigation Reports')) {
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
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.users'))
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

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->badge()
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->status == 1 ? 'Solved' : 'Not Solved';
                    }),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.investigation_report_updated')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (InvestigationReport $record) {
                        if (! canAccessRecord(InvestigationReport::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.investigation_report_not_found'))
                                ->send();
                        }

                        if (getLoggedInUser()->hasRole('Doctor')) {
                            $patientCaseHasDoctor = InvestigationReport::whereId($record->id)->whereDoctorId(getLoggedInUser()->owner_id)->exists();
                            if (! $patientCaseHasDoctor) {
                                return Notification::make()
                                    ->danger()
                                    ->title(__('messages.flash.investigation_report_not_found'))
                                    ->send();
                            }
                        }

                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.flash.investigation_report_deleted'))
                            ->send();
                    })
                    ->successNotificationTitle(__('messages.flash.investigation_report_deleted')),
            ])->actionsColumnLabel('Action')
            ->bulkActions([
                //
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
            'index' => Pages\ListInvestigationReports::route('/'),
            'create' => Pages\CreateInvestigationReport::route('/create'),
            'view' => Pages\ViewInvestigationReport::route('/{record}'),
            'edit' => Pages\EditInvestigationReport::route('/{record}/edit'),
        ];
    }
}
