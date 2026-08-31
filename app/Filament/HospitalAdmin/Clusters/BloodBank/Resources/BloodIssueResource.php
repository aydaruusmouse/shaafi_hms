<?php

namespace App\Filament\HospitalAdmin\Clusters\BloodBank\Resources;

use App\Filament\HospitalAdmin\Clusters\BloodBank;
use App\Filament\HospitalAdmin\Clusters\BloodBank\Resources\BloodIssueResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\BloodDonor;
use App\Models\BloodIssue;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BloodIssueResource extends Resource
{
    protected static ?string $model = BloodIssue::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = BloodBank::class;

    public static function getLabel(): string
    {
        return __('messages.delete.blood_issue');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Issues')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Blood Issues')) {
            return false;
        }

        return true;
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Issues')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Issues')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && getModuleAccess('Blood Issues')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('issue_date')
                    ->placeholder(__('messages.blood_issue.issue_date'))
                    ->label(__('messages.blood_issue.issue_date').': ')
                    ->native(false)
                    ->default(now())
                    ->validationAttribute(__('messages.blood_issue.issue_date'))
                    ->required(),

                Forms\Components\Select::make('doctor_id')
                    ->options(function () {
                        return Doctor::with('user')->get()->where('user.status', User::ACTIVE)->where('tenant_id', auth()->user()->tenant_id)->pluck('user.full_name', 'id');
                    })
                    ->label(__('messages.death_report.doctor_name'))
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.death_report.doctor_name').' '.__('messages.fields.required'),
                    ]),

                Forms\Components\Select::make('patient_id')
                    ->options(function () {
                        return Patient::with('user')->get()->where('user.status', User::ACTIVE)->where('tenant_id', auth()->user()->tenant_id)->pluck('user.full_name', 'id');
                    })
                    ->label(__('messages.death_report.patient_name'))
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.death_report.patient_name').' '.__('messages.fields.required'),
                    ]),

                Forms\Components\Select::make(name: 'donor_id')
                    ->options(function () {
                        return BloodDonor::all()->where('tenant_id', auth()->user()->tenant_id)->pluck('name', 'id');
                    })
                    ->live()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $bloodGroup = BloodDonor::where('id', $state)->first();
                        $set('blooddonor.blood_group', $bloodGroup->blood_group);
                    })
                    ->label(__('messages.blood_donation.donor_name'))
                    ->native(false)
                    ->searchable()
                    ->required()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.blood_donation.donor_name').' '.__('messages.fields.required'),
                    ]),

                TextInput::make('blooddonor.blood_group')
                    ->label(__('messages.delete.blood_group'))
                    ->required()
                    ->validationAttribute(__('messages.delete.blood_group'))
                    ->afterStateHydrated(function (callable $get, $operation, $component) {
                        $operation == 'edit' ? $component->state(BloodDonor::where('id', $get('donor_id'))->first()->blood_group) : null;
                    })
                    ->placeholder(__('messages.delete.blood_group'))
                    ->readOnly()
                    ->maxLength(191),

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->validationAttribute(__('messages.purchase_medicine.amount'))
                    ->label(__('messages.purchase_medicine.amount'))
                    ->placeholder(__('messages.purchase_medicine.amount'))
                    ->numeric()
                    ->minValue(1),

                Forms\Components\Textarea::make('remarks')
                    ->label(__('messages.blood_issue.remarks'))
                    ->placeholder(__('messages.blood_issue.remarks'))
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Lab Technician']) && ! getModuleAccess('Blood Issues')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.advanced_payment.patient'))
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
                    ->weight(FontWeight::SemiBold)
                    ->description(fn ($record) => $record->patient->user->email ?? 'N/A')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary'),

                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.case.doctor'))
                    ->circular()
                    ->sortable(['first_name'])
                    ->defaultImageUrl(function ($record) {
                        if (! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name);
                        }
                    })
                    ->collection('profile')
                    ->url(fn ($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->width(50)->height(50),

                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->description(fn ($record) => $record->doctor->doctorUser->email ?? 'N/A')
                    ->searchable(['users.first_name', 'users.last_name'])
                    ->html()
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.DoctorResource::getUrl('view', ['record' => $record->doctor->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary'),

                TextColumn::make('blooddonor.name')
                    ->label(__('messages.blood_donation.donor_name'))
                    ->default(__('messages.common.n/a'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label(__('messages.blood_issue.issue_date'))
                    ->view('tables.columns.hospitalAdmin.in-blood-issue-date')
                    ->sortable(),

                TextColumn::make('blooddonor.blood_group')
                    ->badge()
                    ->color('danger')
                    ->label(__('messages.delete.blood_group'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(function ($record) {
                        return getCurrencyFormat($record->amount);
                    })
                    ->label(__('messages.invoice.amount'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordAction(null)
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.blood_issue_updated')),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.flash.blood_issue_deleted')),
            ])->actionsColumnLabel(__('messages.common.action'))
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBloodIssues::route('/'),
        ];
    }
}
