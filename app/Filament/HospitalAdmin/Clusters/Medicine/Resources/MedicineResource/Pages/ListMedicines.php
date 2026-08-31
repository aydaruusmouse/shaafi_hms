<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineResource;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListMedicines extends ListRecords
{
    protected static string $resource = MedicineResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.prescription.new_medicine')),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
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
                                Column::make('name')->heading(heading: __('messages.medicine.medicine')),
                                Column::make('brand.name')->heading(heading: __('messages.medicine.brand')),
                                Column::make('category.name')->heading(heading: __('messages.medicine.category')),
                                Column::make('salt_composition')->heading(heading: __('messages.medicine.salt_composition')),
                                Column::make('selling_price')->heading(heading: __('messages.medicine.selling_price')),
                                Column::make('buying_price')->heading(heading: __('messages.medicine.buying_price')),
                                Column::make('side_effects')->heading(heading: __('messages.medicine.side_effects')),
                                Column::make('description')->heading(heading: __('messages.medicine.description')),
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
