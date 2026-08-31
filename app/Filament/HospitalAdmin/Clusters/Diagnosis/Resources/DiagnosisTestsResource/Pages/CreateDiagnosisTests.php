<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource;
use App\Models\PatientDiagnosisProperty;
use App\Models\PatientDiagnosisTest;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateDiagnosisTests extends CreateRecord
{
    protected static string $resource = DiagnosisTestsResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.patient_diagnosis_saved');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
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

    protected function handleRecordCreation(array $input): Model
    {
        if (is_null($input['doctor_id'])) {
            $array['doctor_id'] = auth()->user()->id;
        }
        foreach ($input['add_other_diagnosis_property'] as $item) {
            $outputArray[$item['property_name']] = $item['property_value'];
        }

        if (isset($outputArray)) {
            $input = $outputArray + $input;
        }

        /** @var PatientDiagnosisTest $patientDiagnosisTest */
        $patientDiagnosisTest = PatientDiagnosisTest::create(Arr::only(
            $input,
            ['patient_id', 'doctor_id', 'category_id', 'report_number']
        ));
        if (! empty($input['add_other_diagnosis_property']) || count($input['add_other_diagnosis_property']) > 0 || $input['add_other_diagnosis_property'] != []) {
            $propertyInputArray = Arr::except(
                $input,
                ['_token', 'patient_id', 'doctor_id', 'category_id', 'report_number', 'property_name', 'property_value', 'add_other_diagnosis_property']
            );
        } else {
            $propertyInputArray = Arr::except(
                $input,
                ['_token', 'patient_id', 'doctor_id', 'category_id', 'report_number', 'property_name', 'property_value', 'add_other_diagnosis_property']
            );
        }

        foreach ($propertyInputArray as $key => $value) {
            PatientDiagnosisProperty::create([
                'patient_diagnosis_id' => $patientDiagnosisTest->id,
                'property_name' => $key,
                'property_value' => $value,
            ]);
        }

        if (isset($input['property_name']) && ! empty($input['property_name'])) {
            $otherProperty = Arr::only($input, ['property_name', 'property_value']);
            $patientDiagnosisTestItemInput = $this->prepareInputForPatientDiagnosisTest($otherProperty);

            foreach ($patientDiagnosisTestItemInput as $key => $data) {
                if ($data['property_name'] != null && $data['property_value'] != null) {
                    $data['patient_diagnosis_id'] = $patientDiagnosisTest->id;
                    PatientDiagnosisProperty::create($data);
                }
            }
        }

        return $patientDiagnosisTest;
    }
}
