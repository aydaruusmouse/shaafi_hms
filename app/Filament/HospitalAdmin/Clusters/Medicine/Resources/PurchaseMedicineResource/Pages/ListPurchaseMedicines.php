<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\PurchaseMedicineResource;
use App\Models\PurchaseMedicine;
use Filament\Actions;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Database\Query\Builder;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListPurchaseMedicines extends ListRecords
{
    protected static string $resource = PurchaseMedicineResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\CreateAction::make()->label(__('messages.purchase_medicine.purchase_medicine')),

                ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                    ->disabled(! PurchaseMedicine::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                    ->label(__('messages.common.export_to_excel'))->exports([
                        ExcelExport::make()
                            ->withFilename(__('messages.purchase_medicine.purchase_medicine').'-'.now()->format('Y-m-d').'.xlsx')
                            ->modifyQueryUsing(function (Builder $query) {
                                return $query->where('tenant_id', auth()->user()->tenant_id);
                            })
                            ->withColumns([
                                Column::make('id')->heading('No')->formatStateUsing(function () {
                                    return $this->i++;
                                }),
                                Column::make('purchase_no')->heading(heading: __('messages.purchase_medicine.purchase_number'))
                                    ->formatStateUsing(function ($record) {
                                        return '#'.$record->purchase_no;
                                    }),
                                Column::make('total')->heading(heading: __('messages.purchase_medicine.total')),
                                Column::make('tax')->heading(heading: __('messages.purchase_medicine.tax')),
                                Column::make('discount')->heading(heading: __('messages.purchase_medicine.discount')),
                                Column::make('net_amount')->heading(heading: __('messages.purchase_medicine.net_amount')),
                                Column::make('payment_type')->heading(heading: __('messages.purchase_medicine.payment_mode'))
                                    ->formatStateUsing(function ($record) {
                                        if ($record->payment_type == 0) {
                                            return 'Cash';
                                        } elseif ($record->payment_type == 1) {
                                            return 'Cheque';
                                        } elseif ($record->payment_type == 2) {
                                            return 'Razorpay';
                                        } elseif ($record->payment_type == 3) {
                                            return 'Paystack';
                                        } elseif ($record->payment_type == 4) {
                                            return 'Phonepe';
                                        } elseif ($record->payment_type == 5) {
                                            return 'Stripe';
                                        } elseif ($record->payment_type == 6) {
                                            return 'Flutterwave';
                                        }
                                    }),

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
