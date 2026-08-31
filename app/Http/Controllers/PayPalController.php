<?php

namespace App\Http\Controllers;

use App\Actions\Subscription\CreateSubscription;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Srmklive\PayPal\Services\PayPal;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaypalController extends Controller
{
    public function purchase(Request $request)
    {

        try {
            $plan = json_decode($request->plan);

            $data = [
                'user_id' => Auth::id(),
                'plan_id' => $plan->id,
            ];

            // if ($plan->currency->code != null && ! in_array(strtoupper($plan->currency->code), self::getPayPalSupportedCurrencies())) {
            //     Notification::make()
            //         ->danger()
            //         ->title(__('messages.subscription.this_currency_is_not_supported'))
            //         ->send();

            //     return redirect()->back();
            // }

            session(['data' => $data]);

            $mode = getSuperAdminSettingKeyValue('paypal_mode');
            $clientId = getSuperAdminSettingKeyValue('paypal_key');
            $clientSecret = getSuperAdminSettingKeyValue('paypal_secret');

            config([
                'paypal.mode' => $mode,
                'paypal.sandbox.client_id' => $clientId,
                'paypal.sandbox.client_secret' => $clientSecret,
                'paypal.live.client_id' => $clientId,
                'paypal.live.client_secret' => $clientSecret,
            ]);

            $provider = new PayPal;
            $provider->getAccessToken();

            $data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $plan->id,
                        'amount' => [
                            'value' => $plan->payable_amount,
                            // 'currency_code' => $plan->currency->code,
                            'currency_code' => 'USD',
                        ],
                    ],
                ],
                'application_context' => [
                    'cancel_url' => route('paypal.failed').'?error=subscription_failed',
                    'return_url' => route('paypal.success'),
                ],
            ];
            $order = $provider->createOrder($data);

            return redirect($order['links'][1]['href']);
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title($e->getMessage())
                ->send();

            return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
        }
    }

    public function success(Request $request)
    {

        // dd($request->all()); //token and payer id
        $data = session('data');
        $plan = SubscriptionPlan::find($data['plan_id']);
        $mode = getSuperAdminSettingKeyValue('paypal_mode');
        $clientId = getSuperAdminSettingKeyValue('paypal_key');
        $clientSecret = getSuperAdminSettingKeyValue('paypal_secret');

        config([
            'paypal.mode' => $mode,
            'paypal.sandbox.client_id' => $clientId,
            'paypal.sandbox.client_secret' => $clientSecret,
            'paypal.live.client_id' => $clientId,
            'paypal.live.client_secret' => $clientSecret,
        ]);

        $provider = new PayPal;

        $provider->getAccessToken();
        $token = $request->get('token');
        $response = $provider->capturePaymentOrder($token);

        if (isset($response['purchase_units'][0]['payments']['captures'][0]['amount']['value'])) {
            $subscriptionAmount = $response['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
        }
        try {

            DB::beginTransaction();

            $transaction = Transaction::create([
                'transaction_id' => $response['id'],
                'payment_type' => Transaction::TYPE_PAYPAL,
                'amount' => $subscriptionAmount,
                'status' => Subscription::ACTIVE,
                'user_id' => $data['user_id'],
                'meta' => json_encode($response),
            ]);

            $planData['plan'] = $plan->toArray();
            $planData['user_id'] = $data['user_id'];
            $planData['payment_type'] = Subscription::TYPE_PAYPAL;
            $planData['transaction_id'] = $transaction->id;

            $subscription = CreateSubscription::run($planData);

            DB::commit();

            if ($subscription) {
                setPlanFeatures();
                Notification::make()
                    ->success()
                    ->title(getLoggedInUser()->first_name.' '.__('messages.new_change.subscribed_success'))
                    ->send();

                return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
            }
        } catch (HttpException $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function failed(Request $request)
    {
        Notification::make()
            ->danger()
            ->title(__('messages.payment.payment_failed'))
            ->send();

        return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
    }
}
