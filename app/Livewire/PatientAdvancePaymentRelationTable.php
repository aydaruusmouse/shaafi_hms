<?php

namespace App\Livewire;

use App\Models\AdvancedPayment;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PatientAdvancePaymentRelationTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AdvancedPayment::where('patient_id', $this->id)->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc'))
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('receipt_no')
                    ->label(__('messages.advanced_payment.receipt_no'))
                    ->default(__('messages.common.n/a'))
                    ->badge()
                    ->color('info')
                    ->sortable()->searchable(),
                TextColumn::make('date')
                    ->label(__('messages.advanced_payment.date'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->extraAttributes(['class' => 'text-center'])
                    ->sortable()
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y'))
                    ->html(),
                TextColumn::make('amount')
                    ->label(__('messages.advanced_payment.amount'))
                    ->default(__('messages.common.n/a'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => getCurrencyFormat($state))
                    ->html(),
            ])
            ->actionsColumnLabel(function () {
                if (auth()->user()->hasRole('Patient')) {
                    return null;
                }

                return __('messages.common.action');
            })
            ->actions([
                Actions\EditAction::make()
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->modalWidth('md')
                    ->form([
                        Select::make('patient_id')
                            ->label(__('messages.advanced_payment.patient'))
                            ->options(Patient::with('user')->where('tenant_id', getLoggedInUser()->tenant_id)->orderBy('id', 'desc')->get()->pluck('user.full_name', 'id'))
                            ->label(__('messages.invoice.patient_id').':')
                            ->native(false)
                            ->searchable()
                            ->required()
                            ->validationMessages([
                                'required' => __('messages.fields.the').' '.__('messages.advanced_payment.patient').' '.__('messages.fields.required'),
                            ]),
                        TextInput::make('receipt_no')
                            ->required()
                            ->disabled()
                            ->label(__('messages.advanced_payment.receipt_no').':')
                            ->default(generateUniqueBillNumber())
                            ->readOnly()
                            ->maxLength(191),
                        TextInput::make('amount')
                            ->required()
                            ->label(__('messages.invoice.amount').':')
                            ->placeholder(__('messages.invoice.amount').':')
                            ->numeric()
                            ->minValue(1),
                        DatePicker::make('date')
                            ->native(false)
                            ->disabled()
                            ->label(__('messages.advanced_payment.date').':')
                            ->required(),
                    ])
                    ->successNotificationTitle(__('messages.flash.advanced_payment_updated'))
                    ->iconButton(),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->visible(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return false;
                        }

                        return true;
                    })
                    ->successNotificationTitle(__('messages.flash.invoice_deleted')),
            ])
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.patient-advance-payment-relation-table');
    }
}
