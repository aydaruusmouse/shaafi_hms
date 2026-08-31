<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineBill;
use App\Models\SaleMedicine;
use App\Repositories\DoctorRepository;
use App\Repositories\IpdPatientDepartmentRepository;
use App\Repositories\MedicineBillRepository;
use App\Repositories\MedicineRepository;
use App\Repositories\PatientRepository;
use App\Repositories\PrescriptionRepository;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use PDF;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Unicodeveloper\Paystack\Facades\Paystack;

class MedicineBillController extends AppBaseController
{
    /* @var  PrescriptionRepository
          @var DoctorRepository
         */
    private $prescriptionRepository;

    private $doctorRepository;

    private $medicineRepository;

    private $patientRepository;

    private $medicineBillRepository;

    public function __construct(
        PrescriptionRepository $prescriptionRepo,
        DoctorRepository $doctorRepository,
        MedicineRepository $medicineRepository,
        PatientRepository $patientRepo,
        MedicineBillRepository $medicineBillRepository,
    ) {
        $this->prescriptionRepository = $prescriptionRepo;
        $this->doctorRepository = $doctorRepository;
        $this->medicineRepository = $medicineRepository;
        $this->patientRepository = $patientRepo;
        $this->medicineBillRepository = $medicineBillRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {

        return view('medicine-bills.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {

        $patients = $this->prescriptionRepository->getPatients();
        $doctors = $this->doctorRepository->getDoctors();
        $medicines = $this->prescriptionRepository->getMedicines();
        $data = $this->medicineRepository->getSyncList();
        $medicineList = $this->medicineRepository->getMedicineList();
        $mealList = $this->medicineRepository->getMealList();
        $IpdRepo = App::make(IpdPatientDepartmentRepository::class);
        $medicineCategories = $IpdRepo->getMedicinesCategoriesData();
        $medicineCategoriesList = $IpdRepo->getMedicineCategoriesList();

        return view(
            'medicine-bills.create',
            compact('patients', 'doctors', 'medicines', 'medicineList', 'mealList', 'medicineCategoriesList', 'medicineCategories')
        )->with($data);
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

    /**
     * Store a newly created resource in storage.
     */
    public function store($input)
    {

        if ($input['payment_type'] == MedicineBill::MEDICINE_BILL_STRIPE) {

            foreach ($input['saleMedicine'] as $key => $value) {
                $medicine = Medicine::find($input['saleMedicine'][$key]['medicine_id']);
                if (! empty($duplicateIds)) {
                    foreach ($duplicateIds as $key => $value) {
                        $medicine = Medicine::find($duplicateIds[$key]);
                        $data['error'] = __('messages.medicine_bills.duplicate_medicine');

                        return $data;
                    }
                }
                $qty = $input['saleMedicine'][$key]['sale_quantity'];

                if ($medicine->available_quantity < $qty) {
                    $available = $medicine->available_quantity == null ? 0 : $medicine->available_quantity;
                    $data['error'] = __('messages.medicine_bills.available_quantity').' '.$medicine->name.' '.__('messages.medicine_bills.is').' '.$available.'.';

                    return $data;
                }
            }

            $medicineBill = $this->medicineBillRepository->medicineBillStore($input);
            $result = $this->medicineBillRepository->stripeSession($input, $medicineBill);

            return $result;
        } elseif ($input['payment_type'] == MedicineBill::MEDICINE_BILL_RAZORPAY) {
            if (! in_array(strtoupper(getCurrentCurrency()), getRazorPaySupportedCurrencies())) {
                session(['paymentError' => 'error']);

                return Notification::make()
                    ->title(__('messages.flash.currency_not_supported_razorpay'))
                    ->danger()
                    ->send();
            }
            $medicineBill = $this->medicineBillRepository->medicineBillStore($input);
            $data = $this->razorPayPayment($input);

            return $data;
        } elseif ($input['payment_type'] == MedicineBill::MEDICINE_BILL_PAYSTACK) {

            foreach ($input['saleMedicine'] as $key => $value) {
                $medicine = Medicine::find($input['saleMedicine'][$key]['medicine_id']);
                if (! empty($duplicateIds)) {
                    foreach ($duplicateIds as $key => $value) {
                        $medicine = Medicine::find($duplicateIds[$key]);
                        $data['error'] = __('messages.medicine_bills.duplicate_medicine');

                        return $data;
                    }
                }
                $qty = $input['saleMedicine'][$key]['sale_quantity'];

                if ($medicine->available_quantity < $qty) {
                    $available = $medicine->available_quantity == null ? 0 : $medicine->available_quantity;
                    $data['error'] = __('messages.medicine_bills.available_quantity').' '.$medicine->name.' '.__('messages.medicine_bills.is').' '.$available.'.';

                    return $data;
                }
            }

            $data = $this->paystackPayment($input);

            return $data;
        } elseif ($input['payment_type'] == MedicineBill::MEDICINE_BILL_PHONEPE) {
            $currency = ['INR'];
            if (! in_array(strtoupper(getCurrentCurrency()), $currency)) {
                $data['error'] = __('messages.phonepe.currency_allowed');

                return $data;
            }

            $result = $this->medicineBillRepository->phonePePayment($input);

            return $result;
        } elseif ($input['payment_type'] == MedicineBill::MEDICINE_BILL_FLUTTERWAVE) {

            if (! in_array(strtoupper(getCurrentCurrency()), flutterWaveSupportedCurrencies())) {
                $data['error'] = __('messages.flutterwave.currency_allowed');

                return $data;
            }

            foreach ($input['saleMedicine'] as $key => $value) {
                $medicine = Medicine::find($input['saleMedicine'][$key]['medicine_id']);
                if (! empty($duplicateIds)) {
                    foreach ($duplicateIds as $key => $value) {
                        $medicine = Medicine::find($duplicateIds[$key]);
                        $data['error'] = __('messages.medicine_bills.duplicate_medicine');

                        return $data;
                    }
                }
                $qty = $input['saleMedicine'][$key]['sale_quantity'];

                if ($medicine->available_quantity < $qty) {
                    $available = $medicine->available_quantity == null ? 0 : $medicine->available_quantity;
                    $data['error'] = __('messages.medicine_bills.available_quantity').' '.$medicine->name.' '.__('messages.medicine_bills.is').' '.$available.'.';

                    return $data;
                }
            }

            $this->setFlutterWaveConfig();

            $data = $this->medicineBillRepository->flutterWavePayment($input);

            return $data;
        } elseif ($input['payment_type'] == MedicineBill::MEDICINE_BILL_PAYPAL) {

            if (! in_array(strtoupper(getCurrentCurrency()), getPayPalSupportedCurrencies())) {

                $data['error'] = __('messages.flash.currency_not_supported_paypal');

                return $data;
            }

            foreach ($input['saleMedicine'] as $key => $value) {
                $medicine = Medicine::find($input['saleMedicine'][$key]['medicine_id']);
                if (! empty($duplicateIds)) {
                    foreach ($duplicateIds as $key => $value) {
                        $medicine = Medicine::find($duplicateIds[$key]);
                        $data['error'] = __('messages.medicine_bills.duplicate_medicine');

                        return $data;
                    }
                }
                $qty = $input['saleMedicine'][$key]['sale_quantity'];

                if ($medicine->available_quantity < $qty) {
                    $available = $medicine->available_quantity == null ? 0 : $medicine->available_quantity;
                    $data['error'] = __('messages.medicine_bills.available_quantity').' '.$medicine->name.' '.__('messages.medicine_bills.is').' '.$available.'.';

                    return $data;
                }
            }
            $medicineBill = $this->medicineBillRepository->medicineBillStore($input);
            $data = $this->paypalPayment($input, $medicineBill);

            return $data;
        } else {
            $this->medicineBillRepository->medicineBillStore($input);

            return $this->sendSuccess(__('messages.medicine_bills.medicine_bill').' '.__('messages.common.saved_successfully'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     */
    public function show(MedicineBill $medicineBill): View
    {
        $medicineBill->load(['saleMedicine.medicine']);

        return view('medicine-bills.show', compact('medicineBill'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MedicineBill $medicineBill): View
    {
        $medicineBill->load(['saleMedicine.medicine.category', 'saleMedicine.medicine.purchasedMedicine', 'patient', 'doctor']);

        $patients = $this->prescriptionRepository->getPatients();
        $doctors = $this->doctorRepository->getDoctors();
        $medicines = $this->prescriptionRepository->getMedicines();
        $data = $this->medicineRepository->getSyncList();
        $medicineList = $this->medicineRepository->getMedicineList();
        $mealList = $this->medicineRepository->getMealList();
        $IpdRepo = App::make(IpdPatientDepartmentRepository::class);
        $medicineCategories = $IpdRepo->getMedicinesCategoriesData();
        $medicineCategoriesList = $IpdRepo->getMedicineCategoriesList();

        return view(
            'medicine-bills.edit',
            compact('patients', 'doctors', 'medicines', 'medicineList', 'mealList', 'medicineBill', 'medicineCategoriesList', 'medicineCategories')
        )->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Remove the specified resource from storage.
     *
     * *  @return \Illuminate\Http\Response
     */
    public function destroy(MedicineBill $medicineBill)
    {
        $medicineBill->saleMedicine()->delete();
        $medicineBill->delete();

        return $this->sendSuccess(__('messages.medicine_bills.medicine_bill').' '.__('messages.common.deleted_successfully'));
    }

    /** Store a newly created Patient in storage.
     */
    public function convertToPDF($id): Response
    {
        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $data = $this->prescriptionRepository->getSettingList();

        // Get the image contents
        $imageData = Http::get($data['app_logo'])->body();
        $imageType = pathinfo($data['app_logo'], PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);

        $data['app_logo'] = $base64Image;
        $medicineBill = MedicineBill::with(['saleMedicine.medicine'])->where('id', $id)->first();

        $pdf = PDF::loadView('medicine-bills.medicine_bill_pdf', compact('medicineBill', 'data'));

        return $pdf->stream('medicine-bill.pdf');
    }

    public function getMedicineCategory(Category $category): JsonResponse
    {
        $data = [];
        $data['category'] = $category;
        $data['medicine'] = Medicine::whereCategoryId($category->id)->pluck('name', 'id')->toArray();

        return $this->sendResponse($data, 'retrieved');
    }

    public function stripeSuccess(Request $request)
    {
        $this->medicineBillRepository->medicineBillstripeSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function stripeFailed(Request $request)
    {

        $this->medicineBillRepository->medicineBillstripeFailed($request->all());

        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function razorPayPayment($input)
    {
        $result = $this->medicineBillRepository->razorPayPayment($input);
        session(['medicineBillData' => $input]);
        $type = 9;
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

    public function razorPayPaymentSuccess(Request $request)
    {
        session()->forget('medicineBillData');
        $result = $this->medicineBillRepository->razorPayPaymentSuccess($request->all());
        if (! $result) {
            Notification::make()->title(__('messages.payment.payment_failed'))->danger()->send();

            return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
        }
        Notification::make()->title(__('messages.payment.your_payment_is_successfully_completed'))->success()->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function razorPayPaymentFailed(Request $request)
    {
        $input = session()->get('medicineBillData');
        session()->forget('medicineBillData');
        $input['payment_status'] = isset($input['payment_status']) ? 1 : 0;

        $medicineBill = MedicineBill::orderBy('created_at', 'desc')->latest()->first();

        if ($input['saleMedicine']) {
            foreach ($input['saleMedicine'] as $key => $value) {
                $medicine = Medicine::find($input['saleMedicine'][$key]['medicine_id']);
                $tax = $input['saleMedicine'][$key]['tax'] == null ? $input['saleMedicine'][$key]['tax'] : 0;

                $saleMedicine = SaleMedicine::where('medicine_bill_id', $medicineBill->id)->first();
                $saleMedicine->delete();

                if ($input['payment_status'] == 1) {
                    $medicine->update([
                        'available_quantity' => $input['saleMedicine'][$key]['sale_quantity'] + $medicine->available_quantity,
                    ]);
                }
            }
        }

        $medicineBill->delete();

        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function paystackConfig()
    {
        config([
            'paystack.publicKey' => getPaymentCredentials('paystack_public_key'),
            'paystack.secretKey' => getPaymentCredentials('paystack_secret_key'),
            'paystack.paymentUrl' => 'https://api.paystack.co',
        ]);
    }

    public function paystackPayment($input)
    {
        $this->paystackConfig();
        if (! in_array(strtoupper(getCurrentCurrency()), payStackSupportedCurrencies())) {
            session(['paymentError' => 'error']);

            return Notification::make()
                ->title(__('messages.new_change.paystack_support_zar'))
                ->danger()
                ->send();
        }

        $amount = $input['net_amount'];

        try {
            $data = [
                'email' => getLoggedInUser()->email,
                'amount' => $amount * 100,
                'quantity' => 1,
                'currency' => strtoupper(getCurrentCurrency()),
                'reference' => 'SUB_' . uniqid(),
                'metadata' => json_encode($input),
                'callback_url' => $this->medicineBillRepository->paystackPaymentSuccess($input),
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
            $data['error'] = $e->getMessage();

            return $data;
        }
    }

    public function paypalPayment(array $input, $medicineBill)
    {
        $amount = $input['net_amount'];
        $patientID = $input['patient_id'];
        $medicineBillID = $medicineBill->id;
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
                    'reference_id' => $medicineBillID,
                    'amount' => [
                        'value' => $amount,
                        'currency_code' => getCurrentCurrency(),
                    ],
                ],
            ],
            'application_context' => [
                'cancel_url' => route('medicine.bills.paypal.failed', ['bill_id' => $medicineBillID]),
                'return_url' => route('medicine.bills.paypal.success'),
            ],
        ];

        $order = $provider->createOrder($data);

        if (array_key_exists('error', $order)) {
            MedicineBill::find($medicineBillID)->delete();
            $data['error'] = __('messages.payment.payment_failed');

            return $data;
        }

        $url = $order['links'][1]['href'];

        session(['sessionUrl' => $url]);

        return $url;
    }

    public function paypalSuccess(Request $request): RedirectResponse
    {
        session()->forget('sessionUrl');
        Notification::make()
            ->title(__('messages.flash.your_payment_success'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function paypalFailed(Request $request): RedirectResponse
    {
        $medicineBillID = $request['bill_id'];
        if ($medicineBillID) {
            MedicineBill::find($medicineBillID)->delete();
        }

        session()->forget('sessionUrl');
        session(['paymentError' => 'error']);
        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function phonePePaymentSuccess(Request $request)
    {
        $this->medicineBillRepository->phonePePaymentSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }

    public function flutterWaveSuccess(Request $request)
    {
        if ($request->status == 'cancelled') {
            session(['paymentError' => 'error']);
            Notification::make()
                ->title(__('messages.new_change.payment_fail'))
                ->danger()
                ->send();

            return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
        }

        $this->setFlutterWaveConfig();

        $this->medicineBillRepository->flutterWaveSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect(route('filament.hospitalAdmin.medicine.resources.medicine-bills.index'));
    }
}
