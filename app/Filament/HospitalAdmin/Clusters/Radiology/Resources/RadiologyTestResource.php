<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Filament\HospitalAdmin\Clusters\Radiology;
use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\RadiologyTestResource\Pages;
use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\RadiologyCategory;
use App\Models\RadiologyTest;
use App\Models\User;
use App\Repositories\PatientRepository;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RadiologyTestResource extends Resource
{
    protected static ?string $model = RadiologyTest::class;

    protected static ?string $cluster = Radiology::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Radiology Tests')) {
            return false;
        } elseif (! auth()->user()->hasRole(['Admin', 'Receptionist']) && ! getModuleAccess('Radiology Tests')) {
            return false;
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.radiology_tests');
    }

    public static function getLabel(): string
    {
        return __('messages.radiology_tests');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Pharmacist', 'Lab Technician'])) {
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
                            ->label(__('messages.role.patient').':')
                            ->placeholder(__('messages.document.select_patient'))
                            ->options(app(PatientRepository::class)->getPatients())
                            ->getSearchResultsUsing(fn (string $search) => searchPatientSelectOptions($search))
                            ->required()
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->optionsLimit(500)
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.pathology_test.test_name').' '.__('messages.fields.required'),
                            ]),
                        Select::make('doctor_id')
                            ->label(__('messages.case.doctor').':')
                            ->placeholder(__('messages.web_home.select_doctor'))
                            ->options(fn () => getDoctorSelectOptions())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->default(fn () => getLoggedinDoctor() ? auth()->user()->owner_id : null)
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.case.doctor').' '.__('messages.fields.required'),
                            ]),
                        Select::make('status')
                            ->label(__('messages.common.status').':')
                            ->options([
                                0 => __('messages.appointment.pending'),
                                1 => __('messages.appointment.completed'),
                            ])
                            ->default(0)
                            ->native(false)
                            ->required(),
                        TextInput::make('test_name')
                            ->label(__('messages.pathology_test.test_name').':')
                            ->placeholder(__('messages.pathology_test.test_name'))
                            ->maxLength(255)
                            ->validationAttribute(__('messages.pathology_test.test_name'))
                            ->required(),
                        TextInput::make('short_name')
                            ->label(__('messages.pathology_test.short_name').':')
                            ->placeholder(__('messages.pathology_test.short_name'))
                            ->maxLength(255)
                            ->validationAttribute(__('messages.pathology_test.short_name'))
                            ->required(),
                        TextInput::make('test_type')
                            ->label(__('messages.pathology_test.test_type').':')
                            ->placeholder(__('messages.pathology_test.test_type'))
                            ->maxLength(255)
                            ->validationAttribute(__('messages.pathology_test.test_type'))
                            ->required(),
                        Select::make('category_id')
                            ->label(__('messages.radiology_test.category_name').':')
                            ->options(RadiologyCategory::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id')->sort())
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.radiology_test.category_name').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('subcategory')
                            ->label(__('messages.radiology_test.subcategory').':')
                            ->placeholder(__('messages.radiology_test.subcategory'))
                            ->maxLength(255),
                        TextInput::make('report_days')
                            ->label(__('messages.radiology_test.report_days').':')
                            ->placeholder(__('messages.radiology_test.report_days'))
                            ->maxLength(255),
                        Select::make('charge_category_id')
                            ->live()
                            ->label(__('messages.pathology_test.charge_category').':')
                            ->placeholder(__('messages.pathology_category.select_charge_category'))
                            ->required()
                            ->options(ChargeCategory::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                            ->afterStateUpdated(function ($set, $get) {
                                $id = $get('charge_category_id');
                                $charge_id = Charge::where('charge_category_id', $id)->pluck('id')->first();
                                $set('charge_id', $charge_id);
                                if ($charge_id) {
                                    $set('standard_charge', Charge::where('charge_category_id', $id)->value('standard_charge'));
                                }
                            })
                            ->searchable()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.pathology_test.charge_category').' '.__('messages.fields.required'),
                            ]),
                        Select::make('charge_id')
                            ->live()
                            ->label(__('messages.delete.charge').':')
                            ->placeholder(__('messages.new_change.select_charge'))
                            ->options(function (callable $get) {
                                $id = $get('charge_category_id');

                                return Charge::where('charge_category_id', $id)->pluck('code', 'id');
                            })
                            // ->disabled(function (callable $get) {
                            //     $id = $get('charge_category_id');
                            //     $charge_id = Charge::where('charge_category_id', $id)->pluck('code', 'id');
                            //     if (!empty($charge_id->toArray())) {
                            //         return false;
                            //     }
                            //     return true;
                            // })
                            ->native(false)
                            ->searchable()
                            ->afterStateUpdated(function ($set, $get, $state) {
                                $id = $get('charge_category_id');
                                $charge_id = Charge::where('charge_category_id', $id)->where('id', $state)
                                    ->value('standard_charge');
                                if ($id && $get('charge_id')) {
                                    $set('standard_charge', $charge_id);
                                }
                            })
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.delete.charge').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('standard_charge')
                            ->live()
                            ->required()
                            ->validationAttribute(__('messages.radiology_test.standard_charge'))
                            ->readOnly()
                            ->label(function () {
                                if (getCurrencySymbol() != null) {
                                    return __('messages.radiology_test.standard_charge').' : '.'('.getCurrencySymbol().')';
                                }

                                return __('messages.radiology_test.standard_charge').':';
                            })
                            ->placeholder(__('messages.radiology_test.standard_charge'))
                            ->readOnly(fn ($state) => $state == null ?? true),
                    ])->columns(4),

                ...self::testResultsDocumentSchema(),

            ])->columns(1);
    }

    public static function testResultsDocumentSchema(): array
    {
        return [
            Section::make(__('messages.radiology_test.test_results_document'))
                ->description(__('messages.radiology_test.test_results_document_description'))
                ->schema([
                    Select::make('result_status')
                        ->label(__('messages.radiology_test.result_status'))
                        ->placeholder(__('messages.radiology_test.select_result_status'))
                        ->options([
                            RadiologyTest::RESULT_NORMAL => __('messages.radiology_test.normal'),
                            RadiologyTest::RESULT_ABNORMAL => __('messages.radiology_test.abnormal'),
                        ])
                        ->required()
                        ->native(false)
                        ->helperText(__('messages.radiology_test.result_status_helper')),
                    FileUpload::make('document_path')
                        ->label(__('messages.radiology_test.upload_test_result_document'))
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->maxSize(10240)
                        ->directory('radiology-test-documents')
                        ->preserveFilenames()
                        ->helperText(__('messages.radiology_test.upload_document_helper'))
                        ->downloadable()
                        ->previewable()
                        ->openable()
                        ->live()
                        ->afterStateUpdated(function ($state, $set) {
                            if (! $state) {
                                return;
                            }

                            if (is_array($state)) {
                                $state = $state[0] ?? null;
                            }

                            if (! $state) {
                                return;
                            }

                            $name = $state instanceof TemporaryUploadedFile
                                ? $state->getClientOriginalName()
                                : basename((string) $state);

                            if ($name) {
                                $set('result_document_name', $name);
                                $set('uploaded_at', now());
                            }
                        }),
                    TextInput::make('result_document_name')
                        ->label(__('messages.radiology_test.document_name'))
                        ->disabled()
                        ->dehydrated()
                        ->placeholder(__('messages.radiology_test.document_name_placeholder')),
                    DateTimePicker::make('uploaded_at')
                        ->label(__('messages.radiology_test.uploaded_at'))
                        ->disabled()
                        ->dehydrated()
                        ->placeholder(__('messages.radiology_test.document_name_placeholder')),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
    }

    public static function table(Table $table): Table
    {
        if (auth()->user()->hasRole(['Admin', 'Receptionist', 'Pharmacist', 'Lab Technician']) && ! getModuleAccess('Radiology Tests')) {
            abort(404);
        }

        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('test_name')
                    ->label(__('messages.pathology_test.test_name'))
                    ->getStateUsing(fn ($record) => $record->test_name ?? __('messages.common.n/a'))
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! empty($record->patient->user) && ! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.user.full_name')
                    ->label(__('messages.case.patient'))
                    ->default(__('messages.common.n/a'))
                    ->description(function ($record) {
                        if (empty($record->patient->user)) {
                            return '';
                        }

                        return $record->patient->user->email;
                    })
                    ->sortable(['first_name'])
                    ->html()
                    ->formatStateUsing(fn ($record) => empty($record->patient) ? '<span>'.__('messages.common.n/a').'</span>' : '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'"class="hoverLink font-bold">'.$record->patient->user->full_name.'</a>')
                    ->color('primary')
                    ->searchable(['first_name', 'last_name', 'email']),
                Tables\Columns\TextColumn::make('short_name')
                    ->label(__('messages.pathology_test.short_name'))
                    ->getStateUsing(fn ($record) => $record->short_name ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('test_type')
                    ->label(__('messages.pathology_test.test_type'))
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->test_type ?? __('messages.common.n/a'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('category_id')
                    ->label(__('messages.medicine.category'))
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->radiologycategory->name ?? __('messages.common.n/a'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('chargecategory.name')
                    ->label(__('messages.charge.charge_category'))
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record->chargecategory->name ?? __('messages.common.n/a'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.doctorUser.full_name')
                    ->label(__('messages.case.doctor'))
                    ->default(__('messages.common.n/a'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->status ? __('messages.appointment.completed') : __('messages.appointment.pending'))
                    ->color(fn ($record) => $record->status ? 'success' : 'warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('result_status')
                    ->label(__('messages.radiology_test.result_status'))
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return match ($record->result_status) {
                            RadiologyTest::RESULT_NORMAL => __('messages.radiology_test.normal'),
                            RadiologyTest::RESULT_ABNORMAL => __('messages.radiology_test.abnormal'),
                            default => __('messages.common.n/a'),
                        };
                    })
                    ->color(fn ($record) => match ($record->result_status) {
                        RadiologyTest::RESULT_NORMAL => 'success',
                        RadiologyTest::RESULT_ABNORMAL => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('result_document_name')
                    ->label(__('messages.radiology_test.document_name'))
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label(__('messages.radiology_test.uploaded_at'))
                    ->dateTime('d M, Y')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('messages.common.created_at'))
                    ->dateTime('jS M, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('messages.common.status'))
                    ->options([
                        0 => __('messages.appointment.pending'),
                        1 => __('messages.appointment.completed'),
                    ])
                    ->native(false),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')->label(__('messages.appointment.start_date')),
                        \Filament\Forms\Components\DatePicker::make('created_until')->label(__('messages.appointment.end_date')),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordUrl(null)
            // ->recordAction(null)
            ->actions([
                // Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\Action::make('download_document')
                    ->label(__('messages.document.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => ! empty($record->document_path))
                    ->action(function ($record) {
                        if (! $record->document_path || ! Storage::exists($record->document_path)) {
                            Notification::make()
                                ->title(__('messages.flash.radiology_test_not_found'))
                                ->danger()
                                ->send();

                            return;
                        }

                        return Storage::download($record->document_path, $record->result_document_name ?: basename($record->document_path));
                    }),
                Tables\Actions\Action::make('complete')
                    ->iconButton()
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->tooltip(__('messages.appointment.completed'))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 1,
                            'uploaded_at' => $record->uploaded_at ?? now(),
                        ]);

                        Notification::make()
                            ->title(__('messages.flash.radiology_test_updated'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => ! $record->status),
                Tables\Actions\EditAction::make()->iconButton()->action(function ($record) {
                    if (! canAccessRecord($record, $record->id)) {
                        Notification::make()
                            ->title(__('messages.flash.not_allow_access_record'))
                            ->danger()
                            ->send();

                        return Redirect::back();
                    }
                }),
                Tables\Actions\DeleteAction::make()->iconButton()->action(function ($record) {
                    if (! canAccessRecord($record, $record->id)) {
                        return Notification::make()
                            ->title(__('messages.flash.not_allow_access_record'))
                            ->danger()
                            ->send();
                    }

                    if ($record->document_path && Storage::exists($record->document_path)) {
                        Storage::delete($record->document_path);
                    }

                    $record->delete();

                    return Notification::make()
                        ->title(__('messages.flash.radiology_test_deleted'))
                        ->success()
                        ->send();
                }),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('test_name')
                    ->label(__('messages.pathology_test.test_name').':')
                    ->getStateUsing(fn ($record) => $record->test_name ?? __('messages.common.n/a')),
                TextEntry::make('short_name')
                    ->label(__('messages.pathology_test.short_name').':')
                    ->getStateUsing(fn ($record) => $record->short_name ?? __('messages.common.n/a')),
                TextEntry::make('test_type')
                    ->label(__('messages.pathology_test.test_type').':')
                    ->getStateUsing(fn ($record) => $record->test_type ?? __('messages.common.n/a')),
                TextEntry::make('category_id')
                    ->label(__('messages.radiology_test.category_name').':')
                    ->getStateUsing(fn ($record) => $record->radiologycategory->name ?? __('messages.common.n/a')),
                TextEntry::make('subcategory')
                    ->label(__('messages.radiology_test.subcategory').':')
                    ->getStateUsing(fn ($record) => (! empty($record->subcategory)) ? $record->subcategory : __('messages.common.n/a')),
                TextEntry::make('report_days')
                    ->label(__('messages.radiology_test.report_days').':')
                    ->getStateUsing(fn ($record) => (! empty($record->report_days)) ? $record->report_days : __('messages.common.n/a')),
                TextEntry::make('chargecategory.name')
                    ->label(__('messages.charge.charge_category').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('standard_charge')
                    ->label(__('messages.radiology_test.standard_charge').':')
                    ->default(__('messages.common.n/a'))
                    ->getStateUsing(fn ($record) => getCurrencyFormat($record->standard_charge)),
                TextEntry::make('doctor.doctorUser.full_name')
                    ->label(__('messages.case.doctor').':')
                    ->default(__('messages.common.n/a')),
                TextEntry::make('status')
                    ->label(__('messages.common.status').':')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->status ? __('messages.appointment.completed') : __('messages.appointment.pending')),
                TextEntry::make('result_status')
                    ->label(__('messages.radiology_test.result_status').':')
                    ->badge()
                    ->getStateUsing(fn ($record) => match ($record->result_status) {
                        RadiologyTest::RESULT_NORMAL => __('messages.radiology_test.normal'),
                        RadiologyTest::RESULT_ABNORMAL => __('messages.radiology_test.abnormal'),
                        default => __('messages.common.n/a'),
                    }),
                TextEntry::make('result_document_name')
                    ->label(__('messages.radiology_test.document_name').':')
                    ->getStateUsing(fn ($record) => $record->result_document_name ?? __('messages.radiology_test.no_document_uploaded')),
                TextEntry::make('uploaded_at')
                    ->label(__('messages.radiology_test.uploaded_at').':')
                    ->dateTime('d M, Y h:i A')
                    ->placeholder(__('messages.common.n/a')),
                TextEntry::make('created_at')
                    ->label(__('messages.common.created_at').':')
                    ->since(),
                TextEntry::make('updated_at')
                    ->label(__('messages.common.last_updated').':')
                    ->since(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRadiologyTests::route('/'),
            'create' => Pages\CreateRadiologyTest::route('/create'),
            'edit' => Pages\EditRadiologyTest::route('/{record}/edit'),
        ];
    }

    public static function normalizeDocumentData(array $data): array
    {
        if (isset($data['document_path']) && is_array($data['document_path'])) {
            $data['document_path'] = $data['document_path'][0] ?? null;
        }

        return $data;
    }
}
