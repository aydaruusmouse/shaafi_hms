<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\CallLogResource;
use App\Models\CallLog;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListCallLogs extends ListRecords
{
    protected static string $resource = CallLogResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()
                    ->icon('')
                    ->successNotificationTitle(__('messages.flash.call_log_saved'))
                    ->label(__('messages.call_log.new')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! CallLog::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.call_logs').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('name')->heading(heading: __('messages.common.name')),
                                Column::make('phone')->heading(heading: __('messages.user.phone')),
                                Column::make('date')->heading(heading: __('messages.call_log.received_on')),
                                Column::make('follow_up_date')->heading(heading: __('messages.call_log.follow_up_date')),
                                Column::make('call_type')->heading(heading: __('messages.call_log.call_type'))
                                    ->formatStateUsing(function ($record) {
                                        return $record->call_type == 1 ? __('messages.call_log.incoming') : __('messages.call_log.outgoing');
                                    }),
                                Column::make('note')->heading(heading: __('messages.call_log.note')),

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
