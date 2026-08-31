<?php

namespace App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Vaccinations;
use App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources\VaccinatedPatientsResource\Pages;
use App\Models\Patient;
use App\Models\User;
use App\Models\VaccinatedPatients;
use App\Models\Vaccination;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VaccinatedPatientsResource extends Resource
{
    protected static ?string $model = VaccinatedPatients::class;

    protected static ?string $cluster = Vaccinations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Vaccinated Patients')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Vaccinated Patients')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.vaccinated_patients');
    }

    public static function getLabel(): string
    {
        return __('messages.vaccinated_patients');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole('Admin')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Patient'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label(__('messages.document.patient').': ')
                    ->placeholder(__('messages.document.select_patient'))
                    ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.document.patient').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\Select::make('vaccination_id')
                    ->label(__('messages.vaccinated_patient.vaccine').': ')
                    ->placeholder(__('messages.vaccination.select_vaccination'))
                    ->options(Vaccination::where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('name', 'id'))
                    ->native(false)
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.vaccinated_patient.vaccine').' '.__('messages.fields.required'),
                    ]),
                Forms\Components\TextInput::make('vaccination_serial_number')
                    ->label(__('messages.vaccinated_patient.serial_no').': ')
                    ->placeholder(__('messages.vaccinated_patient.serial_no'))
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('dose_number')
                    ->label(__('messages.vaccinated_patient.does_no').': ')
                    ->placeholder(__('messages.vaccinated_patient.does_no'))
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.vaccinated_patient.does_no'))
                    ->minValue(1)
                    ->rules(['numeric', 'lte:50']),
                Forms\Components\DateTimePicker::make('dose_given_date')
                    ->native(false)
                    ->label(__('messages.vaccinated_patient.dose_given_date').': ')
                    ->placeholder(__('messages.vaccinated_patient.dose_given_date'))
                    ->required()
                    ->validationAttribute(__('messages.vaccinated_patient.dose_given_date'))
                    ->default(now()),
                Forms\Components\Textarea::make('description')
                    ->label(__('messages.document.notes').': ')
                    ->placeholder(__('messages.document.notes'))
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Patient']) && ! getModuleAccess('Vaccinated Patients')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id);
            if (auth()->user()->hasRole('Patient')) {
                $query->where('patient_id', auth()->user()->owner_id);
            }

            return $query;
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.appointment.patient'))
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
                Tables\Columns\TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$record->patient->patientUser->full_name.'</a>')
                    ->html()
                    ->description(fn ($record) => $record->patient->patientUser->email ?? 'N/A')
                    ->searchable(['users.first_name', 'users.last_name']),
                Tables\Columns\TextColumn::make('vaccination.name')
                    ->label(__('messages.vaccinated_patient.vaccination'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vaccination_serial_number')
                    ->label(__('messages.vaccinated_patient.serial_no'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('dose_number')
                    ->label(__('messages.vaccinated_patient.does_no'))
                    ->searchable()
                    ->color('info')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('dose_given_date')
                    ->label(__('messages.vaccinated_patient.dose_given_date'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->extraAttributes(['class' => 'text-center'])
                    ->formatStateUsing(
                        fn ($state) => Carbon::parse($state)->format('g:i A').'<br>'.Carbon::parse($state)->format('jS M, Y')
                    )
                    ->html(),
            ])
            ->recordAction(null)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->modalHeading(__('messages.vaccinated_patient.edit_vaccinate_patient'))->successNotificationTitle(__('messages.flash.vaccinated_patients_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.vaccinated_patients_deleted')),
            ])->actionsColumnLabel((auth()->user()->hasRole('Patient')) ? '' : __('messages.common.action'))
            ->bulkActions([])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVaccinatedPatients::route('/'),
        ];
    }
}
