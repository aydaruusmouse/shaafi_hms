<?php

namespace App\Filament\HospitalAdmin\Clusters\Pathology\Resources;

use App\Filament\HospitalAdmin\Clusters\Pathology;
use App\Filament\HospitalAdmin\Clusters\Pathology\Resources\LabReportPaymentResource\Pages;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Charge;
use App\Models\ChargeCategory;
use App\Models\ConsultationPathologyTest;
use App\Models\PathologyTest;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LabReportPaymentResource extends Resource
{
    protected static ?string $model = ConsultationPathologyTest::class;

    protected static ?string $cluster = Pathology::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'lab-report-payments';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) getModuleAccess('Lab Report Payments');
    }

    public static function getNavigationLabel(): string
    {
        return __('messages.lab_report_payments');
    }

    public static function getLabel(): string
    {
        return __('messages.lab_report_payments');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return hasModulePermission('Lab Report Payments', 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        return hasModulePermission('Lab Report Payments', 'delete');
    }

    public static function canViewAny(): bool
    {
        return hasModulePermission('Lab Report Payments');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        if (! getModuleAccess('Lab Report Payments')) {
            abort(404);
        }

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return ConsultationPathologyTest::groupedIndexQuery($query)
                    ->with(['patient.user', 'pathologyCategory', 'caseable'])
                    ->orderBy('payment_status')
                    ->orderByDesc('created_at');
            })
            ->paginated([10, 25, 50])
            ->columns([
                SpatieMediaLibraryImageColumn::make('patient.user.profile')
                    ->label(__('messages.case.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! empty($record->patient?->user) && ! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.user.full_name')
                    ->label('')
                    ->description(fn ($record) => $record->patient->user->email ?? __('messages.common.n/a'))
                    ->html()
                    ->formatStateUsing(fn ($record) => $record->patient?->id
                        ? '<a href="'.PatientResource::getUrl('view', ['record' => $record->patient->id]).'" class="hoverLink">'.($record->patient->user->full_name ?? __('messages.common.n/a')).'</a>'
                        : __('messages.common.n/a'))
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('doctor_name')
                    ->label(__('messages.case.doctor'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('pathologyCategory.name')
                    ->label(__('messages.pathology_test.category_name'))
                    ->formatStateUsing(function ($record) {
                        return $record->groupQuery()
                            ->with('pathologyCategory')
                            ->get()
                            ->pluck('pathologyCategory.name')
                            ->filter()
                            ->unique()
                            ->implode(', ') ?: __('messages.common.n/a');
                    }),
                TextColumn::make('section')
                    ->label(__('messages.section'))
                    ->badge()
                    ->color(fn ($record) => match ($record->section) {
                        'IPD' => 'success',
                        'OPD' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('paid_amount')
                    ->label(__('messages.purchase_medicine.amount'))
                    ->getStateUsing(fn ($record) => $record->isPaid() ? $record->paid_amount : $record->estimatedCharge())
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state)),
                TextColumn::make('payment_mode')
                    ->label(__('messages.ipd_payments.payment_mode'))
                    ->formatStateUsing(fn ($state) => getIpdPaymentTypes()[$state] ?? __('messages.common.n/a')),
                TextColumn::make('payment_status')
                    ->label(__('messages.common.status'))
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->isPaid() ? __('messages.paid') : __('messages.new_change.pending_payment'))
                    ->color(fn ($record) => $record->isPaid() ? 'success' : 'warning'),
                TextColumn::make('paid_at')
                    ->label(__('messages.common.created_on'))
                    ->dateTime('d M, Y h:i A')
                    ->placeholder(__('messages.common.n/a')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label(__('messages.common.status'))
                    ->options([
                        ConsultationPathologyTest::PAYMENT_UNPAID => __('messages.new_change.pending_payment'),
                        ConsultationPathologyTest::PAYMENT_PAID => __('messages.paid'),
                    ])
                    ->native(false),
            ])
            ->actions([
                Action::make('pay')
                    ->label(__('messages.pay'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->isPaid() && hasModulePermission('Lab Report Payments', 'create'))
                    ->modalHeading(__('messages.common.confirm').' Payment')
                    ->modalWidth('4xl')
                    ->fillForm(function ($record) {
                        return [
                            'payment_mode' => 1,
                            'payment_date' => now(),
                            'tests' => $record->testLineItems(),
                        ];
                    })
                    ->form([
                        Section::make(__('messages.payment.payment_details'))
                            ->schema([
                                Select::make('payment_mode')
                                    ->label(__('messages.subscription_plans.payment_method'))
                                    ->options(getIpdPaymentTypes())
                                    ->default(1)
                                    ->required()
                                    ->searchable()
                                    ->native(false),
                                DatePicker::make('payment_date')
                                    ->label(__('messages.ipd_patient_timeline.date'))
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->default(now())
                                    ->required(),
                                TextInput::make('payment_reference')
                                    ->label(__('messages.postal.reference_no'))
                                    ->placeholder('Optional reference'),
                                Textarea::make('payment_note')
                                    ->label(__('messages.ipd_patient.notes'))
                                    ->placeholder('Any additional notes...')
                                    ->rows(3),
                            ])->columns(2),
                        Section::make('Test Details')
                            ->schema([
                                Repeater::make('tests')
                                    ->label('Tests')
                                    ->schema([
                                        TextInput::make('test_name')
                                            ->label(__('messages.prescription.test'))
                                            ->disabled()
                                            ->dehydrated(),
                                        Select::make('charge_category_id')
                                            ->label(__('messages.pathology_test.charge_category'))
                                            ->options(fn () => ChargeCategory::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($set, $state) {
                                                $amount = Charge::where('charge_category_id', $state)
                                                    ->where('tenant_id', getLoggedInUser()->tenant_id)
                                                    ->value('standard_charge') ?? 0;
                                                $set('amount', $amount);
                                            })
                                            ->native(false),
                                        TextInput::make('amount')
                                            ->label(__('messages.ambulance_call.amount').' ('.strtoupper((string) getCurrentCurrency()).')')
                                            ->prefix(getCurrencySymbol())
                                            ->numeric()
                                            ->required()
                                            ->readOnly()
                                            ->helperText('Auto-calculated'),
                                    ])
                                    ->columns(3)
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false),
                            ]),
                        Section::make(__('messages.invoice.total'))
                            ->schema([
                                Placeholder::make('total_amount')
                                    ->label(__('messages.invoice.total'))
                                    ->content(function ($get) {
                                        $total = collect($get('tests') ?? [])->sum(fn ($row) => (float) ($row['amount'] ?? 0));

                                        return getCurrencyFormat($total);
                                    }),
                            ]),
                    ])
                    ->action(function ($record, array $data) {
                        $total = collect($data['tests'] ?? [])->sum(fn ($row) => (float) ($row['amount'] ?? 0));

                        $record->markGroupPaid([
                            'payment_mode' => $data['payment_mode'] ?? null,
                            'paid_amount' => $total,
                            'paid_at' => $data['payment_date'] ?? now(),
                            'payment_note' => $data['payment_note'] ?? null,
                            'payment_reference' => $data['payment_reference'] ?? null,
                        ]);

                        Notification::make()
                            ->title(__('messages.flash.payment_saved'))
                            ->success()
                            ->send();
                    }),
                Action::make('view_test')
                    ->label(__('messages.common.view'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modalHeading(__('messages.pathology_test.pathology_test_details'))
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('messages.common.close'))
                    ->modalContent(function ($record) {
                        $testIds = $record->groupQuery()
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
                            'record' => $record,
                            'suggestions' => $record->groupQuery()->with(['pathologyCategory', 'patient.user'])->get(),
                        ]);
                    }),
            ])
            ->recordUrl(null)
            ->actionsColumnLabel(__('messages.common.action'))
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabReportPayments::route('/'),
        ];
    }
}
