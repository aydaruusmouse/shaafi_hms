<?php

namespace App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources\VaccinatedPatientsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources\VaccinatedPatientsResource;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageVaccinatedPatients extends ManageRecords
{
    protected static string $resource = VaccinatedPatientsResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.vaccinated_patient.new_vaccinate_patient'))->createAnother(false)->successNotificationTitle(__('messages.flash.vaccinated_patients_saved'))->modalHeading(__('messages.vaccinated_patient.new_vaccinate_patient')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! VaccinatedPatientsResource::getModel()::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->hidden(function () {
                        if (auth()->user()->hasRole('Patient')) {
                            return true;
                        }

                        return false;
                    })
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.vaccinated_patients').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('patient.user.full_name')->heading(heading: __('messages.vaccinated_patient.patient')),
                                Column::make('vaccination.name')->heading(heading: __('messages.vaccinated_patient.vaccine')),
                                Column::make('vaccination_serial_number')->heading(heading: __('messages.vaccinated_patient.serial_no')),
                                Column::make('dose_number')->heading(heading: __('messages.vaccinated_patient.does_no')),
                                Column::make('dose_given_date')->heading(heading: __('messages.vaccinated_patient.dose_given_date')),
                                Column::make('description')->heading(heading: __('messages.vaccinated_patient.description')),

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
