<?php

namespace App\Filament\HospitalAdmin\Clusters\BedManagement\Resources;

use App\Filament\HospitalAdmin\Clusters\BedManagement;
use App\Filament\HospitalAdmin\Clusters\BedManagement\Resources\BedAssignResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Bed;
use App\Models\BedAssign;
use App\Models\BedType;
use App\Models\IpdPatientDepartment;
use App\Models\PatientCase;
use App\Models\User;
use App\Repositories\BedAssignRepository;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BedAssignResource extends Resource
{
    protected static ?string $model = BedAssign::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = BedManagement::class;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor']) && ! getModuleAccess('Bed Assigns')) {
            return false;
        } elseif (! auth()->user()->hasRole('Admin') && ! getModuleAccess('Bed Assigns')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.bed_assigns');
    }

    public static function getLabel(): string
    {
        return __('messages.bed_assigns');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse']) && getModuleAccess('Bed Assigns')) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse']) && getModuleAccess('Bed Assigns')) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse']) && getModuleAccess('Bed Assigns')) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        $bed_id = request()->get('bed_id');

        return $form
            ->live()
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('case_id')
                            ->label(__('messages.bed_assign.case_id'))
                            ->options(function () {
                                $bedAssignRepo = app(BedAssignRepository::class);

                                return $bedAssignRepo->getCases();
                            })
                            ->live()
                            ->afterStateUpdated(function ($set, $state) {
                                if ($state) {
                                    $caseId = PatientCase::whereCaseId($state)->first()->id;
                                    $patientId = PatientCase::whereCaseId($state)->first()->patient_id;
                                    $ipdPatient = IpdPatientDepartment::whereCaseId($caseId)->first();
                                    if ($patientId) {
                                        $set('patient_id', $patientId);
                                    }
                                    if ($ipdPatient != null) {
                                        return $set('ipd_patient_department_id', $ipdPatient->id);
                                    }
                                }

                                return $set('ipd_patient_department_id', null);
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.bed_assign.case_id').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('ipd_patient_department_id')
                            ->live()
                            ->placeholder(function (Get $get) {
                                if ($get('case_id')) {
                                    $caseId = PatientCase::whereCaseId($get('case_id'))->first()->id;
                                    $ipdPatient = IpdPatientDepartment::whereCaseId($caseId)->pluck('ipd_number', 'id');
                                    if (! empty($ipdPatient)) {
                                        return __('messages.ipd_patient.ipd_patient').':';
                                    }
                                }

                                return __('messages.new_change.select_ipd_patient');
                            })
                            ->label(__('messages.ipd_patient.ipd_patient').':')
                            ->preload()
                            ->options(function (Get $get) {
                                if ($get('case_id')) {
                                    $caseId = PatientCase::whereCaseId($get('case_id'))->first()->id;
                                    $ipdPatient = IpdPatientDepartment::whereCaseId($caseId)->pluck('ipd_number', 'id');

                                    return $ipdPatient;
                                }

                                return [];
                            })
                            ->disabled(function (Get $get) {
                                if ($get('case_id')) {
                                    $caseId = PatientCase::whereCaseId($get('case_id'))->first()->id;
                                    $ipdPatient = IpdPatientDepartment::whereCaseId($caseId)->pluck('ipd_number', 'id')->toArray();
                                    if (! empty($ipdPatient) && $ipdPatient != null) {
                                        return false;
                                    }

                                    return true;
                                }

                                return true;
                            })
                            ->native(false)
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.ipd_patient.ipd_patient').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\Select::make('bed_type_id')
                            ->label(__('messages.bed.bed_type').':')
                            ->placeholder(__('messages.bed.select_bed_type'))
                            ->options(fn () => BedType::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('title', 'id'))
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record?->bed?->bed_type) {
                                    $set('bed_type_id', $record->bed->bed_type);
                                }
                            })
                            ->afterStateUpdated(fn ($set) => $set('bed_id', null))
                            ->searchable()
                            ->native(false)
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('bed_id')
                            ->label(__('messages.bed.bed_id').':')
                            ->default($bed_id)
                            ->options(function (Get $get, $record) {
                                $bedTypeId = $get('bed_type_id');
                                if (! $bedTypeId) {
                                    return [];
                                }

                                return Bed::availableByType($bedTypeId, $record?->bed_id)->pluck('name', 'id')->toArray();
                            })
                            ->placeholder(__('messages.bed.select_bed'))
                            ->searchable()
                            ->native(false)
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.bed.bed_id').' '.__('messages.fields.required'),
                            ]),
                        Forms\Components\DatePicker::make('assign_date')
                            ->label(__('messages.bed_assign.assign_date'))
                            ->native(false)
                            ->minDate(fn ($operation, $state) => $operation == 'edit' ? $state : today())
                            ->validationAttribute(__('messages.bed_assign.assign_date'))
                            ->required(),
                        Forms\Components\DatePicker::make('discharge_date')
                            ->label(__('messages.bed_assign.discharge_date'))
                            ->native(false)
                            ->minDate(today())
                            ->visibleOn('edit')
                            ->validationAttribute(__('messages.bed_assign.discharge_date')),
                        Textarea::make('description')
                            ->label(__('messages.common.description'))
                            ->placeholder(__('messages.common.description'))
                            ->rows(3),
                        Hidden::make('patient_id'),
                        Forms\Components\Toggle::make('status')
                            ->inline(false)
                            ->label(__('messages.user.status'))
                            ->validationAttribute(__('messages.user.status'))
                            ->required(),
                    ])->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Doctor', 'Nurse']) && ! getModuleAccess('Bed Assigns')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('case_id')
                    ->badge()
                    ->sortable()
                    ->url(fn ($record) => BedAssignResource::getUrl('view', ['record' => $record->id]))
                    ->label(__('messages.bed_assign.case_id'))
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.invoice.patient'))
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
                    ->description(function (BedAssign $record) {
                        return $record->patient->user->email;
                    })
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['first_name', 'last_name', 'email']),
                Tables\Columns\TextColumn::make('bed.name')
                    ->label(__('messages.bed_assign.bed'))
                    ->html()
                    ->formatStateUsing(fn ($state, $record) => '<a href="'.BedResource::getUrl('view', ['record' => $record->bed->id]).'"class="hoverLink">'.$state.'</a>')
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assign_date')
                    ->label(__('messages.bed_assign.assign_date'))
                    ->getStateUsing(fn ($record) => $record->assign_date ? Carbon::parse($record->assign_date)->isoFormat('MMM D, YYYY') : __('messages.common.n/a'))
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discharge_date')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.bed_assign.discharge_date'))
                    ->getStateUsing(fn ($record) => $record->discharge_date ? Carbon::parse($record->discharge_date)->isoFormat('MMM D, YYYY') : __('messages.common.n/a'))
                    ->badge()
                    // ->color(fn($record) => !$record->discharge_date ?: 'primary')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('status')
                    ->label(__('messages.user.status'))
                    ->updateStateUsing(function ($record, $state) {
                        $state ? $record->status = 1 : $record->status = 0;
                        $record->save();
                        $record->bed->update(['is_available' => 1]);
                        Notification::make()
                            ->title(__('messages.common.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordAction(null)
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        '' => __('messages.filter.all'),
                        1 => __('messages.filter.active'),
                        0 => __('messages.filter.deactive'),
                    ])
                    ->native(false)
                    ->label(__('messages.common.status').':'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton()->successNotificationTitle(__('messages.flash.bed_assign_update')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (BedAssign $record) {
                        if (! canAccessRecord(BedAssign::class, $record->id)) {
                            return Notification::make()
                                ->danger()
                                ->title(__('messages.flash.bed_assign_not_found'))
                                ->send();
                        }

                        $record->bed->update(['is_available' => 1]);

                        $record->delete();

                        return Notification::make()
                            ->title(__('messages.flash.bed_assign_delete'))
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
            'index' => Pages\ListBedAssigns::route('/'),
            'create' => Pages\CreateBedAssign::route('/create'),
            'view' => Pages\ViewBedAssign::route('/{record}'),
            'edit' => Pages\EditBedAssign::route('/{record}/edit'),
        ];
    }
}
