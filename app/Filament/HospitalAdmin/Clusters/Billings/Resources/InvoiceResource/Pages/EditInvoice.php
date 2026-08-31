<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Repositories\InvoiceItemRepository;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Validator;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Retrieve invoice items and prepare data for editing
        $invoiceItems = InvoiceItem::where('invoice_id', $data['id'])->get();

        $formattedData = [];

        // Loop through each InvoiceItem and format the data
        foreach ($invoiceItems as $item) {
            $formattedData[] = [
                'account_id' => $item->account_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        }
        foreach ($formattedData as &$item) {
            $item['amount'] = $item['price'] * $item['quantity'];
        }

        $data['invoice'] = $formattedData;
        $data['discount_amount'] = $data['discount'];
        $data['sub_total'] = $data['amount'];
        $data['total_amount'] = $data['amount'] - $data['amount'] * $data['discount'] / 100;

        return $data;
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

    protected function handleRecordUpdate(Model $record, array $input): Model
    {

        $invoiceId = $record->id;

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

        // Combine data into the input array
        $input = [
            ...$input,
            'account_id' => $accountIds,
            'description' => $descriptions,
            'quantity' => $quantities,
            'price' => $prices,
        ];
        $input['total'] = $input['total_amount'];

        $input = Arr::only($input, [
            'account_id',
            'description',
            'quantity',
            'price',
            'patient_id',
            'invoice_date',
            'discount',
            'status',
            'invoice_id',
            'amount',
        ]);

        $invoiceItemInputArr = Arr::only($input, ['account_id', 'description', 'quantity', 'price', 'id']);

        /** @var Invoice $invoice */
        Invoice::where('id', $invoiceId)->update(Arr::only($input, ['patient_id', 'invoice_date', 'discount', 'status']), $invoiceId);
        $totalAmount = 0;

        $invoiceItemInput = $this->prepareInputForInvoiceItem($invoiceItemInputArr);
        foreach ($invoiceItemInput as $key => $data) {
            $validator = Validator::make($data, InvoiceItem::$rules, [
                'account_id.integer' => 'Please select an account',
            ]);

            if ($validator->fails()) {
                Notification::make()
                    ->danger()
                    ->title($validator->errors()->first())
                    ->send();
            }

            $data['total'] = $data['price'] * $data['quantity'];
            $invoiceItemInput[$key] = $data;
            $totalAmount += $data['total'];
        }
        /** @var InvoiceItemRepository $invoiceItemRepo */
        $invoiceItemRepo = app(InvoiceItemRepository::class);
        $invoice = $record;
        $invoiceItemRepo->updateInvoiceItem($invoiceItemInput, $invoice->id);

        $invoice->amount = $totalAmount;
        $invoice->save();

        return $invoice;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('messages.flash.invoice_updated');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record->id]);
    }
}
