<?php

namespace App\Livewire;

use App\Models\Prescription;
use App\Models\PrescriptionMedicineModal;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PrescriptionMedicineTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public function GetRecord()
    {
        $id = Route::current()->parameter('record');
        $getMedicine = Prescription::with('getMedicine')->where('id', $id)->get();

        foreach ($getMedicine as $item) {
            $this->record = $item->getMedicine;
        }

        $Medicineids = $this->record->pluck('prescription_id')->toArray();
        $medicine = PrescriptionMedicineModal::with('medicines')->whereIn('prescription_id', $Medicineids);

        return $medicine;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(self::GetRecord())
            ->heading(__('messages.prescription.rx'))
            ->columns([
                TextColumn::make('medicines.name')
                    ->label(__('messages.prescription.medicine_name'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('medicines.dosage')
                    ->label(__('messages.ipd_patient_prescription.dosage'))
                    ->formatStateUsing(function ($record) {
                        if ($record->time == 0) {
                            return $record->dosage.' '.__('messages.prescription.after_meal');
                        } else {
                            return $record->dosage.' '.__('messages.prescription.before_meal');
                        }
                    })
                    ->default(__('messages.common.n/a')),
                TextColumn::make('medicines.day')
                    ->label(__('messages.prescription.duration'))
                    ->alignCenter()
                    ->default(fn ($record) => $record->day.' '.__('messages.day')),
                TextColumn::make('medicines.dose_interval')
                    ->label(__('messages.medicine_bills.dose_interval'))
                    ->alignEnd()
                    ->default(function ($record) {
                        if (empty($record->dose_interval)) {
                            return __('messages.common.n/a');
                        }

                        return Prescription::DOSE_INTERVAL[$record->dose_interval];
                    }),
            ])
            ->paginated(false)
            ->filters([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])
            ->emptyStateHeading(__('messages.common.no_data_found'));
    }

    public function render()
    {
        return view('livewire.prescription-medicine-table');
    }
}
