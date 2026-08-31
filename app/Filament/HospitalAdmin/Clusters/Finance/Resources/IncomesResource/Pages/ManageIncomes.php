<?php

namespace App\Filament\HospitalAdmin\Clusters\Finance\Resources\IncomesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Finance\Resources\IncomesResource;
use App\Models\Income;
use App\Repositories\IncomeRepository;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageIncomes extends ManageRecords
{
    protected static string $resource = IncomesResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.incomes.new_income'))->createAnother(false)->successNotificationTitle(__('messages.flash.income_saved'))->after(function ($record) {
                    app(IncomeRepository::class)->createNotification($record->toArray());
                })->before(function ($record, $data, $action) {
                    $currentModel = static::getModel();
                    getUniqueNameValidation($currentModel, $record, $data, $this, false);
                }),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! Income::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.invoices').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('name')->heading(heading: __('messages.incomes.name')),
                                Column::make('income_head')->heading(heading: __('messages.incomes.income_head'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->income_head == 1) {
                                            return __('messages.income_filter.canteen_rate');
                                        } elseif ($record->income_head == 2) {
                                            return __('messages.income_filter.hospital_charges');
                                        } elseif ($record->income_head == 3) {
                                            return __('messages.income_filter.special_campaign');
                                        } elseif ($record->income_head == 4) {
                                            return __('messages.income_filter.vehicle_stand_charge');
                                        }
                                    }),
                                Column::make('invoice_number')->heading(heading: __('messages.incomes.invoice_number')),
                                Column::make('date')->heading(heading: __('messages.incomes.date'))
                                    ->formatStateUsing(function ($record) {
                                        return $record->date ? \Carbon\Carbon::parse($record->date)->translatedFormat('jS M, Y') : __('messages.common.n/a');
                                    }),
                                Column::make('amount')->heading(heading: __('messages.incomes.amount'))
                                    ->formatStateUsing(function ($record) {
                                        return number_format($record->amount, 2);
                                    }),
                                Column::make('description')->heading(heading: __('messages.incomes.description')),
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
