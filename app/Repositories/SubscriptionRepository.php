<?php

namespace App\Repositories;

use App\Mail\NotifyMailSuperAdminForSubscribeHospital;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use Auth;
use Carbon\Carbon;
use Exception;
use Filament\Notifications\Notification;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mail;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class SubscriptionRepository
 */
class SubscriptionRepository extends BaseRepository
{
    protected $fieldSearchable = [
        'user_id',
        'stripe_id',
        'stripe_status',
        'stripe_plan',
        'subscription_plan_id',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * {@inheritDoc}
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * {@inheritDoc}
     */
    public function model()
    {
        return Subscription::class;
    }

    /**
     * @throws ApiErrorException
     */
    public function purchaseSubscriptionForStripe($subscriptionPlanId)
    {
        $data = $this->manageSubscription($subscriptionPlanId);
        if (! isset($data['plan'])) {
            return $data;
        }
        $result = $this->manageStripeData(
            $data['plan'],
            ['amountToPay' => $data['amountToPay'], 'sub_id' => $data['subscription']->id]
        );

        return $result;
    }

    public function manageSubscription(int $subscriptionPlanId, $isPaytm = null)
    {
        /** @var SubscriptionPlan $subscriptionPlan */
        $subscriptionPlan = SubscriptionPlan::findOrFail($subscriptionPlanId);
        $newPlanDays = $subscriptionPlan->frequency == SubscriptionPlan::MONTH ? 30 : 365;

        $startsAt = Carbon::now();
        $endsAt = $startsAt->copy()->addDays($newPlanDays);

        $usedTrialBefore = Subscription::whereUserId(Auth::id())->whereNotNull('trial_ends_at')->exists();

        // if the user did not have any trial plan then give them a trial
        if (! $usedTrialBefore && $subscriptionPlan->trial_days > 0) {
            $endsAt = $startsAt->copy()->addDays($subscriptionPlan->trial_days);
        }

        $amountToPay = $subscriptionPlan->price;

        /** @var Subscription $currentSubscription */
        $currentSubscription = currentActiveSubscription();

        $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);
        $planIsInTrial = checkIfPlanIsInTrial($currentSubscription);
        // switching the plan -- Manage the pro-rating
        if (! $currentSubscription->isExpired() && $amountToPay != 0 && ! $planIsInTrial) {
            $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);

            $currentPlan = $currentSubscription->subscriptionPlan; // TODO: take fields from subscription

            // checking if the current active subscription plan has the same price and frequency in order to process the calculation for the proration
            $planPrice = $currentPlan->price;
            $planFrequency = $currentPlan->frequency;
            if ($planPrice != $currentSubscription->plan_amount || $planFrequency != $currentSubscription->plan_frequency) {
                $planPrice = $currentSubscription->plan_amount;
                $planFrequency = $currentSubscription->plan_frequency;
            }

            $frequencyDays = $planFrequency == SubscriptionPlan::MONTH ? 30 : 365;
            $perDayPrice = round($planPrice / $frequencyDays, 2);

            $remainingBalance = $planPrice - ($perDayPrice * $usedDays);

            if ($remainingBalance < $subscriptionPlan->price) { // adjust the amount in plan i.e. you have to pay for it
                $amountToPay = round($subscriptionPlan->price - $remainingBalance, 2);
            } else {
                $perDayPriceOfNewPlan = round($subscriptionPlan->price / $newPlanDays, 2);

                $totalDays = round($remainingBalance / $perDayPriceOfNewPlan);
                $endsAt = Carbon::now()->addDays($totalDays);
                $amountToPay = 0;
            }
        }

        if ($isPaytm == 1) {
            return $amountToPay;
        }

        // check that if try to switch the plan
        if (! $currentSubscription->isExpired()) {
            if ((checkIfPlanIsInTrial($currentSubscription) || ! checkIfPlanIsInTrial($currentSubscription)) && $subscriptionPlan->price <= 0) {
                return ['status' => false, 'subscriptionPlan' => $subscriptionPlan];
            }
        }

        if ($usedDays <= 0) {
            $startsAt = $currentSubscription->starts_at;
        }

