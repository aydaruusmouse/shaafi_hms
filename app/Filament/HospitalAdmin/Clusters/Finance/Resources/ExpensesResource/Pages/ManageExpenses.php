<?php

namespace App\Filament\HospitalAdmin\Clusters\Finance\Resources\ExpensesResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Finance\Resources\ExpensesResource;
use App\Models\Expense;
use App\Repositories\ExpenseRepository;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ManageExpenses extends ManageRecords
{
    protected static string $resource = ExpensesResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.expense.new_expense'))->successNotificationTitle(__('messages.flash.expense_saved'))->createAnother(false)->modalHeading(__('messages.expense.new_expense'))
                    ->before(function ($data) {
                        $isExist = static::getModel()::whereTenantId(getLoggedInUser()->tenant_id)->where('name', $data['name'])->exists();

                        if ($isExist) {
                            FilamentNotification::make()
                                ->danger()
                                ->title(__('messages.user.name').' '.__('messages.common.is_already_exists'))
                                ->send();
                            $this->halt();
                        }
                    })
                    ->after(function ($record) {
                        app(ExpenseRepository::class)->createNotification($record->toArray());
                    }),
                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! Expense::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.expenses').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('name')->heading(heading: __('messages.incomes.name')),
                                Column::make('expense_head')->heading(heading: __('messages.incomes.income_head'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->expense_head == 1) {
                                            return __('messages.expense_filter.building_rent');
                                        } elseif ($record->expense_head == 2) {
                                            return __('messages.expense_filter.equipments');
                                        } elseif ($record->expense_head == 3) {
                                            return __('messages.expense_filter.electricity_bill');
                                        } elseif ($record->expense_head == 4) {
                                            return __('messages.expense_filter.telephone_bill');
                                        } elseif ($record->expense_head == 5) {
                                            return __('messages.expense_filter.power_generator_fuel_charge');
                                        } elseif ($record->expense_head == 6) {
                                            return __('messages.expense_filter.tea_expense');
                                        }
                                    }),
                                Column::make('invoice_number')->heading(heading: __('messages.incomes.invoice_number')),
                                Column::make('date')->heading(heading: __('messages.expense.date'))
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
