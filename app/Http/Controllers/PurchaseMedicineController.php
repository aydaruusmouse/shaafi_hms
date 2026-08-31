<?php

namespace App\Http\Controllers;

use App\Exports\PurchaseMedicineExport;
use App\Models\Medicine;
use App\Models\PurchasedMedicine;
use App\Models\PurchaseMedicine;
use App\Repositories\AppointmentTransactionRepository;
use App\Repositories\IpdPaymentRepository;
use App\Repositories\MedicineBillRepository;
use App\Repositories\MedicineRepository;
use App\Repositories\PurchaseMedicineRepository;
use DB;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Unicodeveloper\Paystack\Facades\Paystack;

class PurchaseMedicineController extends AppBaseController
{
    /** @var PurchaseMedicineRepository */
    /** @var MedicineRepository */
    /** @var MedicineBillRepository */
    /** @var IpdPaymentRepository */
    /** @var AppointmentTransactionRepository */
    private $ipdPaymentRepository;

    private $purchaseMedicineRepository;

    private $medicineRepository;

    private $medicineBillRepository;

    private $appointmentTransactionRepository;

    public function __construct(PurchaseMedicineRepository $purchaseMedicineRepo, MedicineRepository $medicineRepository, MedicineBillRepository $medicineBillRepository, IpdPaymentRepository $ipdPaymentRepo, AppointmentTransactionRepository $appointmentTransactionRepository)
    {
        $this->purchaseMedicineRepository = $purchaseMedicineRepo;
        $this->medicineRepository = $medicineRepository;
        $this->medicineBillRepository = $medicineBillRepository;
        $this->ipdPaymentRepository = $ipdPaymentRepo;
        $this->appointmentTransactionRepository = $appointmentTransactionRepository;
    }

    public function index(): View
    {

        return view('purchase-medicines.index');
    }

    public function create(): View
    {

        $data = $this->medicineRepository->getSyncList();
        $medicines = $this->purchaseMedicineRepository->getMedicine();
        $medicineList = $this->purchaseMedicineRepository->getMedicineList();
        $categories = $this->purchaseMedicineRepository->getCategory();
        $categoriesList = $this->purchaseMedicineRepository->getCategoryList();

        return view('purchase-medicines.create', compact('medicines', 'medicineList', 'categories', 'categoriesList'))->with($data);
    }

    public function setFlutterWaveConfig()
    {
        $flutterwavePublicKey = getPaymentCredentials('flutterwave_public_key');
        $flutterwaveSecretKey = getPaymentCredentials('flutterwave_secret_key');

        if (! $flutterwavePublicKey && ! $flutterwaveSecretKey) {
            return $this->sendError(__('messages.flutterwave.set_flutterwave_credential'));
        }

        config([
            'flutterwave.publicKey' => $flutterwavePublicKey,
            'flutterwave.secretKey' => $flutterwaveSecretKey,
        ]);
    }

