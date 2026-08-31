<?php

namespace App\Filament\HospitalAdmin\Clusters\Odontogram\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Doctor;
use App\Models\Patient;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\ViewField;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Odontogram as ModelOdontogram;
use App\Filament\HospitalAdmin\Clusters\Odontogram;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Odontogram\Resources\OdontogramResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Odontogram\Resources\OdontogramResource\RelationManagers;
use Filament\Tables\Actions\Action;

class OdontogramResource extends Resource
{
    protected static ?string $model = ModelOdontogram::class;

    protected static ?string $cluster = Odontogram::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('messages.odontogram.odontograms');
    }

    public static function getLabel(): string
    {
        return __('messages.odontogram.odontogram');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse', 'Receptionist']) && !getModuleAccess('Odontograms')) {
            return false;
        } elseif (!auth()->user()->hasRole('Admin') && !getModuleAccess('Odontograms')) {
            return false;
        }
        return true;
    }
    public static function canEdit(Model $record): bool
    {

        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse', 'Receptionist'])) {
            return true;
        }
        return false;
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse', 'Receptionist'])) {
            return true;
        }
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse', 'Receptionist'])) {

            return true;
        }
        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse', 'Receptionist', 'Patient'])) {
            return true;
        }
        return false;
    }

    public static $odontogram = '';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns([
                        'default' => 12,
                        'sm' => 12,
                        'md' => 12,
                        'lg' => 12,
                        'xl' => 12,
                    ])
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(6)
                            ->schema([
                                Select::make('patient_id')
                                    ->label(__('messages.case.patient') . ':')
                                    ->options(
                                        Patient::with('patientUser')
                                            ->get()
                                            ->where('patientUser.tenant_id', '=', getLoggedInUser()->tenant_id)
                                            ->where('patientUser.status', '=', 1)
                                            ->pluck('patientUser.full_name', 'id')
                                            ->sort()
                                    )
                                    ->placeholder(__('messages.document.select_patient'))
                                    ->native()
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('messages.fields.the') . ' ' . __('messages.case.patient') . ' ' . __('messages.fields.required'),
                                    ]),

                                Select::make('doctor_id')
                                    ->label(__('messages.case.doctor') . ':')
                                    ->options(
                                        Doctor::with('doctorUser')
                                            ->get()
                                            ->where('doctorUser.tenant_id', '=', getLoggedInUser()->tenant_id)
                                            ->where('doctorUser.status', '=', 1)
                                            ->pluck('doctorUser.full_name', 'id')
                                            ->sort()
                                    )
                                    ->placeholder(__('messages.web_home.select_doctor'))
                                    ->native()
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('messages.fields.the') . ' ' . __('messages.case.doctor') . ' ' . __('messages.fields.required'),
                                    ]),

                                Textarea::make('description')
                                    ->label(__('messages.common.description') . ':')
                                    ->required()
                                    ->placeholder(__('messages.common.description'))
                                    ->rows(6)
                                    ->validationMessages([
                                        'required' => __('messages.fields.the') . ' ' . __('messages.common.description') . ' ' . __('messages.fields.required'),
                                    ]),
                            ]),
                        ViewField::make('odontogram')
                            ->view('forms.components.odontogram-view')
                            ->live()
                            ->label('')
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the') . ' ' . __('messages.odontogram.odontogram') . ' ' . __('messages.fields.required'),
                            ])
                            ->columnSpan(6),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $tenantId = $user->tenant_id;

                $query->whereHas('patient.patientUser', function (Builder $query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                });

                if ($user->hasRole('Patient')) {
                    $query->where('patient_id', $user->owner_id);
                }

                if ($user->hasRole('Doctor')) {
                    $doctor = Doctor::where('user_id', $user->id)->first();
                    if ($doctor) {
                        $query->where('doctor_id', $doctor->id);
                    } else {
                        $query->where('id', 0);
                    }
                }

                return $query;
            })
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.role.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (!$record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.user.full_name')
                    ->label('')
                    ->color('primary')
                    ->description(fn($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn($record) => '<a href="' . PatientResource::getUrl('view', ['record' => $record->patient->id]) . '"class="hoverLink">' . $record->patient->patientUser->full_name . '</a>')
                    ->html()
                    ->searchable(['users.first_name', 'users.last_name']),
                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.role.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (!$record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(
                        fn($record) => Auth::user()->hasRole(['Admin', 'Doctor', 'Case Manager', 'Receptionist', 'Pharmacist', 'Lab Technician']) ? DoctorResource::getUrl('view', ['record' => $record->doctor->id]) : ''
                    )
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(
                        function ($record) {
                            $user = auth()->user();
                            $allowedRoles = ['Admin', 'Doctor', 'Receptionist', 'Patient', 'Nurse'];

                            if ($user->hasRole($allowedRoles)) {
                                return '<a href="' . DoctorResource::getUrl('view', ['record' => $record->doctor->id]) . '" class="hoverLink">' . $record->doctor->doctorUser->full_name . '</a>';
                            }

                            return $record->doctor->doctorUser->full_name;
                        }
                    )
                    ->html()
                    ->description(fn($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->actionsColumnLabel(__('messages.common.action'))
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (ModelOdontogram $record, array $data) {
                        $record->update($data);
                        return $record;
                    })
                    ->iconButton()
                    ->successNotificationTitle(__('messages.flash.odontogram_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.odontogram_deleted')),
                Action::make('print')
                    ->url(fn($record) => route('odontogram.pdf', ['odontogram' => $record->id]))
                    ->openUrlInNewTab()
                    ->icon('heroicon-s-printer')
                    ->iconButton(),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOdontograms::route('/'),
        ];
    }
}