<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources;

use App\Enums\PaymentModeStatus;
use App\Filament\HospitalAdmin\Clusters\Medicine;
use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Pages;
use App\Models\Medicine as MedicineModel;
use App\Models\PurchaseMedicine;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PurchaseMedicineResource extends Resource
{
    protected static ?string $model = PurchaseMedicine::class;

    protected static ?string $cluster = Medicine::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('messages.purchase_medicine.purchase_medicine');
    }

    public static function getLabel(): string
    {
        return __('messages.purchase_medicine.purchase_medicine');
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
        return $form
            ->schema([
                Forms\Components\Section::make('Order items')
                    ->headerActions([
                        Action::make('reset')
                            ->modalHeading(__('messages.lunch_break.are_u_sure'))
                            ->requiresConfirmation()
                            ->color('danger')
                            ->action(fn (Forms\Set $set) => $set('items', [])),
                    ])
                    ->schema([
                        static::getItemsRepeater(),
                    ]),
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
                                    ->label(__('messages.purchase_medicine.total'))
                                    ->numeric()
                                    ->required()
                                    // ->disabled()
                                    ->inlineLabel(true)
                                    ->dehydrated() // Ensures value is included when form data is submitted
                                    ->reactive()
                                    ->default(0.00)
                                    ->readOnly()
                                    ->columnSpan(5),

                                TextInput::make('discount')
                                    ->label(__('messages.purchase_medicine.discount'))
                                    ->numeric()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        $totalAmount = $get('net_amount');
                                        $discount = (float) $state;
                                        if ($state > 100) {
                                            $set('discount', 100);
                                        }
                                        if ($discount > 0) {
                                            $netAmount = $totalAmount - $discount;
                                            $set('net_amount', max($netAmount, 0));
                                        } else {
                                            $set('net_amount', $totalAmount);
                                        }
                                    })
                                    ->inlineLabel(true)
                                    ->default(0.00)
                                    ->columnSpan(5)
                                    ->reactive(),

                                TextInput::make('tax')
                                    ->label(__('messages.purchase_medicine.tax_amount'))
                                    ->numeric()
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
                                    ->options(getPurchaseMedicinePaymentTypes())
                                    ->inlineLabel(true)
                                    ->required(true)
                                    ->native(false)
                                    ->columnSpan(5)
                                    ->validationMessages([
                                        'required' => __('messages.fields.the').' '.__('messages.subscription_plans.payment_type').' '.__('messages.fields.required'),
                                    ]),

                                Forms\Components\Textarea::make('payment_note')
                                    ->label(__('messages.purchase_medicine.payment_note'))
                                    ->placeholder(__('messages.purchase_medicine.payment_note'))
                                    ->rows(3)
                                    ->columnSpan(6),
                            ])
                            ->columnSpan(5),
                        Forms\Components\Hidden::make('purchase_no')
                            ->default(generateUniquePurchaseNumber())
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
                Tables\Columns\TextColumn::make('purchase_no')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->prefix('#'),
                Tables\Columns\TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tax')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return $state === 1 ? __('messages.employee_payroll.paid') : __('messages.appointment.pending');
                    })
                    ->color(function ($state) {
                        return $state === 1 ? 'success' : 'danger';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state, 2))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_type')
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
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('info')->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton()->successNotificationTitle(__('messages.purchase_medicine.purchase_medicine').' '.__('messages.common.has_been_deleted')),

            ])
            ->actionsColumnLabel(__('messages.common.action'))
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
            'index' => Pages\ListPurchaseMedicines::route('/'),
            'create' => Pages\CreatePurchaseMedicine::route('/create'),
            'view' => Pages\ViewPurchaseMedicine::route('/{record}'),
            'edit' => Pages\EditPurchaseMedicine::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('purchasedMedcines')
            ->relationship('purchasedMedcines')
            ->schema([
                Forms\Components\Select::make('medicine')
                    ->label(__('messages.medicine.medicine'))
                    ->placeholder(__('messages.medicine_bills.select_medicine'))
                    ->options(MedicineModel::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        $medicine = MedicineModel::find($state);
                        $set('sale_price', $medicine->selling_price ?? 0);
                        $set('purchase_price', $medicine->buying_price ?? 0);
                    })
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->searchable()
                    ->validationMessages([
                        'required' => __('messages.fields.the').' '.__('messages.medicine.medicine').' '.__('messages.fields.required'),
                    ]),
                TextInput::make('lot_no')
                    ->label(__('messages.purchase_medicine.lot_no'))
                    ->placeholder(__('messages.purchase_medicine.lot_no'))
                    ->reactive()
                    ->afterStateUpdated(function ($set, $state) {
                        $state < 0 ? $set('lot_no', 0) : $set('lot_no', $state);
                    })
                    ->numeric()
                    ->validationAttribute(__('messages.purchase_medicine.lot_no'))
                    ->required(),
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
                    ->required()
                    ->validationAttribute(__('messages.medicine_bills.sale_price'))
                    ->default(0.00),
                TextInput::make('purchase_price')
                    ->label(__('messages.item_stock.purchase_price'))
                    ->placeholder(__('messages.item_stock.purchase_price'))
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.item_stock.purchase_price'))
                    ->reactive()
                    ->default(0.00),
                TextInput::make('quantity')
                    ->label(__('messages.purchase_medicine.quantity'))
                    ->placeholder(__('messages.purchase_medicine.quantity'))
                    ->reactive()
                    ->afterStateUpdated(function ($state, ?Forms\Set $set = null, $get = null) {
                        // $set('quantity', round($state));
                        self::updateTotal($get, $set);
                    })
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.purchase_medicine.quantity'))
                    ->default(0), // Set the default value as a string
                // ->afterStateUpdated(function ($state, Forms\Set $set) {
                //     // Optionally, you can ensure that the value is formatted correctly after update
                //     $set('taxamt', number_format((float)$state, 2, '.', ''));
                // }),
                TextInput::make('tax')
                    ->label(__('messages.purchase_medicine.tax'))
                    ->placeholder(__('messages.purchase_medicine.tax'))
                    ->numeric()
                    ->suffix('%')
                    ->validationAttribute(__('messages.purchase_medicine.tax'))
                    ->reactive()
                    ->minValue(0)
                    ->maxValue(100)
                    ->default(0),
                TextInput::make('amount')
                    ->label(__('messages.purchase_medicine.amount'))
                    ->placeholder(__('messages.purchase_medicine.amount'))
                    ->numeric()
                    ->required()
                    ->validationAttribute(__('messages.purchase_medicine.amount'))
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
        $items = collect($get('purchasedMedcines'))->values()->toArray();

        $total = 0;
        $totalamt = 0;
        $taxamt = 0;

        foreach ($items as $index => $item) {
            $purchasePrice = (float) $item['purchase_price'] ?? 0;
            $quantity = (int) $item['quantity'] ?? 0;
            $tax = (int) $item['tax'] ?? 0;

            $totalamt += $purchasePrice * $quantity;

            $taxamt += ($purchasePrice * $quantity) * ($tax / 100);

            $total += ($purchasePrice * $quantity) * ((100 + $tax) / 100);
        }

        $set('amount', ((float) $get('purchase_price')) * ((int) $get('quantity')));

        $set('tax', $taxamt);
        $set('total', $totalamt);
        $set('net_amount', $total);
    }
}