    public function store($input)
    {

        try {
            DB::beginTransaction();
            session()->forget('sessionUrl');
            if ($input['payment_type'] == PurchaseMedicine::PURCHASE_MEDICINE_STRIPE) {
                $result = $this->purchaseMedicineRepository->store($input);
                $data = $this->purchaseMedicineRepository->stripeSession($input);

                return $data;
            } elseif ($input['payment_type'] == PurchaseMedicine::PURCHASE_MEDICINE_RAZORPAY) {
                if (! in_array(strtoupper(getCurrentCurrency()), getRazorPaySupportedCurrencies())) {
                    session(['paymentError' => 'error']);

                    return Notification::make()
                        ->title(__('messages.flash.currency_not_supported_razorpay'))
                        ->danger()
                        ->send();
                }
                $this->purchaseMedicineRepository->store($input);
                $data = $this->razorPayInit($input);
                DB::commit();

                return $data;
            } elseif ($input['payment_type'] == PurchaseMedicine::PURCHASE_MEDICINE_PAYSTACK) {

                if (! in_array(strtoupper(getCurrentCurrency()), payStackSupportedCurrencies())) {
                    session(['paymentError' => 'error']);

                    return Notification::make()
                        ->title(__('messages.new_change.paystack_support_zar'))
                        ->danger()
                        ->send();
                }
                $data = $this->PaystackPayment($input);

                return $data;
            } elseif ($input['payment_type'] == PurchaseMedicine::PURCHASE_MEDICINE_PHONEPE) {
                $currency = ['INR'];
                if (! in_array(strtoupper(getCurrentCurrency()), $currency)) {
                    session(['paymentError' => 'error']);

                    return Notification::make()
                        ->title(__('messages.phonepe.currency_allowed'))
                        ->danger()
                        ->send();
                }
                $data = $this->purchaseMedicineRepository->phonePePayment($input);

                return $data;
            } elseif ($input['payment_type'] == PurchaseMedicine::PURCHASE_MEDICINE_FLUTTERWAVE) {

                if (! in_array(strtoupper(getCurrentCurrency()), flutterWaveSupportedCurrencies())) {
                    session(['paymentError' => 'error']);

                    return Notification::make()
                        ->title(__('messages.flutterwave.currency_allowed'))
                        ->danger()
                        ->send();
                }

                $this->setFlutterWaveConfig();

                session(['purchaseMedicineDataFlutterwave' => $input]);

                $result = $this->purchaseMedicineRepository->flutterWavePayment($input);

                return $result;
            } elseif ($input['payment_type'] == PurchaseMedicine::PURCHASE_MEDICINE_PAYPAL) {

                if (! in_array(strtoupper(getCurrentCurrency()), getPayPalSupportedCurrencies())) {
                    session(['paymentError' => 'error']);

                    Notification::make()
                        ->title(__('messages.flash.currency_not_supported_paypal'))
                        ->danger()
                        ->send();

                    return;
                }

                $result = $this->paypalPayment($input);

                return $result;
            } else {
                $this->purchaseMedicineRepository->store($input);
                DB::commit();

                return $this->sendSuccess(__('messages.new_change.medicine_purchase_success'));
            }
        } catch (Exception $e) {

            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @param  PurchaseMedicine  $purchaseMedicine
     */
    public function show(PurchaseMedicine $medicinePurchase): View
    {
        $medicinePurchase->load(['purchasedMedcines.medicines']);

        return view('purchase-medicines.show', compact('medicinePurchase'));
    }

    public function getMedicine(Medicine $medicine): JsonResponse
    {
        $medicineExpiryDate = PurchasedMedicine::where('medicine_id', $medicine->id)->latest()->first();
        $medicine['expiry_date'] = isset($medicineExpiryDate->expiry_date) ? date('Y-m-d', strtotime($medicineExpiryDate->expiry_date)) : '';

        return $this->sendResponse($medicine, 'retrieved');
    }

    public function purchaseMedicineExport()
    {

        $response = Excel::download(new PurchaseMedicineExport, 'purchase-medicine-'.time().'.xlsx');

        ob_end_clean();

        return $response;
    }

    /**
     * [Description for usedMedicine]
     *
     * @return [type]
     */
    public function usedMedicine(): View
    {

        return view('used-medicine.index');
    }

    public function destroy(PurchaseMedicine $medicinePurchase)
    {
        $medicinePurchase->delete();

        return $this->sendSuccess(__('messages.flash.medicine_deleted'));
    }

    public function stripeSuccess(Request $request)
    {

        $this->purchaseMedicineRepository->purchaseMedicinestripeSuccess($request->all());

        Notification::make()->title(__('messages.payment.your_payment_is_successfully_completed'))->success()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function stripeFail(Request $request)
    {

        $input = $request->input;
        $purchaseMedicine = PurchaseMedicine::where('purchase_no', $input['purchase_no']);
        $purchaseMedicine->delete();

        foreach ($input['purchasedMedcines'] as $key => $value) {
            $medicine = Medicine::find($input['purchasedMedcines'][$key]['medicine']);
            $medicineQtyArray = [
                'quantity' => $medicine->quantity - $input['purchasedMedcines'][$key]['quantity'],
                'available_quantity' => $medicine->available_quantity - $input['purchasedMedcines'][$key]['quantity'],
            ];
            $medicine->update($medicineQtyArray);
        }

        Notification::make()->title(__('messages.payment.payment_failed'))->danger()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function razorPayInit($input)
    {
        $result = $this->purchaseMedicineRepository->razorPayPayment($input);
        session(['purchaseMedicineData' => $input]);
        $type = 8;
        $record = $input['purchase_no'];
        $amount = $input['net_amount'];

        $data = [
            'payment_mode' => 'razorpay',
            'status' => $type,
            'record' => $record,
            'amount' => $amount,
        ];

        return $data;
    }

    public function razorPaySuccess(Request $request)
    {
        session()->forget('purchaseMedicineData');
        $result = $this->purchaseMedicineRepository->razorPaySuccess($request->all());
        if (! $result) {
            Notification::make()->title(__('messages.payment.payment_failed'))->danger()->send();

            return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
        }
        Notification::make()->title(__('messages.payment.your_payment_is_successfully_completed'))->success()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function razorPayFailed()
    {
        $input = session('purchaseMedicineData');
        session()->forget('purchaseMedicineData');
        $purchaseMedicine = PurchaseMedicine::where('purchase_no', $input['purchase_no']);
        $purchaseMedicine->delete();

        foreach ($input['purchasedMedcines'] as $key => $value) {
            $medicine = Medicine::find($input['purchasedMedcines'][$key]['medicine']);
            $medicineQtyArray = [
                'quantity' => $medicine->quantity - $input['purchasedMedcines'][$key]['quantity'],
                'available_quantity' => $medicine->available_quantity - $input['purchasedMedcines'][$key]['quantity'],
            ];
            $medicine->update($medicineQtyArray);
        }
        Notification::make()->title(__('messages.payment.payment_failed'))->danger()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function paystackConfig()
    {
        config([
            'paystack.publicKey' => getPaymentCredentials('paystack_public_key'),
            'paystack.secretKey' => getPaymentCredentials('paystack_secret_key'),
            'paystack.paymentUrl' => 'https://api.paystack.co',
        ]);
    }

    public function PaystackPayment($input)
    {
        $this->paystackConfig();

        $amount = $input['net_amount'];
        $purchaseNo = $input['purchase_no'];

        try {
            $data = [
                'email' => getLoggedInUser()->email,
                'orderID' => $purchaseNo,
                'amount' => $amount * 100,
                'quantity' => 1,
                'currency' => strtoupper(getCurrentCurrency()),
                'reference' => 'SUB_' . uniqid(),
                'metadata' => json_encode($input),
                'callback_url' => $this->purchaseMedicineRepository->paystackPaymentSuccess($input),
            ];
            // $authorizationUrl = Paystack::getAuthorizationUrl($data);
            $response = Http::withToken(getPaymentCredentials('paystack_secret_key'))
                ->post('https://api.paystack.co/transaction/initialize', $data);

            $responseData = $response->json();

            if (isset($responseData['status']) && $responseData['status'] === true) {
                session(['sessionUrl' => $responseData['data']['authorization_url']]);
            } else {
                session(['paymentError' => 'error']);
            }

            return;
        } catch (\Exception $e) {
            session(['paymentError' => $e->getMessage()]);

            return Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function phonePePaymentSuccess(Request $request)
    {
        $this->purchaseMedicineRepository->phonePePaymentSuccess($request->all());
        Notification::make()->title(__('messages.payment.your_payment_is_successfully_completed'))->success()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function flutterWavePaymentSuccess(Request $request)
    {
        if ($request->status == 'cancelled') {
            Notification::make()->title(__('messages.new_change.payment_fail'))->danger()->send();

            return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
        }

        $this->setFlutterWaveConfig();

        $this->purchaseMedicineRepository->flutterWaveSuccess($request->all());

        Notification::make()->title(__('messages.payment.your_payment_is_successfully_completed'))->success()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function paypalPayment($input)
    {
        $amount = $input['net_amount'];
        $purchaseNo = $input['purchase_no'];
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
                    'reference_id' => $purchaseNo,
                    'amount' => [
                        'value' => $amount,
                        'currency_code' => getCurrentCurrency(),
                    ],
                ],
            ],
            'application_context' => [
                'cancel_url' => route('medicine.purchase.bills.paypal.failed'),
                'return_url' => route('medicine.purchase.bills.paypal.success'),
            ],
        ];

        $order = $provider->createOrder($data);

        if (array_key_exists('error', $order)) {
            $data['error'] = $order['error'];

            return $data;
        }

        $url = $order['links'][1]['href'];
        session(['purchaseMedicine' => $input]);
        session(['sessionUrl' => $url]);

        return $url;
    }

    public function paypalSuccess(Request $request)
    {

        session()->forget('sessionUrl');
        Notification::make()
            ->title(__('messages.flash.your_payment_success'))
            ->success()
            ->send();
        $this->purchaseMedicineRepository->paypalSuccess($request->all());

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }

    public function paypalFailed(Request $request)
    {
        session()->forget('purchaseMedicine');
        session()->forget('sessionUrl');
        Notification::make()->title(__('messages.payment.payment_failed'))->danger()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.purchase-medicines.index'));
    }
}
