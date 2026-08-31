<?php

namespace App\Http\Controllers;

use App\Models\IpdPatientDepartment;
use App\Models\User;
use App\Repositories\PatientPaypalRepository;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

/**
 * Class PatientPaypalController
 */
class PatientPaypalController extends AppBaseController
{
    /**
     * @var PatientPaypalRepository
     */
    private $patientPaypalRepository;

    public function __construct(PatientPaypalRepository $patientPaypalRepository)
    {
        $this->patientPaypalRepository = $patientPaypalRepository;
    }

    public function onBoard($input)
    {

        try {

            $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
            $amount = $input['amount'];
            // $ipdNumber = $request->get('ipdNumber');
            // $ipdPatientId = IpdPatientDepartment::whereIpdNumber($ipdNumber)->first()->id;
            $ipdPatientId = $input['ipd_patient_department_id'];
            $mode = getSelectedPaymentGateway('paypal_mode');
            $clientId = getSelectedPaymentGateway('paypal_client_id');
            $clientSecret = getSelectedPaymentGateway('paypal_secret');

            config([
                'paypal.mode' => $mode,
                'paypal.sandbox.client_id' => $clientId,
                'paypal.sandbox.client_secret' => $clientSecret,
                'paypal.live.client_id' => $clientId,
                'paypal.live.client_secret' => $clientSecret,
            ]);

            $provider = new PayPalClient;
            $provider->getAccessToken();

            $data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $ipdPatientId,
                        'amount' => [
                            'value' => $amount,
                            'currency_code' => getCurrentCurrency(),
                        ],
                    ],
                ],
                'application_context' => [
                    'cancel_url' => route('patient.paypal.failed'),
                    'return_url' => route('patient.paypal.success'),
                ],
            ];

            $order = $provider->createOrder($data);

            if (isset($order['error'])) {
                return $order;
            }

            $url = $order['links'][1]['href'];
            $data['url'] = $url;

            return $data;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();

            return $result;
        }
    }

    public function success(Request $request): RedirectResponse
    {
        $mode = getSelectedPaymentGateway('paypal_mode');
        $clientId = getSelectedPaymentGateway('paypal_client_id');
        $clientSecret = getSelectedPaymentGateway('paypal_secret');

        config([
            'paypal.mode' => $mode,
            'paypal.sandbox.client_id' => $clientId,
            'paypal.sandbox.client_secret' => $clientSecret,
            'paypal.live.client_id' => $clientId,
            'paypal.live.client_secret' => $clientSecret,
        ]);

        $provider = new PayPalClient;

        $provider->getAccessToken();

        $token = $request->get('token');

        $response = $provider->capturePaymentOrder($token);

        $this->patientPaypalRepository->patientPaymentSuccess($response);

        Notification::make()
            ->title(__('messages.flash.your_payment_success'))
            ->success()
            ->send();

        if (getLoggedinPatient()) {
            return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
        }

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }

    public function failed()
    {

        Notification::make()
            ->title(__('messages.flash.your_payment_failed'))
            ->danger()
            ->send();

        if (getLoggedinPatient()) {
            return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
        }

        return redirect(route('filament.hospitalAdmin.ipd-opd.resources.ipd-patients.index'));
    }
}
