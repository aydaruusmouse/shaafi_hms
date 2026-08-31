<?php

namespace App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource\Pages;

use App\Filament\HospitalAdmin\Clusters\FrontOffice\Resources\VisitorResource;
use App\Models\Visitor;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListVisitors extends ListRecords
{
    protected static string $resource = VisitorResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()
                    ->icon('')
                    ->successNotificationTitle(__('messages.flash.visitor_saved')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! Visitor::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.visitors').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('purpose')->heading(heading: __('messages.visitor.purpose'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->purpose == 1) {
                                            return __('messages.visitor_filter.visit');
                                        } elseif ($record->purpose == 2) {
                                            return __('messages.visitor_filter.enquiry');
                                        } elseif ($record->purpose == 3) {
                                            return __('messages.visitor_filter.seminar');
                                        }
                                    }),
                                Column::make('name')->heading(heading: __('messages.common.name')),
                                Column::make('phone')->heading(heading: __('messages.visitor.phone')),
                                Column::make('id_card')->heading(heading: __('messages.visitor.id_card')),
                                Column::make('no_of_person')->heading(heading: __('messages.visitor.number_of_person')),
                                Column::make('date')->heading(heading: __('messages.visitor.date')),
                                Column::make('in_time')->heading(heading: __('messages.visitor.in_time'))
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->in_time)->format('h:i:s');
                                    }),
                                Column::make('out_time')->heading(heading: __('messages.visitor.out_time'))
                                    ->formatStateUsing(function ($record) {
                                        return Carbon::parse($record->out_time)->format('h:i:s');
                                    }),
                                Column::make('note')->heading(heading: __('messages.visitor.note')),

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
