<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\PaymentReportResource;
use App\Models\Payment;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListPaymentReports extends ListRecords
{
    protected static string $resource = PaymentReportResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()->icon('')
                ->disabled(! Payment::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                ->label(__('messages.common.export_to_excel'))->exports([
                    ExcelExport::make()
                        ->withFilename(__('messages.payments').' '.__('messages.reports').'-'.now()->format('Y-m-d').'.xlsx')
                        ->modifyQueryUsing(function (Builder $query) {
                            return $query->where('tenant_id', auth()->user()->tenant_id);
                        })->withColumns([
                            Column::make('id')->heading('No')->formatStateUsing(function () {
                                return $this->i++;
                            }),
                            Column::make('account.type')->heading(heading: __('messages.account.type'))
                                ->formatStateUsing(function ($record) {
                                    return $record->account->type == 1 ? 'Debit' : 'Credit';
                                }),
                            Column::make('payment_date')->heading(heading: __('messages.payment.payment_date'))
                                ->formatStateUsing(function ($record) {
                                    return $record->payment_date ? \Carbon\Carbon::parse($record->payment_date)->translatedFormat('jS M, Y') : __('messages.common.n/a');
                                }),
                            Column::make('account.name')->heading(heading: __('messages.payment.account_name')),
                            Column::make('pay_to')->heading(heading: __('messages.payment.pay_to')),
                            Column::make('amount')->heading(heading: __('messages.payment.amount')),
                            Column::make('description')->heading(heading: __('messages.common.description')),
                        ]),
                ]),
        ];
    }
}
