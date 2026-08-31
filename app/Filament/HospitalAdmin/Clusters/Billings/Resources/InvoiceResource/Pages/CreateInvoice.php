<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Repositories\InvoiceRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Termwind\Components\Dd;
use Validator;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function prepareInputForInvoiceItem(array $input): array
    {
        $items = [];
        foreach ($input as $key => $data) {
            foreach ($data as $index => $value) {
                $items[$index][$key] = $value;
                if (! (isset($items[$index]['price']) && $key == 'price')) {
                    continue;
                }
                $items[$index]['price'] = removeCommaFromNumbers($items[$index]['price']);
            }
        }

        return $items;
    }

    protected function handleRecordCreation(array $input): Model
    {
        $accountIds = [];
        $descriptions = [];
        $quantities = [];
        $prices = [];

        foreach ($input['invoice'] as $item) {
            $accountIds[] = $item['account_id'];
            $descriptions[] = $item['description'];
            $quantities[] = $item['quantity'];
            $prices[] = $item['price'];
        }

        $input = [
            ...$input,
            'account_id' => $accountIds,
            'description' => $descriptions,
            'quantity' => $quantities,
            'price' => $prices,
        ];
        $input['amount'] = $input['total_amount'];
        $input = Arr::only($input, ['account_id', 'description', 'quantity', 'price', 'patient_id', 'invoice_date', 'discount', 'status', 'invoice_id', 'amount']);
        // dd($input);
        $invoiceItemInputArray = Arr::only($input, ['account_id', 'description', 'quantity', 'price']);
        $invoiceExist = Invoice::where('invoice_id', $input['invoice_id'])->exists();
        if ($invoiceExist) {
            Notification::make()
                ->title(__('messages.flash.invoice_id_already_exist'))
                ->danger()
                ->send();
        }
        /** @var Invoice $invoice */
        $invoice = Invoice::create(Arr::only($input, ['patient_id', 'invoice_date', 'discount', 'status', 'invoice_id']));
        $totalAmount = 0;
        $invoiceItemInput = $this->prepareInputForInvoiceItem($invoiceItemInputArray);
        foreach ($invoiceItemInput as $key => $data) {
            $validator = Validator::make($data, InvoiceItem::$rules);

            if ($validator->fails()) {
                Notification::make()
                    ->title($validator->errors()->first())
                    ->danger()
                    ->send();
            }

            $data['total'] = $data['price'] * $data['quantity'];
            $totalAmount += $data['total'];

            /** @var BillItems $invoiceItem */
            $invoiceItem = new InvoiceItem($data);
            $invoice->invoiceItems()->save($invoiceItem);
        }
        $invoice->amount = $totalAmount;
        $invoice->save();

        app(InvoiceRepository::class)->saveNotification($invoice->toArray());

        return $invoice;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record->id]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('messages.flash.invoice_saved');
    }
}
