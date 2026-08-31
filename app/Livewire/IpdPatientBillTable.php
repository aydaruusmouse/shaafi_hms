<?php

namespace App\Livewire;

use App\Models\IpdCharge;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class IpdPatientBillTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public $processedData = [];

    public function mount()
    {
        $this->id = Route::current()->parameter('record');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(IpdCharge::where('ipd_patient_department_id', $this->id))
            ->columns([
                TextColumn::make('charge_type_id')
                    ->label(__('messages.ipd_patient_charges.charge_type_id'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(function ($record) {
                        if ($record->charge_type_id === 1) {
                            return __('messages.charge_filter.procedure');
                        } elseif ($record->charge_type_id === 2) {
                            return __('messages.charge_filter.investigation');
                        } elseif ($record->charge_type_id === 3) {
                            return __('messages.charge_filter.supplier');
                        } elseif ($record->charge_type_id === 4) {
                            return __('messages.charge_filter.operation_theater');
                        } else {
                            return __('messages.charge_filter.others');
                        }
                    }),
                TextColumn::make('chargecategory.name')
                    ->default(__('messages.common.n/a'))
                    ->label(__('messages.medicine.category')),
                TextColumn::make('date')
                    ->label(__('messages.ipd_patient_charges.date'))
                    ->default(__('messages.common.n/a'))
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->translatedFormat('jS M, Y')),
                TextColumn::make('applied_charge')
                    ->label(__('messages.ipd_patient_charges.applied_charge'))
                    ->alignEnd()
                    ->formatStateUsing(function ($record) {
                        if (! empty($record->applied_charge)) {
                            return getCurrencyFormat($record->applied_charge);
                        } else {
                            return __('messages.common.n/a');
                        }
                    })
                    ->summarize([
                        Sum::make()
                            ->formatStateUsing(fn ($state) => (getCurrencyFormat($state)))
                            ->label(''),
                    ]),
            ])
            ->paginated(false)
            ->actionsColumnLabel(__('messages.common.action'))
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public function render()
    {
        return view('livewire.ipd-patient-bill-table');
    }
}
