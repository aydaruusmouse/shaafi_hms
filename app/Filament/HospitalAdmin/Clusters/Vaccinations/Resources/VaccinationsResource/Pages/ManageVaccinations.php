<?php

namespace App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources\VaccinationsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Vaccinations\Resources\VaccinationsResource;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageVaccinations extends ManageRecords
{
    protected static string $resource = VaccinationsResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.vaccination.new_vaccination'))->modalWidth('md')->createAnother(false)->successNotificationTitle(__('messages.flash.vaccination_saved'))->modalHeading(__('messages.vaccination.new_vaccination')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(function () {
                        if (! VaccinationsResource::getModel()::whereTenantId(getLoggedInUser()->tenant_id)->exists()) {
                            return true;
                        }

                        return false;
                    })
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.vaccinations').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('name')->heading(heading: __('messages.vaccination.name')),
                                Column::make('manufactured_by')->heading(heading: __('messages.vaccination.manufactured_by')),
                                Column::make('brand')->heading(heading: __('messages.vaccination.brand')),

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
