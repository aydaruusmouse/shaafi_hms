<?php

namespace App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Medicine\Resources\MedicineBillsResource;
use App\Http\Controllers\MedicineBillController;
use App\Models\MedicineBill;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMedicineBills extends CreateRecord
{
    protected static string $resource = MedicineBillsResource::class;

    protected static bool $canCreateAnother = false;

    protected function getActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),
        ];
    }

    public function handleRecordCreation(array $input): Model
    {
        $input['total'] = removeCommaFromNumbers(number_format($input['total'], 2));
        $input['discount'] = removeCommaFromNumbers(number_format($input['discount'], 2));
        $input['tax_amount'] = removeCommaFromNumbers(number_format($input['tax_amount'], 2));
        $input['net_amount'] = removeCommaFromNumbers(number_format($input['net_amount'], 2));

        $medicineBillController = app(MedicineBillController::class);

        $data = $medicineBillController->store($input);
        if (is_array($data) && array_key_exists('error', $data)) {
            Notification::make()
                ->title($data['error'])
                ->danger()
                ->send();
            $this->halt();
        }
        if (is_array($data) && array_key_exists('payment_mode', $data)) {
            $this->js('razorPay(event'.','.$data['status'].', '.$data['record'].', '.$data['amount'].')');
            $this->halt();
        }
        $appointment = [
            'bill_number' => 1,
        ];

        $appointment = new MedicineBill($appointment);

        return $appointment;

    }

    protected function getRedirectUrl(): string
    {
        if (session()->has('sessionUrl')) {
            $sessionUrl = session()->get('sessionUrl');
            session()->forget('sessionUrl');

            return $sessionUrl;
        } else {
            return static::getResource()::getUrl('index');
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        if (session()->has('paymentError')) {
            session()->forget('paymentError');

            return '';
        } elseif (! session()->has('sessionUrl')) {
            return __('messages.medicine_bills.medicine_bill').' '.__('messages.common.saved_successfully');
        }

        return '';

    }
}
