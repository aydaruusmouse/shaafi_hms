<?php

namespace App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Billings\Resources\BillResource;
use App\Models\Bill;
use App\Models\BillTransaction;
use App\Models\Setting;
use App\Repositories\BillRepository;
use Filament\Actions;
use Filament\Actions\StaticAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;

class ListBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected $status;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function confirmation(): Actions\Action
    {
        return Actions\Action::make('confirmation')
            ->visible(1)
            ->requiresConfirmation()
            ->modalSubmitAction(function (StaticAction $action) {
                $previousUrl = url()->previous();
                $query = parse_url($previousUrl, PHP_URL_QUERY);
                parse_str($query, $queryParams);
                $status = $queryParams['status'] ?? null;
                $record = $queryParams['record'] ?? null;
                $bill = Bill::whereId($record)->first();

                if ($status == 5) {
                    return $action->extraAttributes(['onclick' => 'razorPay(event, '.$status.', '.$record.', '.$bill->amount.')']);
                }
            })
            ->action(function () {
                $previousUrl = url()->previous();
                $query = parse_url($previousUrl, PHP_URL_QUERY);

                parse_str($query, $queryParams);

                $action = $queryParams['action'] ?? null;
                $status = $queryParams['status'] ?? null;
                $record = $queryParams['record'] ?? null;

                if ($action == 'confirmation' && $record && $status != null) {

                    $bill = Bill::whereId($record)->first();
                    $input['amount'] = removeCommaFromNumbers(number_format($bill['amount'], 2));
                    if ($status == BillTransaction::TYPE_STRIPE) {
                        $stripeSecretKey = Setting::whereTenantId($bill->tenant_id)
                            ->where('key', '=', 'stripe_secret')
                            ->first();
                        $stripeKey = Setting::whereTenantId($bill->tenant_id)
                            ->where('key', '=', 'stripe_key')
                            ->first();
                        if (! empty($stripeSecretKey->value) || ! empty($stripeKey->value)) {
                            setStripeApiKey($bill->tenant_id);
                            $stripeKey = $stripeKey->value;
                        } else {
                            return Notification::make()
                                ->title(__('messages.new_change.provide_stripe_key'))
                                ->warning()
                                ->send();
                        }

                        $session = Session::create([
                            'payment_method_types' => ['card'],
                            'customer_email' => $bill->patient->patientUser->email,
                            'line_items' => [
                                [
                                    'price_data' => [
                                        'product_data' => [
                                            'name' => 'Payment for Patient bill',
                                        ],
                                        'unit_amount' => in_array(getCurrentCurrency(), zeroDecimalCurrencies()) ? $bill->amount : $bill->amount * 100,
                                        'currency' => getCurrentCurrency(),
                                    ],
                                    'quantity' => 1,
                                ],
                            ],
                            'client_reference_id' => $bill->id,
                            'mode' => 'payment',
                            'success_url' => route('bill.stripe.payment.success').'?session_id={CHECKOUT_SESSION_ID}',
                        ]);

                        $result = [
                            'sessionId' => $session['id'],
                        ];

                        if (empty($session) || empty($session->url)) {
                            return Notification::make()
                                ->title(__('messages.flash.failed_to_redirect'))
                                ->warning()
                                ->send();
                        }

                        return redirect($session->url);
                    } elseif ($status == BillTransaction::TYPE_CASH) {
                        $bill->update(['status' => Bill::PENDING]);
                        BillTransaction::create([
                            'transaction_id' => '',
                            'payment_type' => BillTransaction::TYPE_CASH,
                            'amount' => $input['amount'],
                            'bill_id' => $record,
                            'status' => BillTransaction::UNPAID,
                        ]);

                        Notification::make()
                            ->title(__('messages.lunch_break.payment_request_send'))
                            ->success()
                            ->send();
                    } elseif ($status == BillTransaction::PHONEPE) {
                        $currency = ['INR'];
                        if (! in_array(strtoupper(getCurrentCurrency()), $currency)) {
                            return Notification::make()
                                ->title(__('messages.phonepe.currency_allowed'))
                                ->warning()
                                ->send();
                        }

                        $result = app(BillRepository::class)->phonePePayment(['id' => $record] + ['paymentType' => $status] + $input);

                        return $result;
                    } elseif ($status == BillTransaction::FLUTTERWAVE) {
                        if (! in_array(strtoupper(getCurrentCurrency()), ['GBP', 'CAD', 'XAF', 'CLP', 'COP', 'EGP', 'EUR', 'GHS', 'GNF', 'KES', 'MWK', 'MAD', 'NGN', 'RWF', 'SLL', 'STD', 'ZAR', 'TZS', 'UGX', 'USD', 'XOF', 'ZMW'])) {
                            return Notification::make()
                                ->title(__('messages.flutterwave.currency_allowed'))
                                ->warning()
                                ->send();
                        }

                        $flutterwavePublicKey = getPaymentCredentials('flutterwave_public_key');
                        $flutterwaveSecretKey = getPaymentCredentials('flutterwave_secret_key');

                        if (! $flutterwavePublicKey && ! $flutterwaveSecretKey) {
                            return Notification::make()
                                ->title(__('messages.flutterwave.set_flutterwave_credential'))
                                ->warning()
                                ->send();
                        }

                        config([
                            'flutterwave.publicKey' => $flutterwavePublicKey,
                            'flutterwave.secretKey' => $flutterwaveSecretKey,
                        ]);

                        $result = app(BillRepository::class)->flutterWavePayment(['id' => $record] + ['paymentType' => $status] + $input);

                        return $result;
                    } elseif ($status == BillTransaction::PAYSTACK) {
                        $request = new Request($input + ['id' => $record, 'paymentType' => $status]);
                        $result = app(BillRepository::class)->paystackPayment($request);

                        return $result;
                    } elseif ($status == BillTransaction::RAZORPAY) {
                        app(BillRepository::class)->razorpayPayment($input + ['id' => $record, 'paymentType' => $status]);
                    } elseif ($status == BillTransaction::PAYPAL) {
                        app(BillRepository::class)->paypalPayment($input + ['id' => $record, 'paymentType' => $status]);
                    }
                }
            })
            ->modalCancelAction(fn (StaticAction $action) => $action->url(BillResource::getUrl('index')))
            ->color('warning')
            ->modalDescription(__('messages.lunch_break.u_want_to_complete_this_payment'))
            ->modalHeading(__('messages.lunch_break.are_u_sure'));
    }

    public function changePaymentStatus($record, $status)
    {
        $currentUrl = url()->query('hospital/billings/bills?action=confirmation', ['status' => $status, 'record' => $record]);

        return redirect($currentUrl);
    }
}
