<?php

namespace App\Http\Controllers;

use App\Models\IpdPatientDepartment;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\StripeRepository;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use View;

class PatientStripeController extends AppBaseController
{
    /**
     * @var StripeRepository
     */
    private $stripeRepository;

    public function __construct(StripeRepository $stripeRepository)
    {
        $this->stripeRepository = $stripeRepository;
    }

    /**
     * @throws ApiErrorException
     */
    public function createSession($input)
    {

        $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
        $amount = $input['amount'];
        $ipdNumber = $input['ipdNumber'];
        $ipdPaientId = IpdPatientDepartment::whereIpdNumber($ipdNumber)->first()->id;

        $user = User::findOrFail(getLoggedInUserId());
        $userEmail = $user->email;
        $stripeKey = Setting::whereTenantId($tenantId)
            ->where('key', '=', 'stripe_secret')
            ->first();
        if (! empty($stripeKey->value)) {
            setStripeApiKey($tenantId);
        } else {
            return $this->sendError(__('messages.new_change.provide_stripe_key'));
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $userEmail,
            'line_items' => [
                [
                    'price_data' => [
                        'product_data' => [
                            'name' => 'BILL OF PATIENT with IPD #'.$ipdNumber,
                        ],
                        'unit_amount' => in_array(strtoupper(getCurrentCurrency()), zeroDecimalCurrencies()) ? $amount : $amount * 100,
                        'currency' => strtoupper(getCurrentCurrency()),
                    ],
                    'quantity' => 1,
                    'description' => 'BILL OF PATIENT with IPD #'.$ipdNumber,
                ],
            ],
            'client_reference_id' => $ipdPaientId,
            'mode' => 'payment',
            'success_url' => url('stripe-payment-success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('stripe-failed-payment?error=payment_cancelled'),
        ]);
        $result = [
            'sessionId' => $session,
        ];
        dd($result);

        return $this->sendResponse($result, __('messages.flash.session_created'));
    }

    /**
     * @return RedirectResponse|RedirectorStripe::setApiKey(<API-KEY>)
     *
     * @throws Exception
     */
    public function paymentSuccess(Request $request): RedirectResponse
    {
        $sessionId = $request->get('session_id');

        if (empty($sessionId)) {
            throw new UnprocessableEntityHttpException('session_id required');
        }

        $this->stripeRepository->patientBillingPaymentSuccess($sessionId);

        Notification::make()
            ->title(__('messages.flash.your_payment_success'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    /**
     * @return Factory|View
     */
    public function handleFailedPayment(): RedirectResponse
    {
        Notification::make()
            ->title(__('messages.flash.your_payment_failed'))
            ->danger()
            ->send();

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }
}
