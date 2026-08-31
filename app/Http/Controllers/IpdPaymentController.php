<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIpdPaymentRequest;
use App\Models\IpdPayment;
use App\Repositories\IpdPaymentRepository;
use App\Repositories\PatientPaypalRepository;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Unicodeveloper\Paystack\Facades\Paystack;

class IpdPaymentController extends AppBaseController
{
    /** @var IpdPaymentRepository */
    private $ipdPaymentRepository;

    public function __construct(IpdPaymentRepository $ipdPaymentRepo)
    {
        $this->ipdPaymentRepository = $ipdPaymentRepo;
    }
    
    /**
     * Display a listing of the IpdPayment.
     *
     *
     * @throws Exception
     */
    public function index(Request $request) {}

    /**
     * Store a newly created IpdPayment in storage.
     */
    public function store($input)
    {
        $data = [];
        if ($input['payment_mode'] == IpdPayment::PAYMENT_MODES_STRIPE) {
            $data = $this->ipdPaymentRepository->stripeSession($input);

            return $data;
        } elseif ($input['payment_mode'] == IpdPayment::PAYMENT_MODES_RAZORPAY) {
            if (! in_array(strtoupper(getCurrentCurrency()), getRazorPaySupportedCurrencies())) {
                $data['error'] = __('messages.flash.currency_not_supported_razorpay');

                return $data;
            }

            $data = $this->ipdRazorpayPayment($input);

            return $data;
        } elseif ($input['payment_mode'] == IpdPayment::PAYMENT_MODES_PAYPAL) {
            if (! in_array(strtoupper(getCurrentCurrency()), getPayPalSupportedCurrencies())) {

                $data['error'] = __('messages.flash.currency_not_supported_paypal');

                return $data;
            }

            $patientPaypalController = new PatientPaypalController(new PatientPaypalRepository);
            $data = $patientPaypalController->onBoard($input);

            return $data;
        }

        // elseif($input['payment_mode'] == IpdPayment::PAYMENT_MODES_PAYTM){

        //     return $this->sendResponse([
        //         'ipdID' => $input['ipd_patient_department_id'],
        //         'amount' => $input['amount'],
        //         'payment_type' => $input['payment_mode'],
        //     ],'Paytm session created successfully');

        // }
        elseif ($input['payment_mode'] == IpdPayment::PAYMENT_MODES_PAYSTACK) {
            $request = new Request($input);

            $data = $this->ipdPaystackPayment($request);

            return $data;
        } elseif ($input['payment_mode'] == IpdPayment::PAYMENT_MODES_PHONEPE) {

            $currency = ['INR'];

            if (! in_array(strtoupper(getCurrentCurrency()), $currency)) {
                $data['error'] = __('messages.phonepe.currency_allowed');

                return $data;
            }

            $result = $this->ipdPaymentRepository->phonePePayment($input);

            $data['url'] = $result;

            return $data;
        } elseif ($input['payment_mode'] == IpdPayment::PAYMENT_MODES_FLUTTERWAVE) {

            if (! in_array(strtoupper(getCurrentCurrency()), flutterWaveSupportedCurrencies())) {
                $data['error'] = __('messages.flutterwave.currency_allowed');

                return $data;
            }

            $flutterwavePublicKey = getPaymentCredentials('flutterwave_public_key');
            $flutterwaveSecretKey = getPaymentCredentials('flutterwave_secret_key');

            if (! $flutterwavePublicKey && ! $flutterwaveSecretKey) {
                return $this->sendError(__('messages.flutterwave.set_flutterwave_credential'));
            }

            config([
                'flutterwave.publicKey' => $flutterwavePublicKey,
                'flutterwave.secretKey' => $flutterwaveSecretKey,
            ]);

            $data['url'] = $this->ipdPaymentRepository->flutterWavePayment($input);

            return $data;
        } else {
            $data = $this->ipdPaymentRepository->store($input);

            return $data;
        }

        return $input;
    }

    /**
     * Show the form for editing the specified Ipd Payment.`
     */
    public function edit(IpdPayment $ipdPayment): JsonResponse
    {
        if (! canAccessRecord(IpdPayment::class, $ipdPayment->id)) {
            return $this->sendError(__('messages.flash.ipd_payment_not_found'));
        }

        return $this->sendResponse($ipdPayment, __('messages.flash.IPD_payment_retrieved'));
    }

    /**
     * Update the specified Ipd Payment in storage.
     */
    public function update(IpdPayment $ipdPayment, UpdateIpdPaymentRequest $request): JsonResponse
    {
        $this->ipdPaymentRepository->updateIpdPayment($request->all(), $ipdPayment->id);

        return $this->sendSuccess(__('messages.flash.IPD_payment_updated'));
    }

