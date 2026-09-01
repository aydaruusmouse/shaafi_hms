<?php

namespace App\Filament\HospitalAdmin\Clusters\Radiology\Resources;

use App\Filament\HospitalAdmin\Clusters\Radiology;
use App\Filament\HospitalAdmin\Clusters\Radiology\Resources\DoctorSuggestedRadiologyTestsResource\Pages;
use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\ConsultationRadiologyTest;
use App\Models\RadiologyCategory;
use App\Models\RadiologyTest;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DoctorSuggestedRadiologyTestsResource extends Resource
{
    protected static ?string $model = ConsultationRadiologyTest::class;

    protected static ?string $cluster = Radiology::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'doctor-suggested-radiology-tests';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) getModuleAccess('Doctor Suggested Radiology Tests');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.doctor_suggested_radiology_tests');
    }

    public static function getLabel(): string
    {
        return __('messages.doctor_suggested_radiology_tests');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return hasModulePermission('Doctor Suggested Radiology Tests', 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        return hasModulePermission('Doctor Suggested Radiology Tests', 'delete');
    }

    public static function canViewAny(): bool
    {
        return hasModulePermission('Doctor Suggested Radiology Tests');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        if (! getModuleAccess('Doctor Suggested Radiology Tests')) {
            abort(404);
        }

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('tenant_id', getLoggedInUser()->tenant_id)
                    ->where('payment_status', ConsultationRadiologyTest::PAYMENT_PAID)
                    ->whereIn('id', function ($subQuery) {
                        $subQuery->selectRaw('MIN(id)')
                            ->from('consultation_radiology_tests')
                            ->where('tenant_id', getLoggedInUser()->tenant_id)
                            ->where('payment_status', ConsultationRadiologyTest::PAYMENT_PAID)
                            ->groupBy('patient_id', 'caseable_type', 'caseable_id');
                    })
                    ->with(['patient.user', 'radiologyCategory', 'linkedRadiologyTest', 'caseable'])
                    ->orderByDesc('created_at');
            })
            ->columns([
                TextColumn::make('patient.user.full_name')
                    ->label(__('messages.case.patient'))
                    ->description(fn ($record) => $record->patient->user->email ?? __('messages.common.n/a'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doctor_name')
                    ->label(__('messages.case.doctor'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('radiologyCategory.name')
                    ->label(__('messages.radiology_test.category_name'))
                    ->formatStateUsing(function ($record) {
                        return ConsultationRadiologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
                            ->where('patient_id', $record->patient_id)
                            ->where('caseable_type', $record->caseable_type)
                            ->where('caseable_id', $record->caseable_id)
                            ->with('radiologyCategory')
                            ->get()
                            ->pluck('radiologyCategory.name')
                            ->filter()
                            ->unique()
                            ->implode(', ') ?: __('messages.common.n/a');
                    })
                    ->searchable(),
                TextColumn::make('section')
                    ->label(__('messages.section'))
                    ->badge()
                    ->color(fn ($record) => match ($record->section) {
                        'IPD' => 'success',
                        'OPD' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('messages.common.created_on'))
                    ->dateTime('d M, Y h:i A')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->color(fn ($record) => self::pendingCount($record) === 0 ? 'success' : 'warning')
                    ->formatStateUsing(fn ($record) => self::pendingCount($record) === 0 ? __('messages.appointment.completed') : __('messages.appointment.pending')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section')
                    ->label(__('messages.section'))
                    ->options([
                        'IPD' => 'IPD',
                        'OPD' => 'OPD',
                    ])
                    ->query(function (Builder $query, array $state) {
                        if (($state['value'] ?? null) === 'IPD') {
                            $query->where('caseable_type', 'like', '%Ipd%');
                        } elseif (($state['value'] ?? null) === 'OPD') {
                            $query->where('caseable_type', 'like', '%Opd%');
                        }
                    })
                    ->native(false),
                Tables\Filters\Filter::make('pending_only')
                    ->label(__('messages.appointment.pending'))
                    ->query(function (Builder $query) {
                        $query->whereIn('id', function ($subQuery) {
                            $subQuery->select('crt1.id')
                                ->from('consultation_radiology_tests as crt1')
                                ->whereExists(function ($existsQuery) {
                                    $existsQuery->select(DB::raw(1))
                                        ->from('consultation_radiology_tests as crt2')
                                        ->whereColumn('crt2.patient_id', 'crt1.patient_id')
                                        ->whereColumn('crt2.caseable_type', 'crt1.caseable_type')
                                        ->whereColumn('crt2.caseable_id', 'crt1.caseable_id')
                                        ->whereNull('crt2.radiology_test_id');
                                });
                        });
                    }),
            ])
            ->actions([
                Action::make('proceed')
                    ->label(__('messages.proceed'))
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->modalHeading(__('messages.create_radiology_test_from_suggestion'))
                    ->modalSubmitActionLabel(__('messages.common.save'))
                    ->modalWidth('6xl')
                    ->visible(fn ($record) => $record->isPaid() && self::pendingCount($record) > 0 && hasModulePermission('Doctor Suggested Radiology Tests', 'create'))
                    ->fillForm(function ($record) {
                        $pending = self::pendingTests($record);
                        $first = $pending->first();
                        $charge = Charge::where('tenant_id', getLoggedInUser()->tenant_id)->first();

                        $testName = trim((string) ($first?->getAttributes()['test_name'] ?? ''));
                        if ($testName === '' || strcasecmp($testName, 'N/A') === 0) {
                            $testName = ($first?->radiologyCategory?->name ?? 'Radiology').' Test';
                        }

                        return [
                            'patient_id' => $record->patient_id,
                            'patient_phone' => displayPatientPhone($record->patient?->user),
                            'patient_gender' => displayPatientGender($record->patient?->user),
                            'test_name' => $testName,
                            'short_name' => strtoupper(substr($first?->radiologyCategory?->name ?? 'RT', 0, 8)),
                            'test_type' => 'Radiology Test',
                            'category_id' => $first?->radiology_category_id,
                            'charge_category_id' => $charge?->charge_category_id,
                            'standard_charge' => $charge?->standard_charge ?? 0,
                            'consultation_tests' => $pending->pluck('id')->toArray(),
                        ];
                    })
                    ->form([
                        Hidden::make('consultation_tests')->dehydrated(),
                        Section::make(__('messages.radiology_test.test_name'))
                            ->schema([
                                Select::make('patient_id')
                                    ->label(__('messages.case.patient').':')
                                    ->options(fn ($record) => [$record->patient_id => $record->patient->user->full_name ?? 'N/A'])
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                TextInput::make('patient_phone')
                                    ->label(__('messages.user.phone').':')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('patient_gender')
                                    ->label(__('messages.user.gender').':')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('test_name')
                                    ->label(__('messages.radiology_test.test_name').':')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('short_name')
                                    ->label(__('messages.radiology_test.short_name').':')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('test_type')
                                    ->label(__('messages.radiology_test.test_type').':')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('category_id')
                                    ->label(__('messages.radiology_test.category_name').':')
                                    ->options(fn () => RadiologyCategory::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->native(false),
                                Select::make('charge_category_id')
                                    ->live()
                                    ->label(__('messages.radiology_test.charge_category').':')
                                    ->options(fn () => ChargeCategory::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                                    ->afterStateUpdated(function ($set, $get) {
                                        $charge = Charge::where('charge_category_id', $get('charge_category_id'))
                                            ->where('tenant_id', getLoggedInUser()->tenant_id)
                                            ->first();
                                        $set('standard_charge', $charge?->standard_charge ?? 0);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->native(false),
                                TextInput::make('standard_charge')
                                    ->label(__('messages.radiology_test.standard_charge').':')
                                    ->numeric()
                                    ->required()
                                    ->readOnly(),
                            ])->columns(4),
                        ...RadiologyTestResource::testResultsDocumentSchema(),
                    ])
                    ->action(function ($record, array $data) {
                        $consultationTestIds = is_array($data['consultation_tests'] ?? null)
                            ? $data['consultation_tests']
                            : [];

                        $data = RadiologyTestResource::normalizeDocumentData($data);
                        $hasDocument = ! empty($data['document_path']);

                        $radiologyTest = RadiologyTest::create([
                            'patient_id' => $data['patient_id'],
                            'doctor_id' => $record->doctor_id,
                            'test_name' => $data['test_name'],
                            'short_name' => $data['short_name'],
                            'test_type' => $data['test_type'],
                            'category_id' => $data['category_id'],
                            'charge_category_id' => $data['charge_category_id'],
                            'standard_charge' => $data['standard_charge'] ?? 0,
                            'status' => $hasDocument ? 1 : 0,
                            'result_status' => $data['result_status'] ?? null,
                            'document_path' => $data['document_path'] ?? null,
                            'result_document_name' => $data['result_document_name'] ?? null,
                            'uploaded_at' => $data['uploaded_at'] ?? ($hasDocument ? now() : null),
                            'payment_status' => $record->isPaid() ? RadiologyTest::PAYMENT_PAID : RadiologyTest::PAYMENT_UNPAID,
                            'payment_mode' => $record->payment_mode,
                            'paid_amount' => $record->paid_amount,
                            'paid_at' => $record->paid_at,
                            'payment_note' => $record->payment_note,
                            'consultation_radiology_test_id' => $record->id,
                            'tenant_id' => getLoggedInUser()->tenant_id,
                            'report_days' => 1,
                        ]);

                        if (! empty($consultationTestIds)) {
                            ConsultationRadiologyTest::whereIn('id', $consultationTestIds)->update([
                                'radiology_test_id' => $radiologyTest->id,
                                'processed_at' => now(),
                            ]);
                        }

                        Notification::make()
                            ->title(__('messages.flash.radiology_test_saved'))
                            ->success()
                            ->send();
                    }),
                Action::make('view_test')
                    ->label(__('messages.common.view'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn ($record) => self::pendingCount($record) === 0 || ConsultationRadiologyTest::where('patient_id', $record->patient_id)
                        ->where('caseable_type', $record->caseable_type)
                        ->where('caseable_id', $record->caseable_id)
                        ->whereNotNull('radiology_test_id')
                        ->exists())
                    ->modalHeading(__('messages.radiology_tests'))
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalContent(function ($record) {
                        $testIds = ConsultationRadiologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
                            ->where('patient_id', $record->patient_id)
                            ->where('caseable_type', $record->caseable_type)
                            ->where('caseable_id', $record->caseable_id)
                            ->whereNotNull('radiology_test_id')
                            ->pluck('radiology_test_id')
                            ->unique()
                            ->filter()
                            ->values();

                        $test = RadiologyTest::with(['patient.user', 'radiologycategory', 'chargecategory'])
                            ->whereIn('id', $testIds)
                            ->first();

                        return view('filament.hospital-admin.clusters.radiology.resources.doctor-suggested-radiology-tests-resource.pages.view-test-modal', [
                            'test' => $test,
                        ]);
                    }),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription(__('messages.common.no_paid_doctor_suggested_radiology_tests_description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorSuggestedRadiologyTests::route('/'),
        ];
    }

    private static function pendingTests($record)
    {
        return ConsultationRadiologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
            ->where('patient_id', $record->patient_id)
            ->where('caseable_type', $record->caseable_type)
            ->where('caseable_id', $record->caseable_id)
            ->whereNull('radiology_test_id')
            ->get();
    }

    private static function pendingCount($record): int
    {
        return self::pendingTests($record)->count();
    }
}
