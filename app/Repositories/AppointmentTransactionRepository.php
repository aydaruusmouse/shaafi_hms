<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\AppointmentTransaction;
use App\Models\Setting;
use App\Models\SuperAdminSetting;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Stripe\Checkout\Session;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AppointmentTransactionRepository extends BaseRepository
{
    protected $fieldSearchable = [];

    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    public function model(): string
    {
        return AppointmentTransaction::class;
    }

    public function store($input)
    {
        try {
            $appointment = $input instanceof Appointment
                ? $input
                : Appointment::find($input['id'] ?? null);

            if (! $appointment) {
                return true;
            }

            $this->recordForAppointment($appointment);

            return $appointment;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function recordForAppointment(Appointment $appointment, ?string $transactionId = null, ?int $transactionType = null): AppointmentTransaction
    {
        $type = $transactionType ?? $appointment->payment_type ?? Appointment::TYPE_CASH;

        $appointment->forceFill([
            'payment_type' => $appointment->payment_type ?: $type,
            'payment_status' => 1,
        ])->save();

        return AppointmentTransaction::firstOrCreate(
            ['appointment_id' => $appointment->id],
            [
                'transaction_type' => $type,
                'transaction_id' => $transactionId ?: $this->makeOfflineTransactionId($appointment, (int) $type),
                'tenant_id' => $appointment->tenant_id ?? getLoggedInUser()?->tenant_id,
            ]
        );
    }

    private function makeOfflineTransactionId(Appointment $appointment, int $type): string
    {
        $prefix = match ($type) {
            Appointment::CHEQUE => 'CHQ',
            Appointment::TYPE_CASH => 'CASH',
            default => 'APT',
        };

        return $prefix.'-'.$appointment->id.'-'.strtoupper(Str::random(8));
    }

    // public function WebAppointmentstripeSession($input)
    // {
    //     $appointment = Appointment::find($input->id);
    //     $data = [
    //         'appointment_id' => $input->id,
    //         'amount' => $input->appointment_charge,
    //         'payment_mode' => $input->payment_type,
    //     ];
    //     $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
    //     $stripeKey = Setting::whereTenantId($tenantId)
    //     ->where('key', '=', 'stripe_secret')
    //     ->first();
    //     if (! empty($stripeKey->value)) {
    //         setStripeApiKey($tenantId);
    //     } else {
    //         return false;
    //     }

    //     $session = Session::create([
    //         'payment_method_types' => ['card'],
    //         'customer_email' => $appointment->patient->patientUser->email,
    //         'line_items' => [
    //             [
    //                 'price_data' => [
    //                     'product_data' => [
    //                         'name' => 'Payment for Patient bill',
    //                     ],
    //                     'unit_amount' =>  $input->appointment_charge * 100,
    //                     'currency' => getCurrentCurrency(),
    //                 ],
    //                 'quantity' => 1,
    //             ],
    //         ],
    //         'client_reference_id' => $input->id,
    //         'mode' => 'payment',
    //         'success_url' => route('web.appointment.stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
    //         'metadata' => $data,
    //     ]);

    //     $result = [
    //         'sessionId' => $session['id'],
    //     ];

    //     return $result;
    // }

    public function appointmentStripePaymentSuccess($sessionId)
    {
        $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
        setStripeApiKey($tenantId);

        $sessionData = \Stripe\Checkout\Session::retrieve($sessionId['session_id']);

        try {
            DB::beginTransaction();

            $appointmentTransaction = AppointmentTransaction::create([
                'transaction_id' => $sessionData->id,
                'appointment_id' => $sessionData->metadata->appointment_id,
                'transaction_type' => $sessionData->metadata->payment_mode,
                'amount' => $sessionData->metadata->amount,
                'tenant_id' => getLoggedInUser()->tenant_id,
            ]);

            // update appoitment payment Status
            $appointment = Appointment::find($sessionData->metadata->appointment_id);
            $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::TYPE_STRIPE]);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function TransactionRazorpayPayment($input)
    {

        // $amount = $input['appointment_charge'];
        $amount = intval(str_replace(',', '', $input['appointment_charge']));

        $api = new Api(getSelectedPaymentGateway('razorpay_key'), getSelectedPaymentGateway('razorpay_secret'));

        $orderData = [
            'receipt' => '1',
            'amount' => $amount * 100, // 100 = 1 rupees
            'currency' => strtoupper(getCurrentCurrency()),
            'notes' => [
                'appointment_id' => $input['appointment_id'],
                'amount' => $amount,
                'payment_type' => $input['payment_type'],
            ],
        ];

        $razorpayOrder = $api->order->create($orderData);

        $data['id'] = $razorpayOrder->id;
        $data['amount'] = $amount;
        $data['payment_type'] = $input['payment_type'];
        $data['appointment_id'] = $input['appointment_id'];

        return $data;
    }

    public function WebTransactionRazorpayPaymentSuccess($input)
    {
        // dd($input);
        Log::info('RazorPay Payment Successfully');
        $api = new Api(getSelectedPaymentGateway('razorpay_key'), getSelectedPaymentGateway('razorpay_secret'));

        if (count($input) && ! empty($input['razorpay_payment_id'])) {
            try {
                DB::beginTransaction();

                $payment = $api->payment->fetch($input['razorpay_payment_id']);
                $generatedSignature = hash_hmac('sha256', $payment['order_id'].'|'.$input['razorpay_payment_id'], getSelectedPaymentGateway('razorpay_secret'));

                if ($generatedSignature != $input['razorpay_signature']) {
                    return redirect()->back();
                }

                // Create Transaction Here
                $appointmentTransaction = AppointmentTransaction::create([
                    'transaction_id' => $payment['id'],
                    'appointment_id' => $payment['notes']['appointment_id'],
                    'payment_type' => $payment['notes']['payment_type'],
                    'amount' => $payment['notes']['amount'],
                    'tenant_id' => getLoggedInUser()->tenant_id,
                    'transaction_type' => $payment['notes']['payment_type'],
                ]);

                $appointment = Appointment::find($payment['notes']['appointment_id']);
                $appointment->update(['is_completed' => 0, 'payment_status' => 1, 'payment_type' => \App\Models\Appointment::TYPE_RAZORPAY]);

                DB::commit();

                return true;
            } catch (Exception $e) {
                DB::rollBack();
                throw new UnprocessableEntityHttpException($e->getMessage());
            }

            return false;
        }
    }

    public function TransactionRazorpayPaymentSuccess($input)
    {
        Log::info('RazorPay Payment Successfully');
        $api = new Api(getSelectedPaymentGateway('razorpay_key'), getSelectedPaymentGateway('razorpay_secret'));

        if (count($input) && ! empty($input['razorpay_payment_id'])) {
            try {
                DB::beginTransaction();

                $payment = $api->payment->fetch($input['razorpay_payment_id']);
                $sessionData = session()->get('appointmentPaymentData');

                // Create Transaction Here
                $appointmentTransaction = AppointmentTransaction::create([
                    'transaction_id' => $payment['id'],
                    'appointment_id' => $payment['notes']['appointment_id'],
                    'transaction_type' => $sessionData['payment_type'],
                    'amount' => $sessionData['amount'],
                    'tenant_id' => getLoggedInUser()->tenant_id,
                ]);

                $appointment = Appointment::find($payment['notes']['appointment_id']);
                $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::TYPE_RAZORPAY]);

                DB::commit();
                session()->forget('appointmentPaymentData');

                return true;
            } catch (Exception $e) {
                DB::rollBack();
                session()->forget('appointmentPaymentData');
                throw new UnprocessableEntityHttpException($e->getMessage());
            }

            return false;
        }
    }

    public function paypalPaymentSuccess($response)
    {
        try {
            DB::beginTransaction();

            $transactionID = $response['purchase_units'][0]['payments']['captures'][0]['id'];
            $appointmentId = $response['purchase_units'][0]['reference_id'];

            $transactionData = [
                'transaction_id' => $transactionID,
                'appointment_id' => $appointmentId,
                'transaction_type' => Appointment::TYPE_PAYPAL,
                'tenant_id' => getLoggedInUser()->tenant_id,
            ];

            $transaction = AppointmentTransaction::create($transactionData);

            $appointment = Appointment::find($appointmentId);
            $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::TYPE_PAYPAL]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function appointmentFlutterWavePayment($input)
    {
        $amount = $input['appointment_charge'];
        $appointmentId = $input['appointment_id'];
        $appointment = Appointment::find($appointmentId);

        $reference = time();

        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $amount,
            'email' => $appointment->patient->patientUser->email,
            'tx_ref' => $reference,
            'currency' => strtoupper(getCurrentCurrency()),
            'redirect_url' => route('appointment.flutterwave.success'),
            'customer' => [
                'email' => $appointment->patient->patientUser->email,
            ],
            'customizations' => [
                'title' => 'Purchase Medicine Payment',
            ],
            'meta' => [
                'appointment_id' => $appointmentId,
                'amount' => $amount,
                'payment_mode' => $input['payment_type'],
            ],
        ];

        return $this->createFlutterwavePaymentLink($data);
    }

    private function createFlutterwavePaymentLink($data)
    {
        $client = new Client;
        $url = 'https://api.flutterwave.com/v3/payments';
        $clientId = SuperAdminSetting::where('key', 'flutterwave_secret')->first()->value;

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$clientId,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        $body = json_decode($response->getBody(), true);
        if ($body['status'] == 'success') {
            session()->put('sessionUrl', $body['data']['link']);

            return $body['data']['link'];
        }

        return back()->with('error', 'Error initiating payment.');
    }

    public function flutterWaveSuccess($input)
    {
        try {
            DB::beginTransaction();

            if ($input['status'] == 'successful') {

                $transactionID = $input['transaction_id'];
                $flutterWaveData = $this->verifyPayment($transactionID);

                $data = $flutterWaveData['data']['meta'];

                $appointmentTransaction = AppointmentTransaction::create([
                    'transaction_id' => $input['transaction_id'],
                    'appointment_id' => $data['appointment_id'],
                    'transaction_type' => $data['payment_mode'],
                    'amount' => $data['amount'],
                    'tenant_id' => getLoggedInUser()->tenant_id,
                ]);

                $appointment = Appointment::find($data['appointment_id']);
                $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::FLUTTERWAVE]);

                DB::commit();

                return true;
            }
        } catch (Exception $e) {
            DB::rollback();
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function verifyPayment($transactionID)
    {
        $client = new Client;
        $url = "https://api.flutterwave.com/v3/transactions/{$transactionID}/verify";
        $clientId = SuperAdminSetting::where('key', 'flutterwave_secret')->first()->value;

        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$clientId,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function webAppointmentFlutterWavePayment($input)
    {
        $amount = $input['amount'];
        $appointmentId = $input['appointment_id'];
        $appointment = Appointment::find($appointmentId);

        $reference = time();

        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $amount,
            'email' => $appointment->patient->patientUser->email,
            'tx_ref' => $reference,
            'currency' => getCurrentCurrency(),
            'redirect_url' => route('web.appointment.flutterwave.success').'?'.http_build_query(['appointmentId' => $appointmentId]),
            'customer' => [
                'email' => $appointment->patient->patientUser->email,
            ],
            'customizations' => [
                'title' => 'Purchase Medicine Payment',
            ],
            'meta' => [
                'appointment_id' => $appointmentId,
                'amount' => $amount,
                'payment_mode' => $input['payment_type'],
            ],
        ];

        return $this->createFlutterwavePaymentLink($data);
    }

    public function payStackSuccess($input)
    {
        try {
            DB::beginTransaction();

            $sessionAppointmentData = session()->get('appointmentPayStackData');
            $appointment = Appointment::create($sessionAppointmentData);

            $appointmentTransaction = AppointmentTransaction::create([
                'transaction_id' => $input['data']['id'],
                'appointment_id' => $appointment->id,
                'transaction_type' => $appointment->payment_type,
                'amount' => ($input['data']['amount'] / 100),
                'tenant_id' => getLoggedInUser()->tenant_id,
            ]);

            // update appoitment payment Status
            $appointment = Appointment::find($appointment->id);
            $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::PAYSTACK]);

            DB::commit();
            session()->forget('appointmentPayStackData');

            return true;
        } catch (Exception $e) {
            DB::rollback();
            $appointment = Appointment::orderBy('created_at', 'desc')->latest()->first();
            $appointment->delete();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function phonePePayment($input)
    {
        $amount = $input['appointment_charge'];

        $redirectbackurl = route('appointment.phonepe.callback').'?'.http_build_query(['input' => $input]);

        $merchantId = getPaymentCredentials('phonepe_merchant_id');
        $merchantUserId = getPaymentCredentials('phonepe_merchant_id');
        $merchantTransactionId = getPaymentCredentials('phonepe_merchant_transaction_id');
        $baseUrl = getPaymentCredentials('phonepe_env') == 'production' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        $saltKey = getPaymentCredentials('phonepe_salt_key');
        $saltIndex = getPaymentCredentials('phonepe_salt_index');
        $callbackurl = route('appointment.phonepe.callback').'?'.http_build_query(['input' => $input]);

        config([
            'phonepe.merchantId' => $merchantId,
            'phonepe.merchantUserId' => $merchantUserId,
            'phonepe.env' => $baseUrl,
            'phonepe.saltKey' => $saltKey,
            'phonepe.saltIndex' => $saltIndex,
            'phonepe.redirectUrl' => $redirectbackurl,
            'phonepe.callBackUrl' => $callbackurl,
        ]);

        $data = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => $merchantUserId,
            'amount' => $amount * 100,
            'redirectUrl' => $redirectbackurl,
            'redirectMode' => 'POST',
            'callbackUrl' => $callbackurl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        try {

            $encode = base64_encode(json_encode($data));

            $string = $encode.'/pg/v1/pay'.$saltKey;
            $sha256 = hash('sha256', $string);
            $finalXHeader = $sha256.'###'.$saltIndex;

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $baseUrl.'/pg/v1/pay',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(['request' => $encode]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-VERIFY: '.$finalXHeader,
                ],
            ]);

            $response = curl_exec($curl);

            if (curl_errno($curl)) {
                throw new Exception('Curl error: '.curl_error($curl));
            }

            curl_close($curl);

            $rData = json_decode($response, true);

            if (isset($rData['data']['instrumentResponse']['redirectInfo']['url'])) {
                $url = $rData['data']['instrumentResponse']['redirectInfo']['url'];
                session()->put(['sessionUrl' => $url]);

                return $url;
            } else {
                throw new Exception('Invalid response from PhonePe');
            }
        } catch (Exception $e) {
            session(['paymentError' => 'error']);

            return Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function phonePePaymentSuccess($input)
    {
        try {
            DB::beginTransaction();
            $data = $input['input'];

            $appointment = Appointment::create($data);

            $appointmentTransaction = AppointmentTransaction::create([
                'transaction_id' => $input['transactionId'],
                'appointment_id' => $appointment->id,
                'transaction_type' => $data['payment_type'],
                'amount' => $input['amount'],
                'tenant_id' => getLoggedInUser()->tenant_id,
            ]);

            $appointment = Appointment::find($appointment->id);
            $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::PHONEPE]);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function webPhonePePaymentSuccess($input)
    {

        try {
            DB::beginTransaction();
            $data = $input['input'];

            $appointmentTransaction = AppointmentTransaction::create([
                'transaction_id' => $input['transactionId'],
                'appointment_id' => $data['data']['appointment_id'],
                'transaction_type' => $data['payment_type'],
                'amount' => $input['amount'],
                'tenant_id' => getLoggedInUser()->tenant_id,
            ]);

            $appointment = Appointment::find($data['data']['appointment_id']);
            $appointment->update(['payment_status' => 1, 'payment_type' => \App\Models\Appointment::PHONEPE]);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollback();
            Appointment::destroy($input['data']['appointment_id']);
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function webPhonePePayment($input)
    {
        $amount = $input['amount'];

        $redirectbackurl = route('web.appointment.phonepe.callback').'?'.http_build_query(['input' => $input]);

        $merchantId = getPaymentCredentials('phonepe_merchant_id');
        $merchantUserId = getPaymentCredentials('phonepe_merchant_id');
        $merchantTransactionId = getPaymentCredentials('phonepe_merchant_transaction_id');
        $baseUrl = getPaymentCredentials('phonepe_env') == 'production' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        $saltKey = getPaymentCredentials('phonepe_salt_key');
        $saltIndex = getPaymentCredentials('phonepe_salt_index');
        $callbackurl = route('web.appointment.phonepe.callback').'?'.http_build_query(['input' => $input]);

        config([
            'phonepe.merchantId' => $merchantId,
            'phonepe.merchantUserId' => $merchantUserId,
            'phonepe.env' => $baseUrl,
            'phonepe.saltKey' => $saltKey,
            'phonepe.saltIndex' => $saltIndex,
            'phonepe.redirectUrl' => $redirectbackurl,
            'phonepe.callBackUrl' => $callbackurl,
        ]);

        $data = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => $merchantUserId,
            'amount' => $amount * 100,
            'redirectUrl' => $redirectbackurl,
            'redirectMode' => 'POST',
            'callbackUrl' => $callbackurl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];
        try {

            $encode = base64_encode(json_encode($data));

            $string = $encode.'/pg/v1/pay'.$saltKey;
            $sha256 = hash('sha256', $string);
            $finalXHeader = $sha256.'###'.$saltIndex;

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $baseUrl.'/pg/v1/pay',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(['request' => $encode]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-VERIFY: '.$finalXHeader,
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);

            $rData = json_decode($response);
            $url = $rData->data->instrumentResponse->redirectInfo->url;

            return $url;

        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
