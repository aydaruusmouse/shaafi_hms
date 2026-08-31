<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\ManualBillingPaymentsResource;
use App\Models\Bill;
use App\Models\BillTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListManualBillingPayments extends ListRecords
{
    protected static string $resource = ManualBillingPaymentsResource::class;

    public function changePaymentStatus($record, $status)
    {
        $billTransaction = BillTransaction::with('bill.patient.patientUser')->find($record);
        if ($status == BillTransaction::APPROVED) {
            DB::table('bill_transactions')
                ->where('id', $billTransaction->id)
                ->update([
                    'is_manual_payment' => $status,
                    'status' => BillTransaction::PAID,
                    'tenant_id' => $billTransaction->bill->patient->patientUser->tenant_id,
                ]);
            Bill::whereId($billTransaction->bill_id)->update(['status' => Bill::PAID]);

            return Notification::make()
                ->title(__('messages.flash.manual_payment_approved'))
                ->success()
                ->send();
        } else {
            if ($status == BillTransaction::DENIED) {
                DB::table('bill_transactions')
                    ->where('id', $billTransaction->id)
                    ->update([
                        'is_manual_payment' => $status,
                        'status' => BillTransaction::UNPAID,
                        'tenant_id' => $billTransaction->bill->patient->patientUser->tenant_id,
                    ]);
                Bill::whereId($billTransaction->bill_id)->update(['status' => Bill::UNPAID]);

                return Notification::make()
                    ->title(__('messages.flash.manual_payment_denied'))
                    ->success()
                    ->send();
            }
        }
    }
}
