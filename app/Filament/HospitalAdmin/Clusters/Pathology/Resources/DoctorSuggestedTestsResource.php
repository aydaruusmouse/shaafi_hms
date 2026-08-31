<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources;

use App\Filament\HospitalAdmin\Clusters\Pathology;
use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\DoctorSuggestedTestsResource\Pages;
use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\ConsultationPathologyTest;
use App\Models\PathologyCategory;
use App\Models\PathologyParameter;
use App\Models\PathologyTest;
use App\Models\PathologyUnit;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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

class DoctorSuggestedTestsResource extends Resource
{
    protected static ?string $model = ConsultationPathologyTest::class;

    protected static ?string $cluster = Pathology::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 6;

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) getModuleAccess('Doctor Suggested Tests');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.doctor_suggested_tests');
    }

    public static function getLabel(): string
    {
        return __('messages.doctor_suggested_tests');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return hasModulePermission('Doctor Suggested Tests', 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        return hasModulePermission('Doctor Suggested Tests', 'delete');
    }

    public static function canViewAny(): bool
    {
        return hasModulePermission('Doctor Suggested Tests');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        if (! getModuleAccess('Doctor Suggested Tests')) {
            abort(404);
        }

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('tenant_id', getLoggedInUser()->tenant_id)
                    ->where('payment_status', ConsultationPathologyTest::PAYMENT_PAID)
                    ->whereIn('id', function ($subQuery) {
                        $subQuery->selectRaw('MIN(id)')
                            ->from('consultation_pathology_tests')
                            ->where('tenant_id', getLoggedInUser()->tenant_id)
                            ->where('payment_status', ConsultationPathologyTest::PAYMENT_PAID)
                            ->groupBy('patient_id', 'caseable_type', 'caseable_id');
                    })
                    ->with(['patient.user', 'pathologyCategory', 'pathologyTest', 'caseable'])
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
                TextColumn::make('pathologyCategory.name')
                    ->label(__('messages.pathology_test.category_name'))
                    ->formatStateUsing(function ($record) {
                        return ConsultationPathologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
                            ->where('patient_id', $record->patient_id)
                            ->where('caseable_type', $record->caseable_type)
                            ->where('caseable_id', $record->caseable_id)
                            ->with('pathologyCategory')
                            ->get()
                            ->pluck('pathologyCategory.name')
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
                            $subQuery->select('cpt1.id')
                                ->from('consultation_pathology_tests as cpt1')
                                ->whereExists(function ($existsQuery) {
                                    $existsQuery->select(DB::raw(1))
                                        ->from('consultation_pathology_tests as cpt2')
                                        ->whereColumn('cpt2.patient_id', 'cpt1.patient_id')
                                        ->whereColumn('cpt2.caseable_type', 'cpt1.caseable_type')
                                        ->whereColumn('cpt2.caseable_id', 'cpt1.caseable_id')
                                        ->whereNull('cpt2.pathology_test_id');
                                });
                        });
                    }),
            ])
            ->actions([
                Action::make('proceed')
                    ->label(__('messages.proceed'))
                    ->icon('heroicon-o-arrow-right')
                    ->color('primary')
                    ->modalHeading(__('messages.create_pathology_test_from_suggestion'))
                    ->modalSubmitActionLabel(__('messages.common.save'))
                    ->modalWidth('6xl')
                    ->visible(fn ($record) => $record->isPaid() && self::pendingCount($record) > 0 && hasModulePermission('Doctor Suggested Tests', 'create'))
                    ->fillForm(function ($record) {
                        $pending = self::pendingTests($record);
                        $first = $pending->first();
                        $chargeCategoryId = null;
                        $standardCharge = 0;
                        $charge = Charge::where('tenant_id', getLoggedInUser()->tenant_id)->first();
                        $chargeCategoryId = $charge?->charge_category_id;
                        $standardCharge = $charge?->standard_charge ?? 0;

                        $parameters = $pending
                            ->whereNotNull('pathology_parameter_id')
                            ->unique('pathology_parameter_id')
                            ->map(function ($test) {
                                $parameter = PathologyParameter::find($test->pathology_parameter_id);

                                return [
                                    'parameter_id' => $test->pathology_parameter_id,
                                    'patient_result' => '',
                                    'reference_range' => $parameter?->reference_range ?? '',
                                    'unit_id' => $parameter?->pathologyUnit?->name ?? '',
                                ];
                            })
                            ->values()
                            ->toArray();

                        $testName = trim((string) ($first?->getAttributes()['test_name'] ?? ''));
                        if ($testName === '' || strcasecmp($testName, 'N/A') === 0) {
                            $testName = ($first?->pathologyCategory?->name ?? 'Pathology').' Test';
                        }

                        return [
                            'patient_id' => $record->patient_id,
                            'test_name' => $testName,
                            'short_name' => strtoupper(substr($first?->pathologyCategory?->name ?? 'PT', 0, 8)),
                            'test_type' => 'Lab Test',
                            'category_id' => $first?->pathology_category_id,
                            'charge_category_id' => $chargeCategoryId,
                            'standard_charge' => $standardCharge,
                            'consultation_tests' => $pending->pluck('id')->toArray(),
                            'parameter' => $parameters,
                        ];
                    })
                    ->form([
                        Hidden::make('consultation_tests')->dehydrated(),
                        Section::make(__('messages.pathology_test.test_name'))
                            ->schema([
                                Select::make('patient_id')
                                    ->label(__('messages.case.patient').':')
                                    ->options(fn ($record) => [$record->patient_id => $record->patient->user->full_name ?? 'N/A'])
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                TextInput::make('test_name')
                                    ->label(__('messages.pathology_test.test_name').':')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('short_name')
                                    ->label(__('messages.pathology_test.short_name').':')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('test_type')
                                    ->label(__('messages.pathology_test.test_type').':')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('category_id')
                                    ->label(__('messages.pathology_test.category_name').':')
                                    ->options(fn () => PathologyCategory::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->native(false),
                                Select::make('charge_category_id')
                                    ->live()
                                    ->label(__('messages.pathology_test.charge_category').':')
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
                                    ->label(__('messages.pathology_test.standard_charge').':')
                                    ->numeric()
                                    ->required()
                                    ->readOnly(),
                            ])->columns(4),
                        Repeater::make('parameter')
                            ->label(__('messages.new_change.parameter_name'))
                            ->schema([
                                Select::make('parameter_id')
                                    ->label(__('messages.new_change.parameter_name'))
                                    ->options(fn () => PathologyParameter::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('parameter_name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($set, $get) {
                                        $parameter = PathologyParameter::find($get('parameter_id'));
                                        $set('reference_range', $parameter?->reference_range ?? '');
                                        $set('unit_id', $parameter?->pathologyUnit?->name ?? PathologyUnit::where('id', $parameter?->unit_id)->value('name'));
                                    })
                                    ->required()
                                    ->native(false),
                                TextInput::make('patient_result')
                                    ->label(__('messages.new_change.patient_result'))
                                    ->required(),
                                TextInput::make('reference_range')
                                    ->label(__('messages.new_change.reference_range'))
                                    ->readOnly()
                                    ->dehydrated(false),
                                TextInput::make('unit_id')
                                    ->label(__('messages.pathology_test.unit'))
                                    ->readOnly()
                                    ->dehydrated(false),
                            ])
                            ->addActionLabel(__('messages.common.add'))
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data) {
                        $consultationTestIds = is_array($data['consultation_tests'] ?? null)
                            ? $data['consultation_tests']
                            : [];

                        $pathologyTest = PathologyTest::create([
                            'patient_id' => $data['patient_id'],
                            'doctor_id' => $record->doctor_id,
                            'test_name' => $data['test_name'],
                            'short_name' => $data['short_name'],
                            'test_type' => $data['test_type'],
                            'category_id' => $data['category_id'],
                            'charge_category_id' => $data['charge_category_id'],
                            'standard_charge' => $data['standard_charge'] ?? 0,
                            'status' => 0,
                            'payment_status' => $record->isPaid() ? PathologyTest::PAYMENT_PAID : PathologyTest::PAYMENT_UNPAID,
                            'payment_mode' => $record->payment_mode,
                            'paid_amount' => $record->paid_amount,
                            'paid_at' => $record->paid_at,
                            'payment_note' => $record->payment_note,
                            'tenant_id' => getLoggedInUser()->tenant_id,
                            'report_days' => 1,
                        ]);

                        foreach ($data['parameter'] ?? [] as $parameter) {
                            if (! empty($parameter['parameter_id'])) {
                                $pathologyTest->parameterItems()->create([
                                    'parameter_id' => $parameter['parameter_id'],
                                    'patient_result' => $parameter['patient_result'] ?? '',
                                ]);
                            }
                        }

                        if (! empty($consultationTestIds)) {
                            ConsultationPathologyTest::whereIn('id', $consultationTestIds)->update([
                                'pathology_test_id' => $pathologyTest->id,
                                'processed_at' => now(),
                            ]);
                        }

                        Notification::make()
                            ->title(__('messages.flash.pathology_test_saved'))
                            ->success()
                            ->send();
                    }),
                Action::make('view_test')
                    ->label(__('messages.common.view'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn ($record) => self::pendingCount($record) === 0 || ConsultationPathologyTest::where('patient_id', $record->patient_id)
                        ->where('caseable_type', $record->caseable_type)
                        ->where('caseable_id', $record->caseable_id)
                        ->whereNotNull('pathology_test_id')
                        ->exists())
                    ->modalHeading(__('messages.pathology_tests'))
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalContent(function ($record) {
                        $testIds = ConsultationPathologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
                            ->where('patient_id', $record->patient_id)
                            ->where('caseable_type', $record->caseable_type)
                            ->where('caseable_id', $record->caseable_id)
                            ->whereNotNull('pathology_test_id')
                            ->pluck('pathology_test_id')
                            ->unique()
                            ->filter()
                            ->values();

                        $test = PathologyTest::with(['parameterItems.pathologyParameter', 'patient.user', 'pathologycategory', 'chargecategory'])
                            ->whereIn('id', $testIds)
                            ->first();

                        return view('filament.hospital-admin.clusters.pathology.resources.doctor-suggested-tests-resource.pages.view-test-modal', [
                            'test' => $test,
                        ]);
                    }),
                Action::make('pdf')
                    ->iconButton()
                    ->icon('heroicon-s-printer')
                    ->color('warning')
                    ->url(function ($record) {
                        $testId = self::linkedPathologyTestId($record);

                        return $testId ? route('pathology.test.pdf', $testId) : null;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => (bool) self::linkedPathologyTestId($record)),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'))
            ->emptyStateDescription(__('messages.common.no_paid_doctor_suggested_tests_description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDoctorSuggestedTests::route('/'),
        ];
    }

    private static function pendingTests($record)
    {
        return ConsultationPathologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
            ->where('patient_id', $record->patient_id)
            ->where('caseable_type', $record->caseable_type)
            ->where('caseable_id', $record->caseable_id)
            ->whereNull('pathology_test_id')
            ->get();
    }

    private static function pendingCount($record): int
    {
        return self::pendingTests($record)->count();
    }

    private static function linkedPathologyTestId($record): ?int
    {
        return ConsultationPathologyTest::where('tenant_id', getLoggedInUser()->tenant_id)
            ->where('patient_id', $record->patient_id)
            ->where('caseable_type', $record->caseable_type)
            ->where('caseable_id', $record->caseable_id)
            ->whereNotNull('pathology_test_id')
            ->value('pathology_test_id');
    }
}