        $input = [
            'user_id' => getLoggedInUser()->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'plan_amount' => $subscriptionPlan->price,
            'plan_frequency' => $subscriptionPlan->frequency,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => Subscription::INACTIVE,
            'sms_limit' => $subscriptionPlan->sms_limit,
        ];

        $subscription = Subscription::create($input);

        if ($subscriptionPlan->price <= 0 || $amountToPay == 0) {
            // De-Active all other subscription
            Subscription::whereUserId(getLoggedInUserId())
                ->where('id', '!=', $subscription->id)
                ->update([
                    'status' => Subscription::INACTIVE,
                ]);
            Subscription::findOrFail($subscription->id)->update(['status' => Subscription::ACTIVE]);

            return ['status' => true, 'subscriptionPlan' => $subscriptionPlan];
        }

        session(['subscription_plan_id' => $subscription->id]);
        if ($isPaytm == null) {
            session(['from_pricing' => request()->get('from_pricing')]);
        }

        return [
            'plan' => $subscriptionPlan,
            'amountToPay' => $amountToPay,
            'subscription' => $subscription,
        ];
    }

    /**
     * @throws ApiErrorException
     */
    public function manageStripeData($subscriptionPlan, $data)
    {
        $amountToPay = $data['amountToPay'];
        $subscriptionID = $data['sub_id'];

        if ($subscriptionPlan->currency != null && in_array(
            Str::upper(getSubscriptionPlanCurrencyCode($subscriptionPlan->currency)),
            zeroDecimalCurrencies()
        )) {
            $planAmount = $amountToPay;
        } else {
            $planAmount = $amountToPay * 100;
        }

        setSuperAdminStripeApiKey();
        // dd(Auth::user()->email,$subscriptionPlan->name,$planAmount,getSubscriptionPlanCurrencyCode($subscriptionPlan->currency),$subscriptionID,$planAmount,$subscriptionPlan->currency);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => Auth::user()->email,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => getSubscriptionPlanCurrencyCode($subscriptionPlan->currency),
                        'unit_amount' => $planAmount,
                        'product_data' => [
                            'name' => $subscriptionPlan->name,
                            'description' => 'Subscribing for the plan named '.$subscriptionPlan->name,
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'client_reference_id' => $subscriptionID,
            'metadata' => [
                'payment_type' => request()->get('payment_type'),
                'amount' => $planAmount,
                'plan_currency' => $subscriptionPlan->currency,
            ],
            'mode' => 'payment',
            'success_url' => url('payment-success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('failed-payment?error=payment_cancelled'),
        ]);

        $result = [
            'sessionId' => $session['id'],
        ];

        return $result;
    }

    /**
     * @throws ApiErrorException
     */
    public function paymentUpdate($request)
    {
        try {
            setSuperAdminStripeApiKey();
            // Current User Subscription

            // New Plan Subscribe
            $stripe = new \Stripe\StripeClient(
                getSuperAdminStripeApiKey()
            );
            $sessionData = $stripe->checkout->sessions->retrieve(
                $request->session_id,
                []
            );

            // where, $sessionData->client_reference_id = the subscription id
            Subscription::findOrFail($sessionData->client_reference_id)->update(['status' => Subscription::ACTIVE]);
            // De-Active all other subscription
            Subscription::whereUserId(getLoggedInUserId())
                ->where('id', '!=', $sessionData->client_reference_id)
                ->update([
                    'status' => Subscription::INACTIVE,
                ]);

            $paymentAmount = null;
            if ($sessionData->metadata->plan_currency != null && in_array(
                getSubscriptionPlanCurrencyCode($sessionData->metadata->plan_currency),
                zeroDecimalCurrencies()
            )) {
                $paymentAmount = $sessionData->amount_total;
            } else {
                $paymentAmount = $sessionData->amount_total / 100;
            }

            $transaction = Transaction::create([
                'transaction_id' => $request->session_id,
                'payment_type' => $sessionData->metadata->payment_type,
                'amount' => $paymentAmount,
                'user_id' => getLoggedInUserId(),
                'status' => Subscription::ACTIVE,
                'meta' => json_encode($sessionData),
            ]);

            $subscription = Subscription::findOrFail($sessionData->client_reference_id);
            $subscription->update(['transaction_id' => $transaction->id]);

            $mailData = [
                'amount' => $paymentAmount,
                'user_name' => getLoggedInUser()->full_name,
                'plan_name' => $subscription->subscriptionPlan->name,
                'start_date' => $subscription->starts_at,
                'end_date' => $subscription->ends_at,
            ];

            Mail::to(getLoggedInUser()->email)
                ->send(new NotifyMailSuperAdminForSubscribeHospital(
                    'emails.hospital_subscription_mail',
                    __('messages.new_change.subscription_mail'),
                    $mailData
                ));

            DB::commit();
            $subscription->load('subscriptionPlan');

            return $subscription;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function paymentFailed($subscriptionPlanId)
    {
        $subscriptionPlan = Subscription::findOrFail($subscriptionPlanId);
        $subscriptionPlan->delete();
    }

    public function manageCashSubscription(int $subscriptionPlanId): array
    {
        /** @var SubscriptionPlan $subscriptionPlan */
        $subscriptionPlan = SubscriptionPlan::findOrFail($subscriptionPlanId);
        $newPlanDays = $subscriptionPlan->frequency == SubscriptionPlan::MONTH ? 30 : 365;

        $startsAt = Carbon::now();
        $endsAt = $startsAt->copy()->addDays($newPlanDays);

        $usedTrialBefore = Subscription::whereUserId(Auth::id())->whereNotNull('trial_ends_at')->exists();

        // if the user did not have any trial plan then give them a trial
        if (! $usedTrialBefore && $subscriptionPlan->trial_days > 0) {
            $endsAt = $startsAt->copy()->addDays($subscriptionPlan->trial_days);
        }

        $amountToPay = $subscriptionPlan->price;

        /** @var Subscription $currentSubscription */
        $currentSubscription = currentActiveSubscription();

        $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);
        $planIsInTrial = checkIfPlanIsInTrial($currentSubscription);
        // switching the plan -- Manage the pro-rating
        if (! $currentSubscription->isExpired() && $amountToPay != 0 && ! $planIsInTrial) {
            $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);

            $currentPlan = $currentSubscription->subscriptionPlan; // TODO: take fields from subscription

            // checking if the current active subscription plan has the same price and frequency in order to process the calculation for the proration
            $planPrice = $currentPlan->price;
            $planFrequency = $currentPlan->frequency;
            if ($planPrice != $currentSubscription->plan_amount || $planFrequency != $currentSubscription->plan_frequency) {
                $planPrice = $currentSubscription->plan_amount;
                $planFrequency = $currentSubscription->plan_frequency;
            }

            $frequencyDays = $planFrequency == SubscriptionPlan::MONTH ? 30 : 365;
            $perDayPrice = round($planPrice / $frequencyDays, 2);

            $remainingBalance = $planPrice - ($perDayPrice * $usedDays);

            if ($remainingBalance < $subscriptionPlan->price) { // adjust the amount in plan i.e. you have to pay for it
                $amountToPay = round($subscriptionPlan->price - $remainingBalance, 2);
            } else {
                $perDayPriceOfNewPlan = round($subscriptionPlan->price / $newPlanDays, 2);

                $totalDays = round($remainingBalance / $perDayPriceOfNewPlan);
                $endsAt = Carbon::now()->addDays($totalDays);
                $amountToPay = 0;
            }
        }

        // check that if try to switch the plan
        if (! $currentSubscription->isExpired()) {
            if ((checkIfPlanIsInTrial($currentSubscription) || ! checkIfPlanIsInTrial($currentSubscription)) && $subscriptionPlan->price <= 0) {
                return ['status' => false, 'subscriptionPlan' => $subscriptionPlan];
            }
        }

        if ($usedDays <= 0) {
            $startsAt = $currentSubscription->starts_at;
        }

        $input = [
            'user_id' => getLoggedInUser()->id,
            'subscription_plan_id' => $subscriptionPlan->id,
            'plan_amount' => $subscriptionPlan->price,
            'plan_frequency' => $subscriptionPlan->frequency,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => Subscription::INACTIVE,
            'sms_limit' => $subscriptionPlan->sms_limit,
        ];

        $subscription = Subscription::create($input);

        if ($subscriptionPlan->price <= 0 || $amountToPay == 0) {
            // De-Active all other subscription
            Subscription::whereUserId(getLoggedInUserId())
                ->where('id', '!=', $subscription->id)
                ->update([
                    'status' => Subscription::INACTIVE,
                ]);
            Subscription::findOrFail($subscription->id)->update(['status' => Subscription::ACTIVE]);

            return ['status' => true, 'subscriptionPlan' => $subscriptionPlan];
        }

        return [
            'plan' => $subscriptionPlan,
            'amountToPay' => $amountToPay,
            'subscription' => $subscription,
        ];
    }

    public function phonePePayment($input)
    {
        $subscriptionData = $this->manageSubscription($input['id']);
        $amountToPay = $subscriptionData['amountToPay'];
        $input['subscription_id'] = $subscriptionData['subscription']->id;
        $meta = ['subscription_id' => $input['subscription_id'], 'planId' => $input['id'], 'user_id' => getLoggedInUserId()];

        $redirectbackurl = route('subscription.phonepe.callback').'?'.http_build_query(['input' => $meta]);

        $merchantId = getSuperAdminPaymentCredentials('phonepe_merchant_id');
        $merchantUserId = getSuperAdminPaymentCredentials('phonepe_merchant_id');
        $merchantTransactionId = getSuperAdminPaymentCredentials('phonepe_merchant_transaction_id');
        $baseUrl = getSuperAdminPaymentCredentials('phonepe_env') == 'production' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        $saltKey = getSuperAdminPaymentCredentials('phonepe_salt_key');
        $saltIndex = getSuperAdminPaymentCredentials('phonepe_salt_index');
        $callbackurl = route('subscription.phonepe.callback').'?'.http_build_query(['input' => $meta]);

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
            'amount' => $amountToPay * 100,
            'redirectUrl' => $redirectbackurl,
            'redirectMode' => 'POST',
            'callbackUrl' => $callbackurl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

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
        if (empty($url)) {
            Notification::make()
                ->title(__('messages.payment.payment_failed'))
                ->danger()
                ->send();

            return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
        }

        return $url;
    }

    public function flutterWavePayment($input)
    {
        $data = $this->manageSubscription($input['plan_id']);

        if (! isset($data['plan'])) {
            return $data;
        }

        $subscriptionId = $data['subscription']->id;

        $flutterWaveData = [
            'payment_options' => 'card,banktransfer',
            'amount' => $data['amountToPay'],
            'email' => getLoggedInUser()->email,
            'tx_ref' => time(),
            'currency' => Str::upper(getCurrentCurrency()),
            'redirect_url' => route('purchase.subscription.flutterwave.success').'?'.http_build_query(['subscriptionId' => $subscriptionId]),
            'customer' => [
                'email' => getLoggedInUser()->email,
            ],
            'customizations' => [
                'title' => 'Purchase subscription Payment',
            ],
            'meta' => [
                'subscription_id' => $data['subscription']->id,
                'plan_currency' => Str::upper($data['plan']->currency),
            ],
        ];

        // dd($flutterWaveData);
        $paymentURL = $this->createFlutterwavePaymentLink($flutterWaveData);

        return $paymentURL;
    }

    private function createFlutterwavePaymentLink($data)
    {
        $client = new Client;
        $url = 'https://api.flutterwave.com/v3/payments';

        // Retrieve secret key directly from the passed variable
        $flutterwaveSecretKey = getSuperAdminPaymentCredentials('flutterwave_secret');

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$flutterwaveSecretKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        $body = json_decode($response->getBody(), true);

        if ($body['status'] == 'success') {
            return $body['data']['link'];
        }

        return null;
    }

    public function phonePePaymentSuccess($input)
    {
        try {
            DB::beginTransaction();
            $transactionID = $input['transactionId'];
            $data = $input['input'];

            Subscription::findOrFail($data['subscription_id'])->update(['status' => Subscription::ACTIVE]);
            Subscription::whereUserId(getLoggedInUserId())
                ->where('id', '!=', $data['subscription_id'])
                ->update([
                    'status' => Subscription::INACTIVE,
                ]);

            $paymentAmount = ($input['amount'] / 100);

            $transaction = Transaction::create([
                'transaction_id' => $input['transactionId'],
                'payment_type' => 7,
                'amount' => $paymentAmount,
                'user_id' => getLoggedInUserId() ?? $input['user_id'],
                'status' => Subscription::ACTIVE,
                'meta' => json_encode(Arr::except($data, ['user_id'])),
            ]);

            $subscription = Subscription::findOrFail($data['subscription_id']);
            $subscription->update(['transaction_id' => $transaction->id]);

            $mailData = [
                'amount' => $paymentAmount,
                'user_name' => getLoggedInUser()->full_name,
                'plan_name' => $subscription->subscriptionPlan->name,
                'start_date' => $subscription->starts_at,
                'end_date' => $subscription->ends_at,
            ];

            Mail::to(getLoggedInUser()->email)
                ->send(new NotifyMailSuperAdminForSubscribeHospital('emails.hospital_subscription_mail', __('messages.new_change.subscription_mail'), $mailData));
            DB::commit();
            $subscription->load('subscriptionPlan');

            return $subscription;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }

        return false;
    }

    public function flutterWaveSuccess($request)
    {
        try {
            DB::beginTransaction();

            $transactionID = $request['transaction_id'];
            $verificationResponse = $this->verifyPayment($transactionID);

            if ($verificationResponse['status'] == 'success') {

                $data = $verificationResponse['data']['meta'];
                $paymentAmount = $verificationResponse['data']['amount'];
                Subscription::findOrFail($data['subscription_id'])->update(['status' => Subscription::ACTIVE]);
                Subscription::whereUserId(getLoggedInUserId())
                    ->where('id', '!=', $data['subscription_id'])
                    ->update([
                        'status' => Subscription::INACTIVE,
                    ]);

                // Create a transaction record
                $transaction = Transaction::create([
                    'transaction_id' => $transactionID,
                    'payment_type' => 8, // Example payment type
                    'amount' => $paymentAmount,
                    'user_id' => getLoggedInUserId(),
                    'status' => Subscription::ACTIVE,
                    'meta' => json_encode($verificationResponse),
                ]);

                $subscription = Subscription::findOrFail($data['subscription_id']);
                $subscription->update(['transaction_id' => $transaction->id]);

                // Send email notification
                $mailData = [
                    'amount' => $paymentAmount,
                    'user_name' => getLoggedInUser()->full_name,
                    'plan_name' => $subscription->subscriptionPlan->name,
                    'start_date' => $subscription->starts_at,
                    'end_date' => $subscription->ends_at,
                ];

                Mail::to(getLoggedInUser()->email)
                    ->send(new NotifyMailSuperAdminForSubscribeHospital('emails.hospital_subscription_mail', __('messages.new_change.subscription_mail'), $mailData));

                DB::commit();
                $subscription->load('subscriptionPlan');

                return $subscription;
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return false;
    }

    private function verifyPayment($transactionID)
    {
        $client = new Client;
        $url = "https://api.flutterwave.com/v3/transactions/{$transactionID}/verify";

        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.getSuperAdminPaymentCredentials('flutterwave_secret'),
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function setFlutterWaveConfig()
    {
        $flutterwavePublicKey = getSuperAdminPaymentCredentials('flutterwave_key');
        $flutterwaveSecretKey = getSuperAdminPaymentCredentials('flutterwave_secret');

        if (! $flutterwavePublicKey || ! $flutterwaveSecretKey) {
            return $this->sendError(__('messages.flutterwave.set_flutterwave_credential'));
        }

        config([
            'flutterwave.publicKey' => $flutterwavePublicKey,
            'flutterwave.secretKey' => $flutterwaveSecretKey,
        ]);
    }
}
