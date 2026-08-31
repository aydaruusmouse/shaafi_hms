<?php

namespace App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Services\Resources\AmbulanceResource;
use App\Models\Ambulance;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListAmbulances extends ListRecords
{
    protected static string $resource = AmbulanceResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.ambulance.new_ambulance')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! Ambulance::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.ambulances').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('vehicle_number')->heading(heading: __('messages.ambulance.vehicle_number')),
                                Column::make('vehicle_model')->heading(heading: __('messages.ambulance.vehicle_model')),
                                Column::make('vehicle_type')->heading(heading: __('messages.ambulance.vehicle_type'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->vehicle_type == 1) {
                                            return __('messages.ambulance.owned');
                                        }
                                        if ($record->vehicle_type == 2) {
                                            return __('messages.ambulance.contractual');
                                        }
                                    }),
                                Column::make('year_made')->heading(heading: __('messages.ambulance.year_made')),
                                Column::make('driver_name')->heading(heading: __('messages.ambulance.driver_name')),
                                Column::make('driver_license')->heading(heading: __('messages.ambulance.driver_license')),
                                Column::make('driver_contact')->heading(heading: __('messages.ambulance.driver_contact')),
                                Column::make('note')->heading(heading: __('messages.ambulance.note')),
                                Column::make('is_available')->heading(heading: __('messages.ambulance.is_available'))
                                    ->formatStateUsing(fn ($state) => $state ? __('messages.bed.available') : __('messages.bed.not_available')),

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