    /**
     * Remove the specified IpdPayment from storage.
     *
     *
     * @throws Exception
     */
    public function destroy(IpdPayment $ipdPayment): JsonResponse
    {
        if (! canAccessRecord(IpdPayment::class, $ipdPayment->id)) {
            return $this->sendError(__('messages.flash.ipd_payment_not_found'));
        }

        $this->ipdPaymentRepository->deleteIpdPayment($ipdPayment->id);

        return $this->sendSuccess(__('messages.flash.IPD_payment_deleted'));
    }

    public function downloadMedia(IpdPayment $ipdPayment): Media
    {
        $media = $ipdPayment->getMedia(IpdPayment::IPD_PAYMENT_PATH)->first();
        ob_end_clean();
        if ($media != null) {
            $media = $media->id;
            $mediaItem = Media::findOrFail($media);

            return $mediaItem;
        }

        return '';
    }

    public function handleFailedPayment(): RedirectResponse
    {
        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        if (getLoggedinPatient()) {
            return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
        }

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function ipdStripePaymentSuccess(Request $request)
    {
        $this->ipdPaymentRepository->ipdStripePaymentSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        if (getLoggedinPatient()) {
            return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
        }

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function ipdRazorpayPayment($input)
    {
        $result = $this->ipdPaymentRepository->razorpayPayment($input);

        if (! $result) {
            $data['error'] = __('messages.payment.payment_failed');

            return $data;
        }
        session(['ipdPayment' => $input]);
        $type = 6;
        $record = $input['ipd_patient_department_id'];
        $amount = $result['amount'];

        $data = [
            'payment_mode' => 'razorpay',
            'status' => $type,
            'record' => $record,
            'amount' => $amount,
        ];

        return $data;
    }

    public function ipdRazorpayPaymentSuccess(Request $request)
    {

        $this->ipdPaymentRepository->ipdRazorpayPaymentSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        if (getLoggedinPatient()) {
            return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
        }

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function ipdRazorpayPaymentFailed()
    {
        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function phonePePaymentSuccess(Request $request)
    {
        $this->ipdPaymentRepository->phonePePaymentSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function flutterwavePaymentSuccess(Request $request)
    {
        if ($request->status == 'cancelled') {

            Notification::make()
                ->title(__('messages.payment.payment_failed'))
                ->danger()
                ->send();

            return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
        }

        $this->ipdPaymentRepository->flutterWaveSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function paystackConfig()
    {
        config([
            'paystack.publicKey' => getPaymentCredentials('paystack_public_key'),
            'paystack.secretKey' => getPaymentCredentials('paystack_secret_key'),
            'paystack.paymentUrl' => 'https://api.paystack.co',
        ]);
    }

    public function ipdPaystackPayment(Request $request)
    {
        $input = $request->all();
        $data = [];
        if (! in_array(strtoupper(getCurrentCurrency()), payStackSupportedCurrencies())) {
            $data['error'] = __('messages.new_change.paystack_support_zar');

            return $data;
        }
        $amount = $request->amount;
        $ipdNumber = $request->ipd_patient_department_id;

        $this->paystackConfig();

        try {
            $paystackData = [
                'email' => getLoggedInUser()->email,
                'orderID' => $ipdNumber,
                'amount' => (int)($amount * 100),
                'quantity' => 1,
                'currency' => strtoupper(getCurrentCurrency()),
                'reference' => 'SUB_' . uniqid(),
                'metadata' => json_encode($input),
                'callback_url' => $this->ipdPaymentRepository->ipdPaystackPaymentSuccess($input),
            ];

            // $authorizationUrl = Paystack::getAuthorizationUrl($data);
            $response = Http::withToken(getPaymentCredentials('paystack_secret_key'))
                ->post('https://api.paystack.co/transaction/initialize', $paystackData);

            $responseData = $response->json();

            if (isset($responseData['status']) && $responseData['status'] === true && isset($responseData['data']['authorization_url'])) {
                return redirect($responseData['data']['authorization_url']);
            } else {
                logger()->error('Paystack Init Failed', $responseData); // For debugging
                throw new \Exception($responseData['message'] ?? 'Paystack error: Invalid response');
            }
        } catch (\Exception $e) {
            $data['error'] = __($e->getMessage());
            dd($data);
            return $data;
        }
    }

    public function IpdPaystackPaystackSuccess(Request $request)
    {
        $paymentDetails = Paystack::getPaymentData();

        $this->ipdPaymentRepository->ipdPaystackPaymentSuccess($paymentDetails);

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }
}
