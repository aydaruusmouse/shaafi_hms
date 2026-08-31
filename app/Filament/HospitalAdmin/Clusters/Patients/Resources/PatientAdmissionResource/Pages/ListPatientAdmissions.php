<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientAdmissionResource;
use App\Models\Doctor;
use App\Models\PatientAdmission;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListPatientAdmissions extends ListRecords
{
    protected static string $resource = PatientAdmissionResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()
                    ->icon('')
                    ->label(__('messages.patient_admission.new_patient_admission')),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(function () {
                        if (auth()->user()->hasRole('Doctor')) {
                            $doctorId = Doctor::whereUserId(getLoggedInUserId())->first()->id;

                            return ! PatientAdmission::whereTenantId(getLoggedInUser()->tenant_id)
                                ->whereDoctorId($doctorId)
                                ->exists();
                        } else {
                            return ! PatientAdmission::whereTenantId(getLoggedInUser()->tenant_id)
                                ->exists();
                        }
                    })
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.patient_admissions').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })

                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('patient.user.full_name')->heading(heading: __('messages.role.patient')),
                                Column::make('doctor.user.full_name')->heading(heading: __('messages.role.doctor')),
                                Column::make('patient_admission_id')->heading(heading: __('messages.bill.admission_id')),
                                Column::make('admission_date')->heading(heading: __('messages.bill.admission_date'))
                                    ->formatStateUsing(function ($record) {
                                        return $record->admission_date ? \Carbon\Carbon::parse($record->admission_date)->translatedFormat('jS M, Y') : __('messages.common.n/a');
                                    }),
                                Column::make('discharge_date')->heading(heading: __('messages.bill.discharge_date'))
                                    ->formatStateUsing(function ($record) {
                                        return $record->discharge_date ? \Carbon\Carbon::parse($record->discharge_date)->translatedFormat('jS M, Y') : __('messages.common.n/a');
                                    }),
                                Column::make('package.name')->heading(heading: __('messages.patient_admission.package')),
                                Column::make('insurance.name')->heading(heading: __('messages.delete.insurance')),
                                Column::make('bed.name')->heading(heading: __('messages.delete.bed')),
                                Column::make('policy_no')->heading(heading: __('messages.bill.policy_no')),
                                Column::make('agent_name')->heading(heading: __('messages.patient_admission.agent_name')),
                                Column::make('guardian_name')->heading(heading: __('messages.patient_admission.guardian_name')),
                                Column::make('guardian_relation')->heading(heading: __('messages.patient_admission.guardian_relation')),
                                Column::make('guardian_contact')->heading(heading: __('messages.patient_admission.guardian_contact')),
                                Column::make('guardian_address')->heading(heading: __('messages.patient_admission.guardian_address')),
                                Column::make('status')->heading(heading: __('messages.common.status'))
                                    ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.deactive')),

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
