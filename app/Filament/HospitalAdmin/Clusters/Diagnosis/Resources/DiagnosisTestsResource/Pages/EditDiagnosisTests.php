<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource;
use App\Models\PatientDiagnosisProperty;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditDiagnosisTests extends EditRecord
{
    protected static string $resource = DiagnosisTestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {

        $input = PatientDiagnosisProperty::where('patient_diagnosis_id', $data['id'])->get()->toArray();

        $outputArray = [] + $data;

        foreach ($input as $item) {
            $outputArray[$item['property_name']] = $item['property_value'];
        }

        $repeaterInput = Arr::except($outputArray, ['patient_id', 'doctor_id', 'category_id', 'report_number', 'age', 'height', 'weight', 'average_glucose', 'fasting_blood_sugar', 'urine_sugar', 'blood_pressure', 'diabetes', 'cholesterol', 'id', 'tenant_id', 'created_at', 'updated_at']) ?? [];

        foreach ($repeaterInput as $property_name => $property_value) {
            $repeaterInputGet['add_other_diagnosis_property'][] = [
                'property_name' => $property_name,
                'property_value' => $property_value,
            ];
        }

        $repeaterInputGet = $repeaterInputGet ?? [];
        $input = $outputArray + $repeaterInputGet;

        return $input;
    }

    public function prepareInputForPatientDiagnosisTest(array $input): array
    {
        $item = [];
        foreach ($input as $key => $data) {
            foreach ($data as $index => $value) {
                $item[$index][$key] = $value;
            }
        }

        return $item;
    }

    protected function handleRecordUpdate(Model $record, array $input): Model
    {
        if (isset($input['add_other_diagnosis_property'])) {
            foreach ($input['add_other_diagnosis_property'] as $item) {
                $outputArray[$item['property_name']] = $item['property_value'];
            }
            if (isset($outputArray)) {
                $input = $outputArray + $input;
            }
        }

        // Step 2: Update the main diagnosis test record
        $record->update(Arr::only(
            $input,
            ['patient_id', 'doctor_id', 'category_id', 'report_number']
        ));

        // Step 3: Delete the old diagnosis properties related to the current test
        PatientDiagnosisProperty::wherePatientDiagnosisId($record->id)->delete();

        $propertyInputArray = Arr::except(
            $input,
            ['_token', 'patient_id', 'doctor_id', 'category_id', 'report_number', 'property_name', 'property_value', 'add_other_diagnosis_property']
        );

        foreach ($propertyInputArray as $key => $value) {
            PatientDiagnosisProperty::create([
                'patient_diagnosis_id' => $record->id,
                'property_name' => $key,
                'property_value' => $value,
            ]);
        }

        if (isset($input['property_name']) && ! empty($input['property_name'])) {
            $otherProperty = Arr::only($input, ['property_name', 'property_value']);
            $patientDiagnosisTestItemInput = $this->prepareInputForPatientDiagnosisTest($otherProperty);

            foreach ($patientDiagnosisTestItemInput as $key => $data) {
                if ($data['property_name'] !== null && $data['property_value'] !== null) {
                    $data['patient_diagnosis_id'] = $record->id;
                    PatientDiagnosisProperty::create($data);
                }
            }
        }

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.patient_diagnosis_updated');
    }
}
