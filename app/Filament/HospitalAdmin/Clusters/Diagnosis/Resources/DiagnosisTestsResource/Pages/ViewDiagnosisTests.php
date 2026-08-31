<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource;
use App\Repositories\PatientDiagnosisTestRepository;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Lang;

class ViewDiagnosisTests extends ViewRecord
{
    protected static string $resource = DiagnosisTestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label(__('messages.patient_diagnosis_test.print_diagnosis_test'))
                ->color('success')
                ->url(fn ($record) => route('patient.diagnosis.test.pdf', ['patientDiagnosisTest' => $record->id]), shouldOpenInNewTab: true),
            Actions\EditAction::make(),
            Actions\Action::make('back')
                ->label(__('messages.common.back'))
                ->outlined()
                ->url(url()->previous()),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return __('messages.patient_diagnosis_test.patient_diagnosis_test_details');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('patient.user.full_name')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.patient_diagnosis_test.patient').':'),
                    TextEntry::make('doctor.user.full_name')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.patient_diagnosis_test.doctor').':'),
                    TextEntry::make('category.name')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.patient_diagnosis_test.diagnosis_category').':'),
                    TextEntry::make('report_number')
                        ->default(__('messages.common.n/a'))
                        ->label(__('messages.patient_diagnosis_test.report_number')),
                    ...self::setExtraFields(),
                    TextEntry::make('created_at')
                        ->formatStateUsing(fn ($state) => $state->diffForHumans())
                        ->label(__('messages.common.created_at').':'),
                    TextEntry::make('patientDiagnosisProperties.updated_at')
                        ->formatStateUsing(function ($state) {
                            $firstDate = explode(',', $state)[0] ?? null;

                            return $firstDate ? Carbon::parse(trim($firstDate))->diffForHumans() : null;
                        })
                        ->label(__('messages.common.updated_at').':'),

                ])->columns(2),
            ]);
    }

    public function setExtraFields()
    {
        $id = $this->record->id;
        $patientDiagnosisTests = app(PatientDiagnosisTestRepository::class)->getPatientDiagnosisTestProperty($id);

        if (isset($patientDiagnosisTests) && ! empty($patientDiagnosisTests)) {
            foreach ($patientDiagnosisTests as $patientDiagnosisTest) {
                if ($patientDiagnosisTest->property_name != null) {
                    $extraFields[] = TextEntry::make($patientDiagnosisTest->property_name)
                        ->default($patientDiagnosisTest->property_value ? $patientDiagnosisTest->property_value : __('messages.common.n/a'))
                        ->extraAttributes(['style' => 'word-break: break-all;'])
                        ->label(function () use ($patientDiagnosisTest) {
                            if (Lang::has('messages.patient_diagnosis_test.'.$patientDiagnosisTest->property_name.'')) {
                                return __('messages.patient_diagnosis_test.'.$patientDiagnosisTest->property_name.'');
                            } else {
                                return str_replace('_', ' ', $patientDiagnosisTest->property_name);
                            }
                        });
                }
            }
        }

        return $extraFields;
    }
}
