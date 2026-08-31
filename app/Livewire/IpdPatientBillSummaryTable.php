<?php

namespace App\Livewire;

use App\Filament\hospitalAdmin\Clusters\IpdOpd\Resources\IpdPatientResource;
use App\Repositories\IpdBillRepository;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientBillSummaryTable extends Component implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    public ?array $data = [];

    public $bill;

    public $record;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');

        $ipdPatientDepartmentRepository = App::make(IpdBillRepository::class);
        $this->bill = $ipdPatientDepartmentRepository->getBillList($this->record);

        $this->bill['gross_total'] = $this->bill['total_charges'] + $this->bill['bedCharge'];

        $finalNetPayabelAmount = $this->calculate([
            'discount_in_percentage' => $this->bill['discount_in_percentage'],
            'tax_in_percentage' => $this->bill['tax_in_percentage'],
            'other_charges' => $this->bill['other_charges'],
            'total_charges' => $this->bill['total_charges'],
            'total_payment' => $this->bill['total_payment'],
            'bedCharge' => $this->bill['bedCharge'],
        ]);

        $this->form->fill([
            'bed_charge' => $this->bill['bedCharge'],
            'total_charges' => $this->bill['total_charges'],
            'gross_total' => $this->bill['gross_total'],
            'discount_in_percentage' => $this->bill['discount_in_percentage'],
            'tax_in_percentage' => $this->bill['tax_in_percentage'],
            'other_charges' => $this->bill['other_charges'],
            'total_payment' => $this->bill['total_payment'],
            'net_payable_amount' => $finalNetPayabelAmount,
        ]);
    }

    protected function calculate($data)
    {

        $totalCharges = (int) $this->bill['total_charges'];
        $totalPayments = (int) $this->bill['total_payment'];
        $bedCharge = (int) $this->bill['bedCharge'];

        $discountPercent = (int) $data['discount_in_percentage'];
        $taxPercentage = (int) $data['tax_in_percentage'];
        $otherCharges = (int) $data['other_charges'];

        $totalDiscount = ($discountPercent / 100) * ($totalCharges + $bedCharge);
        $totalTax = ($taxPercentage / 100) * ($totalCharges + $bedCharge);

        $netPayableAmount = ($totalCharges + $otherCharges + $totalTax) - ($totalPayments + $totalDiscount);
        $netPayableAmount = is_nan($netPayableAmount) ? 0 : $netPayableAmount;

        return $netPayableAmount + $bedCharge;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Placeholder::make('bed_charge')
                    ->label(__('messages.lunch_break.bed_charge').': ')
                    ->inlineLabel()
                    ->content(fn ($state) => getCurrencyFormat($state))
                    ->extraAttributes(['class' => 'ms-auto'])
                    ->default(__('messages.common.n/a')),
                Placeholder::make('total_charges')
                    ->label(__('messages.ipd_bill.total_charges').': ')
                    ->content(fn ($state) => getCurrencyFormat($state))
                    ->extraAttributes(['class' => 'ms-auto'])
                    ->inlineLabel(),
                Group::make([
                    Placeholder::make('gross_total')
                        ->label(__('messages.ipd_bill.gross_total').': ')
                        ->content(fn ($state) => getCurrencyFormat($state))
                        ->extraAttributes(['class' => 'ms-auto'])
                        ->inlineLabel(),
                ])
                    ->extraAttributes(['class' => 'pb-4 border-b border-gray']),

                TextInput::make('discount_in_percentage')
                    ->readOnly(getLoggedinPatient())
                    ->inlineLabel()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->live()
                    ->debounce(500)
                    ->disabled(function () {
                        return $this->bill['ipd_patient_department']->bill_status == 1 ? true : false;
                    })
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        if ($state < 0 || $state > 100 || empty($state)) {
                            $set('discount_in_percentage', 0);

                            $set('net_payable_amount', $this->calculate([
                                'discount_in_percentage' => $get('discount_in_percentage'),
                                'tax_in_percentage' => $get('tax_in_percentage'),
                                'other_charges' => $get('other_charges'),
                            ]));
                        } else {
                            $set('net_payable_amount', $this->calculate([
                                'discount_in_percentage' => $get('discount_in_percentage'),
                                'tax_in_percentage' => $get('tax_in_percentage'),
                                'other_charges' => $get('other_charges'),
                            ]));
                        }
                    })
                    ->label(__('messages.ipd_bill.discount_in_percentage').' (%) :'),

                TextInput::make('tax_in_percentage')
                    ->readOnly(getLoggedinPatient())
                    ->inlineLabel()
                    ->numeric()
                    ->minValue(1)
                    ->live()
                    ->debounce(500)
                    ->maxValue(100)
                    ->disabled(function () {
                        return $this->bill['ipd_patient_department']->bill_status == 1 ? true : false;
                    })
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        if ($state < 0 || $state > 100 || empty($state)) {
                            $set('tax_in_percentage', 0);
                            $set('net_payable_amount', $this->calculate([
                                'discount_in_percentage' => $get('discount_in_percentage'),
                                'tax_in_percentage' => 0,
                                'other_charges' => $get('other_charges'),
                            ]));
                        }

                        if (! empty($state) && $state > 0 && $state < 100) {
                            $set('net_payable_amount', $this->calculate([
                                'discount_in_percentage' => $get('discount_in_percentage'),
                                'tax_in_percentage' => $state,
                                'other_charges' => $get('other_charges'),
                            ]));
                        }
                    })
                    ->label(__('messages.ipd_bill.tax_in_percentage').' (%) :'),

                TextInput::make('other_charges')
                    ->readOnly(getLoggedinPatient())
                    ->inlineLabel()
                    ->live()
                    ->numeric()
                    ->minValue(0)
                    ->debounce(500)
                    ->disabled(function () {
                        return $this->bill['ipd_patient_department']->bill_status == 1 ? true : false;
                    })
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        if ($state < 0 || empty($state)) {
                            $set('other_charges', 0);

                            $set('net_payable_amount', $this->calculate([
                                'discount_in_percentage' => $get('discount_in_percentage'),
                                'tax_in_percentage' => $get('tax_in_percentage'),
                                'other_charges' => $state,
                            ]));
                        } else {
                            $set('net_payable_amount', $this->calculate([
                                'discount_in_percentage' => $get('discount_in_percentage'),
                                'tax_in_percentage' => $get('tax_in_percentage'),
                                'other_charges' => $state,
                            ]));
                        }
                    })
                    ->label(__('messages.ipd_bill.other_charges').' :')
                    ->extraFieldWrapperAttributes(['class' => 'pb-4 border-b border-gray']),

                Group::make([
                    Placeholder::make('total_payment')
                        ->label(__('messages.ipd_bill.paid_amount').' :')
                        ->inlineLabel()
                        ->extraAttributes(['class' => 'ms-auto'])
                        ->content(fn ($state) => getCurrencyFormat($state)),
                ])
                    ->extraAttributes(['class' => 'pb-4 border-b border-gray']),

                Group::make([
                    Placeholder::make('net_payable_amount')
                        ->label(__('messages.ipd_bill.net_payable_amount').' :')
                        ->inlineLabel()
                        ->extraAttributes(['class' => 'ms-auto'])
                        ->content(fn ($state) => getCurrencyFormat($state)),
                ])
                    ->extraAttributes(['class' => 'pb-4 border-b border-gray']),
            ])
            ->statePath('data');
    }

    public function render()
    {
        return view('livewire.ipd-patient-bill-summary-table');
    }

    public function submitBill()
    {
        $data = $this->data;
        $data['ipd_patient_department_id'] = $this->id;
        $data['total_payments'] = $this->bill['total_payment'];

        $ipdBillRepository = App::make(IpdBillRepository::class);
        $ipdBillRepository->saveBill($data);

        Notification::make()
            ->success()
            ->title(__('messages.flash.IPD_bill_saved'))
            ->send();

        return redirect()->to(IpdPatientResource::getUrl('view', ['record' => $this->id]));
    }
}
