<?php

namespace App\Http\Controllers;

use App\Mail\NotifyMailSuperAdminForSubscribeHospital;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Repositories\SubscriptionRepository;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;
use Mail;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LandingPhonePeController extends AppBaseController
{
    private $subscriptionRepo;

    public function __construct(SubscriptionRepository $subscriptionRepo)
    {
        $this->subscriptionRepo = $subscriptionRepo;
    }

    public function phonePayInit(Request $request)
    {
        $input = $request->all();
        $currency = ['INR'];

        if (! in_array(strtoupper(getCurrentCurrency()), $currency)) {
            return $this->sendError(__('messages.phonepe.currency_allowed'));
        }

        $result = $this->phonePePayment($input);

        return $this->sendResponse(['url' => $result], 'PhonePe created successfully');
    }

    public function subscriptionPhonePePaymentSuccess(Request $request)
    {
        $subscription = $this->phonePePaymentSuccess($request->all());

        Flash::success($subscription->subscriptionPlan->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'));
        setPlanFeatures();

        if (session('from_pricing') == 'landing.home') {
            return redirect(route('landing-home'));
        } elseif (session('from_pricing') == 'landing.about.us') {
            return redirect(route('landing.about.us'));
        } elseif (session('from_pricing') == 'landing.services') {
            return redirect(route('landing.services'));
        } elseif (session('from_pricing') == 'landing.pricing') {
            return redirect(route('landing.pricing'));
        } else {
            return redirect(route('subscription.pricing.plans.index'));
        }
    }

    private function phonePePayment($input)
    {
        $subscriptionData = $this->manageSubscription($input['planId']);
        $amountToPay = $subscriptionData['amountToPay'];
        $input['subscription_id'] = $subscriptionData['subscription']->id;

        $redirectbackurl = route('user.subscription.phonepe.callback').'?'.http_build_query(['input' => $input]);

        $merchantId = getSuperAdminPaymentCredentials('phonepe_merchant_id');
        $merchantUserId = getSuperAdminPaymentCredentials('phonepe_merchant_id');
        $merchantTransactionId = getSuperAdminPaymentCredentials('phonepe_merchant_transaction_id');
        $baseUrl = getSuperAdminPaymentCredentials('phonepe_env') == 'production' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        $saltKey = getSuperAdminPaymentCredentials('phonepe_salt_key');
        $saltIndex = getSuperAdminPaymentCredentials('phonepe_salt_index');
        $callbackurl = route('user.subscription.phonepe.callback').'?'.http_build_query(['input' => $input]);

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

        return $url;
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
                'user_id' => getLoggedInUserId(),
                'status' => Subscription::ACTIVE,
                'meta' => json_encode($data),
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
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return false;
    }
}
