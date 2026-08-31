<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\PostalResource;
use App\Models\Postal;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListPostals extends ListRecords
{
    protected static string $resource = PostalResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.postal.new_receive'))->modalWidth('3xl')->createAnother(false)->modalHeading(__('messages.postal.new_receive'))->successNotificationTitle(__('messages.flash.postal_receive_saved')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! Postal::where('type', 1)->whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.postal_receive').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('from_title')->heading(heading: __('messages.postal.from_title')),
                                Column::make('reference_no')->heading(heading: __('messages.postal.reference_no')),
                                Column::make('to_title')->heading(heading: __('messages.postal.to_title')),
                                Column::make('date')->heading(heading: __('messages.postal.date')),
                                Column::make('address')->heading(heading: __('messages.postal.address')),

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
