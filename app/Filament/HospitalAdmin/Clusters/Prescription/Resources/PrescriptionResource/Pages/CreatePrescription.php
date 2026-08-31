<?php

namespace App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Prescription\Resources\PrescriptionResource;
use App\Models\Medicine;
use App\Repositories\PrescriptionRepository;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePrescription extends CreateRecord
{
    protected static string $resource = PrescriptionResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->url(url()->previous()),
        ];
    }

    protected function getRedirectUrl(): string
    {

        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {

        return __('messages.flash.prescription_saved');
    }

    public function handleRecordCreation(array $input): Model
    {
        if (is_null($input['doctor_id'])) {
            $array['doctor_id'] = auth()->user()->id;
        }

        $input['status'] = isset($input['status']) ? 1 : 0;

        $structuredMedicines = [
            'medicine' => [],
            'dosage' => [],
            'day' => [],
            'time' => [],
            'dose_interval' => [],
            'comment' => [],
        ];

        foreach ($input['getMedicine'] as $medicineData) {
            $structuredMedicines['medicine'][] = $medicineData['medicine'];
            $structuredMedicines['dosage'][] = $medicineData['dosage'];
            $structuredMedicines['day'][] = $medicineData['day'];
            $structuredMedicines['time'][] = $medicineData['time'];
            $structuredMedicines['dose_interval'][] = $medicineData['dose_interval'];
            $structuredMedicines['comment'][] = $medicineData['comment'];
        }

        if ($structuredMedicines['medicine'][0] == null) {
            Notification::make()
                ->title(__('messages.medicine_bills.select_medicine'))
                ->danger()
                ->send();

            $this->halt();

        } elseif ($structuredMedicines['dose_interval'][0] == null) {
            Notification::make()
                ->title(__('messages.new_change.select_dose_interval'))
                ->danger()
                ->send();

            $this->halt();
        }

        if (isset($structuredMedicines['medicine'])) {
            $arr = collect($structuredMedicines['medicine']);
            $duplicateIds = $arr->duplicates();
            foreach ($structuredMedicines['medicine'] as $key => $value) {
                $medicine = Medicine::find($structuredMedicines['medicine'][$key]);
                if (! empty($duplicateIds)) {
                    foreach ($duplicateIds as $key => $value) {
                        $medicine = Medicine::find($duplicateIds[$key]);
                        Notification::make()
                            ->title(__('messages.medicine_bills.duplicate_medicine'));
                    }
                }
            }
            foreach ($structuredMedicines['medicine'] as $key => $value) {
                $medicine = Medicine::find($structuredMedicines['medicine'][$key]);
                $qty = $structuredMedicines['day'][$key] * $structuredMedicines['dose_interval'][$key];
                if ($medicine->available_quantity < $qty) {
                    $available = $medicine->available_quantity == null ? 0 : $medicine->available_quantity;
                    Notification::make()
                        ->title(__('messages.medicine_bills.available_quantity').$medicine->name.__('messages.new_change.is').$available.'.');
                }
            }
        }

        $prescription = app(PrescriptionRepository::class)->create($input);
        app(PrescriptionRepository::class)->createPrescription($input, $prescription);
        app(PrescriptionRepository::class)->createNotification($input);

        return $prescription;
    }
}
