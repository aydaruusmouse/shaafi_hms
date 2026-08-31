<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Repositories\RazorpayRepository;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Laracasts\Flash\Flash;
use PayPalHttp\HttpException;
use Razorpay\Api\Api;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RazorpayController extends AppBaseController
{
    /**
     * @var RazorpayRepository
     */
    private $razorPayRepo;

    public function __construct(RazorpayRepository $razorpayRepository)
    {
        $this->razorPayRepo = $razorpayRepository;
    }

    /**
     * @return Application|JsonResponse|RedirectResponse|Redirector
     */
    public function purchaseSubscription(Request $request)
    {
        $plan = json_decode($request->plan);

        $input = $request->all();

        try {
            $subscriptionsPricingPlan = SubscriptionPlan::findOrFail($input['plan_id']);
            if ($subscriptionsPricingPlan->currency != null && ! in_array(
                strtoupper($subscriptionsPricingPlan->currency),
                getRazorPaySupportedCurrencies()
            )) {
                Flash::error(__('messages.flash.currency_not_supported_razorpay'));
                if (session('from_pricing') == 'landing.home') {
                    return response()->json(['url' => route('landing-home')]);
                } elseif (session('from_pricing') == 'landing.about.us') {
                    return response()->json(['url' => route('landing.about.us')]);
                } elseif (session('from_pricing') == 'landing.services') {
                    return response()->json(['url' => route('landing.services')]);
                } elseif (session('from_pricing') == 'landing.pricing') {
                    return response()->json(['url' => route('landing.pricing')]);
                } else {
                    return response()->json(['url' => route('subscription.pricing.plans.index')]);
                }
            }

            $subscriptionData = $this->razorPayRepo->manageSubscription($input['plan_id']);
            if (! isset($data['plan'])) { // 0 amount plan or try to switch the plan if it is in trial mode
                // returning from here if the plan is free.
                if (isset($data['status']) && $data['status'] == true) {
                    return $this->sendSuccess($data['subscriptionPlan']->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'));
                } else {
                    if (isset($data['status']) && $data['status'] == false) {
                        return $this->sendError(__('messages.flash.cannot_switch'));
                    }
                }
            }

            $razorPayKey = getSuperAdminPaymentCredentials('razorpay_key');
            $razorPaySecret = getSuperAdminPaymentCredentials('razorpay_secret');

            if (isset($razorPayKey) && ! is_null($razorPayKey) && isset($razorPaySecret) && ! is_null($razorPaySecret)) {
                $key = getSuperAdminPaymentCredentials('razorpay_key');
                $secret = getSuperAdminPaymentCredentials('razorpay_secret');
            } else {
                $key = config('payments.razorpay.key');
                $secret = config('payments.razorpay.secret');
            }

            $api = new Api($key, $secret);
            $receipt = (string) $subscriptionData['plan']->id;
            $orderData = [
                'receipt' => $receipt,
                'amount' => intval($subscriptionData['amountToPay']) * 100, // 100 = 1 rupees
                'currency' => strtoupper($subscriptionData['plan']->currency),
                'notes' => [
                    'email' => getLoggedInUser()->email,
                    'name' => getLoggedInUser()->first_name.' '.getLoggedInUser()->last_name,
                    'subscription_id' => $subscriptionData['subscription']->id,
                ],
            ];
            $razorpayOrder = $api->order->create($orderData);
            $data['id'] = $razorpayOrder->id;
            $data['amount'] = $subscriptionData['amountToPay'] * 100;
            $data['name'] = getLoggedInUser()->first_name.' '.getLoggedInUser()->last_name;
            $data['email'] = getLoggedInUser()->email;
            $data['contact'] = getLoggedInUser()->phone;
            $data['currency'] = strtoupper($subscriptionData['plan']->currency);
            $data['planID'] = $subscriptionData['subscription']->id;

            return $this->sendResponse($data, __('messages.flash.order_created'));
        } catch (HttpException $ex) {
            throw new UnprocessableEntityHttpException($ex->getMessage());
        }
    }

    /**
     * @return Application|Redirector|RedirectResponse
     */
    public function paymentSuccess(Request $request)
    {

        $input = $request->all();

        try {
            $subscription = $this->razorPayRepo->paymentSuccess($input);

            if (count($input) === 1 && isset($input['razorpay_payment_id'])) {
                Notification::make()
                    ->success()
                    ->title(__('messages.payment.your_payment_is_successfully_completed'))
                    ->send();

                return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
            }
            Flash::success($subscription->subscriptionPlan->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'));

            $toastData = [
                'toastType' => 'success',
                'toastMessage' => $subscription->subscriptionPlan->name.' '.__('messages.subscription_pricing_plans.has_been_subscribed'),
            ];

            if (session('from_pricing') == 'landing.home') {
                return redirect(route('landing-home'))->with('toast-data', $toastData);
            } elseif (session('from_pricing') == 'landing.about.us') {
                return redirect(route('landing.about.us'))->with('toast-data', $toastData);
            } elseif (session('from_pricing') == 'landing.services') {
                return redirect(route('landing.services'))->with('toast-data', $toastData);
            } elseif (session('from_pricing') == 'landing.pricing') {
                return redirect(route('landing.pricing'))->with('toast-data', $toastData);
            } else {
                return redirect(route('subscription.pricing.plans.index'));
            }
        } catch (\Exception $e) {
            Log::info('RazorPay Payment Failed Error:'.$e->getMessage());
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return Application|JsonResponse|RedirectResponse|Redirector
     */
    public function paymentFailed(Request $request): JsonResponse
    {
        try {
            $data = $request->get('data');
            Log::info('payment failed');
            Log::info($data);
            $subscription = session('subscription_plan_id');

            /** @var RazorpayRepository $RazorpayRepo */
            $this->razorPayRepo->paymentFailed($subscription);

            // Flash::error(__('messages.flash.unable_to_process'));

            if (session('from_pricing') == 'landing.home') {
                return response()->json(['url' => route('landing-home')]);
            } elseif (session('from_pricing') == 'landing.about.us') {
                return response()->json(['url' => route('landing.about.us')]);
            } elseif (session('from_pricing') == 'landing.services') {
                return response()->json(['url' => route('landing.services')]);
            } elseif (session('from_pricing') == 'landing.pricing') {
                return response()->json(['url' => route('landing.pricing')]);
            } else {
                return response()->json(['url' => route('subscription.pricing.plans.index')]);
            }
        } catch (\Exception $ex) {
            throw new UnprocessableEntityHttpException($ex->getMessage());
        }
    }

    /**
     * @return Application|JsonResponse|RedirectResponse|Redirector
     */
    public function paymentFailedModal(Request $request)
    {
        if (empty($request->all())) {
            session()->forget('subscriptionRazorpayData');
            Notification::make()
                ->danger()
                ->title(__('messages.payment.payment_failed'))
                ->send();

            return redirect(route('filament.hospitalAdmin.pages.subscription-plans'));
        }

        try {
            $subscription = session('subscription_plan_id');

            /** @var RazorpayRepository $RazorpayRepo */
            $this->razorPayRepo->paymentFailed($subscription);

            Flash::error('Payment not completed.');

            if (session('from_pricing') == 'landing.home') {
                return response()->json(['url' => route('landing-home')]);
            } elseif (session('from_pricing') == 'landing.about.us') {
                return response()->json(['url' => route('landing.about.us')]);
            } elseif (session('from_pricing') == 'landing.services') {
                return response()->json(['url' => route('landing.services')]);
            } elseif (session('from_pricing') == 'landing.pricing') {
                return response()->json(['url' => route('landing.pricing')]);
            } else {
                return response()->json([
                    'url' => route('subscription.pricing.plans.index'),
                    'message' => 'Payment not completed.',
                ]);
            }
        } catch (HttpException $ex) {
            throw new UnprocessableEntityHttpException($ex->getMessage());
        }
    }
}
