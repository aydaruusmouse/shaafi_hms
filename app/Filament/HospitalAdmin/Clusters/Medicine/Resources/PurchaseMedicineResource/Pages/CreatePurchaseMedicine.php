<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource;
use App\Http\Controllers\PurchaseMedicineController;
use App\Models\Medicine as MedicineModel;
use App\Models\PurchaseMedicine;
use Carbon\Carbon;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePurchaseMedicine extends CreateRecord
{
    protected static string $resource = PurchaseMedicineResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order items')
                    ->headerActions([
                        FormAction::make('reset')
                            ->modalHeading(__('messages.lunch_break.are_u_sure'))
                            ->requiresConfirmation()
                            ->color('danger')
                            // ->action(fn(Forms\Set $set) => $set('items', [])),
                            ->action(fn () => $this->redirect(PurchaseMedicineResource::getUrl('create'), navigate: true)),
                    ])
                    ->schema([
                        static::getItemsRepeater(),
                    ]),
                Grid::make(12)
                    ->schema([
                        Textarea::make('note')
                            ->label(__('messages.document.notes'))
                            ->placeholder(__('messages.document.notes'))
                            ->rows(3)
                            ->columnSpan(6),
                        Placeholder::make('')
                            ->columnSpan(1),

                        Grid::make(5)
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
                                    ->afterStateUpdated(function ($state, Set $set, $get) {
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

                                Textarea::make('payment_note')
                                    ->label(__('messages.purchase_medicine.payment_note'))
                                    ->placeholder(__('messages.purchase_medicine.payment_note'))
                                    ->rows(3)
                                    ->columnSpan(6),
                            ])
                            ->columnSpan(5),
                        Hidden::make('purchase_no')
                            ->default(generateUniquePurchaseNumber())
                            ->dehydrated(),
                    ]),
            ]);
    }

    public function getItemsRepeater(): Repeater
    {
        return Repeater::make('purchasedMedcines')
            ->relationship('purchasedMedcines')
            ->schema([
                Select::make('medicine')
                    ->label(__('messages.medicine.medicine'))
                    ->placeholder(__('messages.medicine_bills.select_medicine'))
                    ->options(MedicineModel::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, Set $set) {
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
                    ->afterStateUpdated(function ($state, ?Set $set = null, $get = null) {
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
            ->afterStateUpdated(function ($state, Set $set, $get) {
                self::updateTotal($get, $set);
            })
            ->dehydrated();
    }

    public function updateTotal($get, $set): void
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

    protected function handleRecordCreation(array $input): Model
    {
        try {
            $input['total'] = removeCommaFromNumbers(number_format($input['total'], 2));
            $input['discount'] = removeCommaFromNumbers(number_format($input['discount'], 2));
            $input['tax'] = removeCommaFromNumbers(number_format($input['tax'], 2));
            $input['net_amount'] = removeCommaFromNumbers(number_format($input['net_amount'], 2));

            $purchaseMedicineController = app(PurchaseMedicineController::class);
            $data = $purchaseMedicineController->store($input);

            if (is_array($data) && array_key_exists('error', $data)) {
                Notification::make()
                    ->title($data['error'])
                    ->danger()
                    ->send();
                $this->halt();
            }
            if (is_array($data) && array_key_exists('payment_mode', $data)) {
                $this->js('razorPay(event'.','.$data['status'].', '.$data['record'].', '.$data['amount'].')');
                $this->halt();
            }

            $medicinePurchase = [
                'purchase_no' => '1234',
            ];

            $purchaseMedicine = new PurchaseMedicine($medicinePurchase);

            return $purchaseMedicine;
        } catch (Exception $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        if (session()->has('sessionUrl')) {
            $sessionUrl = session()->get('sessionUrl');
            session()->forget('sessionUrl');

            return $sessionUrl;
        } else {
            return static::getResource()::getUrl('index');
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        if (session()->has('paymentError')) {
            session()->forget('paymentError');

            return '';
        } elseif (! session()->has('sessionUrl')) {
            return __('messages.new_change.medicine_purchase_success');
        }

        return '';
    }
}
