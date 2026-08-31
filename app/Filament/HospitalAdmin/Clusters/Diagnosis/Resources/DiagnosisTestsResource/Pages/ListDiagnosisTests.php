<?php

namespace App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Diagnosis\Resources\DiagnosisTestsResource;
use App\Models\PatientDiagnosisTest;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListDiagnosisTests extends ListRecords
{
    protected static string $resource = DiagnosisTestsResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make(),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(function () {
                        $user = auth()->user();
                        $tenantId = getLoggedInUser()->tenant_id;

                        if ($user->hasRole('Patient')) {
                            return ! PatientDiagnosisTest::whereTenantId($tenantId)
                                ->where('patient_id', $user->owner_id)
                                ->exists();
                        }

                        if ($user->hasRole('Doctor')) {
                            return ! PatientDiagnosisTest::whereTenantId($tenantId)
                                ->where('doctor_id', $user->owner_id)
                                ->exists();
                        }

                        return ! PatientDiagnosisTest::whereTenantId($tenantId)->exists();
                    })
                    ->hidden(function () {
                        if (auth()->user()->hasRole(['Admin'])) {
                            return true;
                        }

                        return false;
                    })
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.doctors').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('patient.user.full_name')->heading(heading: __('messages.role.patient')),
                                Column::make('doctor.user.full_name')->heading(heading: __('messages.role.doctor')),
                                Column::make('category.name')->heading(heading: __('messages.patient_diagnosis_test.diagnosis_category')),
                                Column::make('report_number')->heading(heading: __('messages.patient_diagnosis_test.report_number')),

                            ]),

                    ]),

            ])
                ->icon('fas-angle-down')
                ->iconPosition(IconPosition::After)
                ->label(__('messages.common.actions'))
                ->button(),
        ];
    }
}
