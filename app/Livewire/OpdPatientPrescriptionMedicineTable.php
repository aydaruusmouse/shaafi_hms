<?php

namespace App\Livewire;

use App\Models\OpdPrescription;
use App\Models\OpdPrescriptionItem;
use App\Models\Prescription;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class OpdPatientPrescriptionMedicineTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $record;

    public $id;

    public function mount(string $recordId)
    {
        $this->id = $recordId;
    }

    public function GetRecord()
    {
        $getMedicine = OpdPrescription::with('opdPrescriptionItems')->whereOpdPatientDepartmentId($this->id)->get();

        foreach ($getMedicine as $item) {
            $this->record = $item->opdPrescriptionItems;
        }

        $Medicineids = $this->record->pluck('opd_prescription_id')->toArray();
        $medicine = OpdPrescriptionItem::with(['medicineCategory', 'medicine', 'prescriptionMedicines'])->whereIn('opd_prescription_id', $Medicineids);

        return $medicine;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->GetRecord())
            ->columns([
                TextColumn::make('medicineCategory.name')
                    ->label(__('messages.medicine.medicine_category'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('medicine.name')
                    ->label(__('messages.prescription.medicine_name'))
                    ->default(__('messages.common.n/a')),
                TextColumn::make('medicine.dosage')
                    ->label(__('messages.ipd_patient_prescription.dosage'))
                    ->formatStateUsing(function ($record) {
                        if ($record->time == 0) {
                            return $record->dosage.' '.__('messages.prescription.after_meal');
                        } else {
                            return $record->dosage.' '.__('messages.prescription.before_meal');
                        }
                    })
                    ->default(__('messages.common.n/a')),
                TextColumn::make('medicine.dose_interval')
                    ->label(__('messages.medicine_bills.dose_interval'))
                    ->default(function ($record) {
                        if (empty($record->dose_interval)) {
                            return __('messages.common.n/a');
                        }

                        return Prescription::DOSE_INTERVAL[$record->dose_interval];
                    }),
                TextColumn::make('instruction')
                    ->label(__('messages.ipd_patient_prescription.instruction'))
                    ->default(__('messages.common.n/a')),
            ])
            ->actionsColumnLabel(__('messages.common.action'))
            ->paginated(false)
            ->filters([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public function render()
    {
        return view('livewire.opd-patient-prescription-medicine-table');
    }
}
