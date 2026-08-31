<?php

namespace App\Http\Controllers;

use App\Actions\Subscription\CreateSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FlutterwaveController extends Controller
{
    public function purchase(Request $request)
    {
        $plan = json_decode($request->plan);

        $data = [
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
        ];

        session(['data' => $data]);

        $clientId = getSuperAdminSettingKeyValue('flutterwave_key');
        $clientSecret = getSuperAdminSettingKeyValue('flutterwave_secret');

        config([
            'flutterwave.publicKey' => $clientId,
            'flutterwave.secretKey' => $clientSecret,
        ]);

        $supportedCurrency = ['GBP', 'CAD', 'XAF', 'CLP', 'COP', 'EGP', 'EUR', 'GHS', 'GNF', 'KES', 'MWK', 'MAD', 'NGN', 'RWF', 'SLL', 'STD', 'ZAR', 'TZS', 'UGX', 'USD', 'XOF', 'ZMW'];

        $transactionRef = time();
        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $plan->payable_amount,
            'email' => $request->user()->email,
            'tx_ref' => $transactionRef,
            'currency' => 'USD',
            'redirect_url' => route('flutterwave.subscription.success'),
            'customer' => [
                'email' => Auth()->user()->email,
            ],
            'customizations' => [
                'title' => 'Purchase Subscription Payment',
            ],
            // 'meta' => [
            //     'subscription_id' =>  $subscription->id,
            //     'amount' => $plan->payable_amount * 100,
            //     'payment_mode' => Subscription::TYPE_FLUTTERWAVE,
            // ],
        ];
        $paymentURL = $this->createFlutterwavePaymentLink($data);

        return redirect($paymentURL);
    }

    // Method to create payment link using Guzzle
    private function createFlutterwavePaymentLink($data)
    {
        $clientId = getSuperAdminSettingKeyValue('flutterwave_key');
        $clientSecret = getSuperAdminSettingKeyValue('flutterwave_secret');

        config([
            'flutterwave.publicKey' => $clientId,
            'flutterwave.secretKey' => $clientSecret,
        ]);

        $client = new Client;
        $url = 'https://api.flutterwave.com/v3/payments';

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$clientSecret,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        $body = json_decode($response->getBody(), true);

        if ($body['status'] == 'success') {
            return $body['data']['link'];
        }

        return back()->with('error', 'Error initiating payment.');
    }

    public function success(Request $request)
    {
        $sessionData = session('data');
        $plan = SubscriptionPlan::find($sessionData['plan_id']);

        $clientId = getSuperAdminSettingKeyValue('flutterwave_key');
        $clientSecret = getSuperAdminSettingKeyValue('flutterwave_secret');

        config([
            'flutterwave.publicKey' => $clientId,
            'flutterwave.secretKey' => $clientSecret,
        ]);
        $input = $request->all();
        if ($input['status'] == 'successful') {
            $transactionID = $request->transaction_id;
            $flutterWaveData = $this->verifyPayment($transactionID);
            $data = $flutterWaveData['data'];

            try {
                DB::beginTransaction();
                $transaction = Transaction::create([
                    'transaction_id' => $transactionID,
                    'payment_type' => Transaction::TYPE_FLUTTERWAVE,
                    'amount' => $data['amount'],
                    // 'status' => Transaction::SUCCESS,
                    'status' => Subscription::ACTIVE,
                    'user_id' => $sessionData['user_id'],
                    'meta' => json_encode($flutterWaveData),
                ]);

                $planData['plan'] = $plan->toArray();
                $planData['user_id'] = $sessionData['user_id'];
                $planData['payment_type'] = Transaction::TYPE_FLUTTERWAVE;
                $planData['transaction_id'] = $transaction->id;
                $subscription = CreateSubscription::run($planData);
                DB::commit();

                if ($subscription) {
                    Notification::make()
                        ->success()
                        ->title(__('messages.subscription_pricing_plans.has_been_subscribed'))
                        ->send();

                    setPlanFeatures();

                    return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
                }
            } catch (HttpException $ex) {
                DB::rollBack();
                throw $ex;
            }
        } else {
            Notification::make()
                ->danger()
                ->title(__('messages.payment.payment_failed'))
                ->send();

            return redirect()->route('filament.hospitalAdmin.pages.subscription-plans');
        }
    }

    // Method to verify payment using Guzzle
    private function verifyPayment($transactionID)
    {
        $clientSecret = getSuperAdminSettingKeyValue('flutterwave_secret');
        $client = new Client;
        $url = "https://api.flutterwave.com/v3/transactions/{$transactionID}/verify";

        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$clientSecret,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
