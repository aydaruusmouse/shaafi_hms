<?php

namespace App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Patients\Resources\CaseResource;
use App\Models\PatientCase;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListCases extends ListRecords
{
    protected static string $resource = CaseResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()
                    ->icon('')
                    ->label(__('messages.case.new_case')),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! PatientCase::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->hidden(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return true;
                        }

                        return false;
                    })
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.patients_cases').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('case_id')->heading(heading: __('messages.case.case_id')),
                                Column::make('patient.user.full_name')->heading(heading: __('messages.case.patient')),
                                Column::make('phone')->heading(heading: __('messages.user.phone')),
                                Column::make('doctor.user.full_name')->heading(heading: __('messages.appointment.doctor')),
                                Column::make('date')->heading(heading: __('messages.case.case_date')),
                                Column::make('status')->heading(heading: __('messages.common.status'))
                                    ->formatStateUsing(fn ($state) => $state ? __('messages.common.active') : __('messages.common.deactive')),
                                Column::make('fee')->heading(heading: __('messages.case.fee')),
                                Column::make('description')->heading(heading: __('messages.case.description')),

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
