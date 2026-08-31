<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources;

use App\Enums\PaymentModeStatus;
use App\Filament\HospitalAdmin\Clusters\Doctors\Resources\DoctorResource;
use App\Filament\HospitalAdmin\Clusters\Medicine;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages\CreateMedicineBills;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages\EditMedicineBills;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages\ListMedicineBills;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages\ViewMedicineBills;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Category;
use App\Models\Medicine as ModelsMedicine;
use App\Models\MedicineBill;
use App\Models\Patient;
use App\Models\PurchasedMedicine;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MedicineBillsResource extends Resource
{
    protected static ?string $model = MedicineBill::class;

    protected static ?string $cluster = Medicine::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('messages.medicine_bills.medicine_bills');
    }

    public static function getLabel(): string
    {
        return __('messages.medicine_bills.medicine_bills');
    }

    public static function canCreate(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function canViewAny(): bool
    {
        if (auth()->user()->hasRole(['Admin', 'Pharmacist', 'Lab Technician'])) {
            return true;
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        $operation = request()->routeIs('filament.hospitalAdmin.resources.medicine-bills.edit') ? 'edit' : 'create';
        return $form

            ->schema([

                Forms\Components\Grid::make(4)
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('patient_id')
                                    ->searchable()
                                    ->label(__('messages.document.patient') . ': ')
                                    ->placeholder(__('messages.document.select_patient'))
                                    ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                                    ->native(false)
                                    ->required()
                                    ->validationMessages([
                                        'required' => __('messages.fields.the') . ' ' . __('messages.document.patient') . ' ' . __('messages.fields.required'),
                                    ]),
                                DateTimePicker::make('bill_date')
                                    ->seconds(false)
                                    ->native(false)
                                    ->default(now())
                                    ->label(__('messages.bill.bill_date') . ':')
                                    ->validationAttribute(__('messages.bill.bill_date'))
                                    ->required(),
                                Toggle::make('payment_status')
                                    ->label(__('messages.medicine_bills.payment_status') . ':')
                                    ->inline(false)
                                    ->default(true)
                                    ->validationAttribute(__('messages.medicine_bills.payment_status'))
                                    ->required(),
                            ])->columns(3),

                    ]),
                Forms\Components\Grid::make('full')
                    ->schema([
                        static::getItemsRepeater(),
                    ])->columnSpan('full'),

                Forms\Components\Grid::make(12)
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label(__('messages.document.notes'))
                            ->placeholder(__('messages.document.notes'))
                            ->rows(3)
                            ->columnSpan(6),
                        Forms\Components\Placeholder::make('')
                            ->columnSpan(1),

                        Forms\Components\Grid::make(5)
                            ->schema([

                                TextInput::make('total')
                                    ->label(__('messages.bill.total_amount'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->validationAttribute(__('messages.bill.total_amount'))
                                    // ->disabled()
                                    ->readOnly()
                                    ->inlineLabel(true)
                                    ->dehydrated()
                                    ->reactive()
                                    ->default(0.00)
                                    ->columnSpan(5)
                                    ->live(),

                                TextInput::make('discount')
                                    ->label(__('messages.purchase_medicine.discount'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if ($state > 100) {
                                            $set('discount', 100);
                                        }
                                        self::updateTotal($get, $set);
                                    })
                                    ->inlineLabel(true)
                                    ->default(0.00)
                                    ->columnSpan(5)
                                    ->reactive(),

                                TextInput::make('tax_amount')
                                    ->label(__('messages.purchase_medicine.tax_amount'))
                                    ->minValue(1)
                                    ->inlineLabel(true)
                                    ->default(0.00)
                                    ->readOnly()
                                    ->columnSpan(5),

                                TextInput::make('net_amount')
                                    ->label(__('messages.purchase_medicine.net_amount'))
                                    ->required()
                                    ->validationAttribute(__('messages.purchase_medicine.net_amount'))
                                    ->numeric()
                                    ->inlineLabel(true)
                                    ->dehydrated()
                                    ->reactive()
                                    ->readOnly()
                                    ->default(0.00)
                                    ->columnSpan(5),

                                Select::make('payment_type')
                                    ->label(__('messages.subscription_plans.payment_type'))
                                    ->placeholder(__('messages.purchase_medicine.payment_mode'))
                                    ->options($operation === 'edit'
                                        ? getPurchaseMedicineManualPaymentTypes()
                                        : getPurchaseMedicinePaymentTypes())
                                    ->inlineLabel(true)
                                    ->required(true)
                                    ->native(false)
                                    ->columnSpan(5)
                                    ->validationMessages([
                                        'required' => __('messages.fields.the') . ' ' . __('messages.subscription_plans.payment_type') . ' ' . __('messages.fields.required'),
                                    ]),


                                Forms\Components\Textarea::make('payment_note')
                                    ->label(__('messages.purchase_medicine.payment_note'))
                                    ->placeholder(__('messages.purchase_medicine.payment_note'))
                                    ->rows(3)
                                    ->columnSpan(6),
                            ])
                            ->columnSpan(5),
                        Forms\Components\Hidden::make('purchase_no')
                            ->default(generateUniquePurchaseNumber()) // Set the default value using your function
                            ->dehydrated(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->modifyQueryUsing(function ($query) {
            return $query->where('tenant_id', getLoggedInUser()->tenant_id);
        });

        return $table
            ->paginated([10, 25, 50])
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('bill_number')
                    ->label(__('messages.medicine_bills.bill_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->prefix('#'),
                TextColumn::make('bill_date')
                    ->label(__('messages.appointment.date'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(
                        fn($state) => Carbon::parse($state)->format('g:i A') . '<br>' . Carbon::parse($state)->format('jS M, Y')
                    )
                    ->html(),
                SpatieMediaLibraryImageColumn::make('patient.patientUser.profile')
                    ->label(__('messages.appointment.patient'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! $record->patient->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->patient->user->full_name);
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(fn($record) => PatientResource::getUrl('view', ['record' => $record->patient->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('patient.patientUser.full_name')
                    ->label('')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn($record) => '<a href="' . PatientResource::getUrl('view', ['record' => $record->patient->id]) . '"class="hoverLink">' . $record->patient->patientUser->full_name . '</a>')
                    ->description(fn($record) => $record->patient->patientUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),

                SpatieMediaLibraryImageColumn::make('doctor.doctorUser.profile')
                    ->label(__('messages.appointment.doctor'))
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        if (! empty($record->doctor->user) && ! $record->doctor->user->hasMedia(User::COLLECTION_PROFILE_PICTURES)) {
                            return getUserImageInitial($record->id, $record->doctor->user->full_name ?? '');
                        }
                    })
                    ->sortable(['first_name'])
                    ->url(function ($record) {
                        if (! empty($record->doctor->id)) {
                            return DoctorResource::getUrl('view', ['record' => $record->doctor->id]);
                        }
                    })
                    // ->url(fn($record) => DoctorResource::getUrl('view', ['record' => $record->doctor->id]))
                    ->collection('profile')
                    ->width(50)->height(50),
                TextColumn::make('doctor.doctorUser.full_name')
                    ->label('')
                    ->html()
                    ->color('primary')
                    ->weight(FontWeight::SemiBold)
                    ->formatStateUsing(fn($record) => '<a href="' . DoctorResource::getUrl('view', ['record' => $record->doctor->id]) . '"class="hoverLink">' . $record->doctor->doctorUser->full_name . '</a>')
                    ->description(fn($record) => $record->doctor->doctorUser->email ?? __('messages.common.n/a'))
                    ->searchable(['users.first_name', 'users.last_name']),
                TextColumn::make('payment_type')
                    ->label(__('messages.purchase_medicine.payment_mode'))
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if ($state === 7) {
                            return 'PayPal';
                        }

                        return PaymentModeStatus::tryFrom($state)?->getLabel() ?? '-'; // Return label or '-' if not found
                    })
                    ->color(function ($state) {
                        return PaymentModeStatus::tryFrom($state)?->getColor() ?? 'secondary'; // Return color or 'secondary' if not found
                    })
                    ->sortable(),
                TextColumn::make('net_amount')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn($state) => getCurrencyFormat($state, 2))
                    ->label(__('messages.purchase_medicine.net_amount')),
                TextColumn::make('payment_status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return $state === 1 ? __('messages.employee_payroll.paid') : __('messages.appointment.pending');
                    })
                    ->color(function ($state) {
                        return $state === 1 ? 'success' : 'danger';
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordUrl(null)
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton()->hidden(function ($record) {
                    return $record->payment_type === MedicineBill::MEDICINE_BILL_CASH || $record->payment_type === MedicineBill::MEDICINE_BILL_CHEQUE ? false : true;
                }),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->action(function (MedicineBill $record) {
                        $record->saleMedicine()->delete();
                        $record->delete();

                        return Notification::make()
                            ->success()
                            ->title(__('messages.medicine_bills.medicine_bill') . ' ' . __('messages.common.deleted_successfully'))
                            ->send();
                    }),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => ListMedicineBills::route('/'),
            'create' => CreateMedicineBills::route('/create'),
            'view' => ViewMedicineBills::route('/{record}'),
            'edit' => EditMedicineBills::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('saleMedicine')
            ->relationship('saleMedicine')
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label(__('messages.medicine_categories'))
                    ->afterStateHydrated(function ($record, $set, $operation) {
                        if ($operation === 'edit') {
                            return $set('category_id', $record->medicine->category->id);
                        }
                    })
                    ->options(Category::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                    ->placeholder(__('messages.medicine.select_category'))
                    ->native(false)
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('medicine_id', null);
                    })
                    ->validationMessages([
                        'required' => __('messages.fields.the') . ' ' . __('messages.medicine_categories') . ' ' . __('messages.fields.required'),
                    ]),
                Forms\Components\Select::make('medicine_id')
                    ->label(__('messages.medicine.medicine'))
                    ->placeholder(__('messages.medicine_bills.select_medicine'))
                    ->options(function (Get $get, $record) {
                        $categoryId = $get('category_id');
                        if ($categoryId) {
                            $category = ModelsMedicine::where('tenant_id', getLoggedInUser()->tenant_id)
                                ->where('category_id', $categoryId)
                                ->get()->pluck('name', 'id')->toArray();

                            return $category;
                        }

                        return [];
                    })
                    ->required()
                    ->live()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $medicine = ModelsMedicine::find($state);
                        $purchaseMedicine = PurchasedMedicine::where('medicine_id', $medicine->id)->latest()->first();
                        $set('sale_price', $medicine->selling_price ?? 0);
                        $set('expiry_date', $purchaseMedicine->expiry_date ?? Carbon::now()->startOfDay()->format('Y-m-d'));

                        $suffix = $medicine->quantity ?? '%';
                        $set('quantity_suffix', $suffix);
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->searchable()
                    ->validationMessages([
                        'required' => __('messages.fields.the') . ' ' . __('messages.medicine.medicine') . ' ' . __('messages.fields.required'),
                    ]),
                DatePicker::make('expiry_date')
                    ->label(__('messages.purchase_medicine.expiry_date'))
                    ->placeholder(__('messages.purchase_medicine.expiry_date'))
                    ->native(false)

                    ->minDate(function () {
                        return Carbon::now()->startOfDay()->format('Y-m-d');
                    })
                    ->validationAttribute(__('messages.purchase_medicine.expiry_date'))
                    ->required(),
                TextInput::make('sale_price')
                    ->label(__('messages.medicine_bills.sale_price'))
                    ->placeholder(__('messages.medicine_bills.sale_price'))
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->validationAttribute(__('messages.medicine_bills.sale_price'))
                    ->reactive()
                    ->minValue(0)
                    ->afterStateUpdated(function ($state, ?Forms\Set $set = null, $get = null) {
                        self::updateTotal($get, $set);
                    })
                    ->live()
                    ->default(0.00),
                TextInput::make('sale_quantity')
                    ->label(__('messages.purchase_medicine.quantity'))
                    ->placeholder(__('messages.purchase_medicine.quantity'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, ?Forms\Set $set = null, $get = null) {
                        self::updateTotal($get, $set);
                    })
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.purchase_medicine.quantity'))
                    ->default('0')
                    ->minValue(0)
                    ->suffix(function (Forms\Get $get) {
                        return $get('quantity_suffix') ?? '0';  // Use the dynamically set suffix, default to '%'
                    }),
                TextInput::make('tax')
                    ->label(__('messages.purchase_medicine.tax'))
                    ->placeholder(__('messages.purchase_medicine.tax'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%')
                    ->reactive()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('amount')
                    ->label(__('messages.purchase_medicine.amount'))
                    ->placeholder(__('messages.purchase_medicine.amount'))
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.purchase_medicine.amount'))
                    ->minValue(0)
                    ->readOnly()
                    ->default(0),
            ])
            ->defaultItems(1)
            ->hiddenLabel()
            ->columns([
                'md' => 10,
            ])
            ->required()
            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                self::updateTotal($get, $set);
            })
            ->dehydrated();
    }

    public static function updateTotal($get, $set): void
    {
        $items = collect($get('saleMedicine'))->values()->toArray();

        $total = 0;
        $totalamt = 0;
        $taxamt = 0;

        foreach ($items as $index => $item) {

            $purchasePrice = (float) $item['sale_price'] ?? 0;
            $quantity = (int) $item['sale_quantity'] ?? 0;
            $tax = (int) $item['tax'] ?? 0;

            $totalamt += $purchasePrice * $quantity;
            $taxamt += ($purchasePrice * $quantity) * ($tax / 100);

            $total += ($purchasePrice * $quantity) * ((100 + $tax) / 100);
        }
        $set('amount', ((int) $get('sale_price')) * ((int) $get('sale_quantity')));
        $set('tax_amount', $taxamt);
        $set('total', $totalamt);
        $totalAmount = $total;
        $discount = (float) $get('discount');

        if ($discount > 0) {
            $netAmount = $totalAmount - $discount;
            $set('net_amount', max($netAmount, 0));
        } else {
            $set('net_amount', $totalAmount);
        }
    }
}
