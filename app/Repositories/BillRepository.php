<?php

namespace App\Repositories;

use App\Models\Accountant;
use App\Models\Bill;
use App\Models\BillItems;
use App\Models\BillTransaction;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\Receptionist;
use App\Models\Setting;
use App\Models\User;
use Arr;
use Carbon\Carbon;
use DB;
use Exception;
use Filament\Notifications\Notification as FilamentNotification;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Razorpay\Api\Api;
use Srmklive\PayPal\Services\PayPal;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Unicodeveloper\Paystack\Facades\Paystack;
use Validator;

/**
 * Class BillRepository
 *
 * @version February 13, 2020, 9:47 am UTC
 */
class BillRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'patient_id',
        'bill_date',
        'amount',
    ];

    /**
     * Return searchable fields
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Bill::class;
    }

    /**
     * @return mixed
     */
    public function getSyncList($isEditScreen)
    {
        $data['associateMedicines'] = $this->getAssociateMedicinesList();
        $data['patientAdmissionIds'] = $this->getPatientAdmissionIdList($isEditScreen);
        ksort($data['patientAdmissionIds']);

        return $data;
    }

    public function getPatientList()
    {
        /** @var Patient $patients */
        $patients = Patient::with('patientUser')->get()->where('patientUser.status', '=', 1)->pluck('patientUser.full_name', 'id')->sort();

        return $patients;
    }

    public function getAssociateMedicinesList(): array
    {
        $result = Medicine::orderBy('name', 'asc')->get()->pluck('name', 'id')->toArray();
        $medicines = [];
        foreach ($result as $key => $item) {
            $medicines[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $medicines;
    }

    public function getMedicinesList(): array
    {
        $medicine = Medicine::orderBy('name', 'asc')->get()->pluck('name', 'id')->toArray();
        $selectOption = ['0' => 'Select Medicine'];
        $medicine = $selectOption + $medicine;

        return $medicine;
    }

    public function getPatientAdmissionIdList($isEditScreen = false): array
    {
        /** @var PatientAdmission $patientAdmissions */
        $patientAdmissions = PatientAdmission::with('patient.patientUser')->whereTenantId(getLoggedInUser()->tenant_id)->where('status', '=', 1);
        $existingPatientAdmissionIds = Bill::whereTenantId(getLoggedInUser()->tenant_id)->pluck('patient_admission_id')->toArray();

        if ($isEditScreen) {
            $patientAdmissionsResults = $patientAdmissions->whereIn(
                'patient_admission_id',
                $existingPatientAdmissionIds
            )->get();
        } else {
            $patientAdmissionsResults = $patientAdmissions->whereNotIn(
                'patient_admission_id',
                $existingPatientAdmissionIds
            )->get();
        }

        $result = [];
        foreach ($patientAdmissionsResults as $patientAdmissionsResult) {
            $result[$patientAdmissionsResult->patient_admission_id] = $patientAdmissionsResult->patient_admission_id.' '.$patientAdmissionsResult->patient->patientUser->full_name;
        }

        return $result;
    }

    public function saveBill(array $input): Bill
    {
        $billItemInputArray = Arr::only($input, ['item_name', 'qty', 'price']);
        $input['bill_id'] = Bill::generateUniqueBillId();
        /** @var Bill $bill */
        $bill = $this->create($input);
        $totalAmount = 0;

        $billItemInput = $this->prepareInputForBillItem($billItemInputArray);
        foreach ($billItemInput as $key => $data) {
            $validator = Validator::make($data, BillItems::$rules);

            if ($validator->fails()) {
                throw new UnprocessableEntityHttpException($validator->errors()->first());
            }

            $data['amount'] = $data['price'] * $data['qty'];
            $totalAmount += $data['amount'];

            /** @var BillItems $billItem */
            $billItem = new BillItems($data);
            $bill->billItems()->save($billItem);
        }
        $bill->amount = $input['total_amt'];
        $bill->save();

        return $bill;
    }

    public function prepareInputForBillItem(array $input): array
    {
        $items = [];
        foreach ($input as $key => $data) {
            foreach ($data as $index => $value) {
                $items[$index][$key] = $value;
                if (! (isset($items[$index]['price']) && $key == 'price')) {
                    continue;
                }
                $items[$index]['price'] = removeCommaFromNumbers($items[$index]['price']);
            }
        }

        return $items;
    }

    /**
     * @throws Exception
     */
    public function updateBill(int $billId, array $input): Bill
    {
        $billItemInputArr = Arr::only($input, ['item_name', 'qty', 'price', 'id']);

        /** @var Bill $bill */
        $bill = $this->update($input, $billId);
        $totalAmount = 0;

        $billItem = BillItems::whereBillId($billId);
        // $billItem->delete();

        $billItemInput = $this->prepareInputForBillItem($billItemInputArr);
        foreach ($billItemInput as $key => $data) {
            $validator = Validator::make($data, BillItems::$rules);

            if ($validator->fails()) {
                throw new UnprocessableEntityHttpException($validator->errors()->first());
            }

            $data['amount'] = $data['price'] * $data['qty'];
            $billItemInput[$key] = $data;
            $totalAmount += $data['amount'];
        }
        /** @var BillItemsRepository $billItemRepo */
        $billItemRepo = app(BillItemsRepository::class);
        $billItemRepo->updateBillItem($billItemInput, $bill->id);

        $bill->amount = $totalAmount;
        $bill->save();

        return $bill;
    }

    /**
     * @return mixed
     */
    public function patientAdmissionDetails($inputs)
    {
        $patientAdmissionId = $inputs['patient_admission_id'];
        $patientAdmission = PatientAdmission::wherePatientAdmissionId($patientAdmissionId)->first();
        $data['patientDetails'] = $patientAdmission->patient->patientUser;
        $data['doctorName'] = $patientAdmission->doctor->doctorUser->full_name;
        $admissionDate = Carbon::parse($patientAdmission->admission_date);
        $dischargeDate = Carbon::parse($patientAdmission->discharge_date);
        $patientAdmission->totalDays = $admissionDate->diffInDays($dischargeDate) + 1;
        $patientAdmission->insuranceName = isset($patientAdmission->insurance->name) ? $patientAdmission->insurance->name : '';

        if (isset($patientAdmission->package_id)) {
            $package = Package::with('packageServicesItems.service')->findOrFail($patientAdmission->package_id);
            $data['package'] = $package;
        } else {
            $data['package'] = '';
        }
        $data['admissionDetails'] = $patientAdmission;

        if (isset($inputs['editBillId'])) {
            $billGet = Bill::with('billItems')->wherePatientAdmissionId($inputs['patient_admission_id'])->get();
            if (count($billGet) > 0) {
                $data['billItems'] = BillItems::whereBillId($billGet[0]->id)->get();
            } else {
                $data['billItems'] = '';
            }
        }

        return $data;
    }

    /**
     * @return mixed
     */
    public function getSyncListForCreate()
    {
        $data['setting'] = Setting::where('tenant_id', getLoggedInUser()->tenant_id)->pluck('value', 'key')->toArray();

        return $data;
    }

    /**
     * @return mixed
     */
    public function getSyncListForCreateForPDF()
    {
        $data['setting'] = Setting::whereTenantId(getLoggedInUser()->tenant_id)->pluck('value', 'key')->toArray();

        return $data;
    }

    public function saveNotification(array $input)
    {
        $patient = Patient::with('patientUser')->where('id', $input['patient_id'])->first();
        $doctor = Doctor::with('doctorUser')->get()->where('doctorUser.full_name', $input['doctor_id'])->first();
        $receptionists = Receptionist::pluck('user_id', 'id')->toArray();
        $accountants = Accountant::pluck('user_id', 'id')->toArray();
        $userIds = [
            $patient->user_id => Notification::NOTIFICATION_FOR[Notification::PATIENT],
            $doctor->user_id => Notification::NOTIFICATION_FOR[Notification::DOCTOR],
        ];

        foreach ($receptionists as $key => $userId) {
            $userIds[$userId] = Notification::NOTIFICATION_FOR[Notification::RECEPTIONIST];
        }

        foreach ($accountants as $key => $userId) {
            $userIds[$userId] = Notification::NOTIFICATION_FOR[Notification::ACCOUNTANT];
        }
        $adminUser = User::role('Admin')->first();
        $allUsers = $userIds + [$adminUser->id => Notification::NOTIFICATION_FOR[Notification::ADMIN]];
        $users = getAllNotificationUser($allUsers);

        foreach ($users as $key => $notification) {
            if ($notification == Notification::NOTIFICATION_FOR[Notification::PATIENT]) {
                $title = $patient->patientUser->full_name.' your bills has been created.';
            } else {
                $title = $patient->patientUser->full_name.' bills has been created.';
            }

            addNotification([
                Notification::NOTIFICATION_TYPE['Bills'],
                $key,
                $notification,
                $title,
            ]);
        }
    }

    public function phonePePayment($input)
    {
        $amount = $input['amount'];

        $redirectbackurl = route('billing.phonepe.callback').'?'.http_build_query(['input' => $input]);

        $merchantId = getPaymentCredentials('phonepe_merchant_id');
        $merchantUserId = getPaymentCredentials('phonepe_merchant_id');
        $merchantTransactionId = getPaymentCredentials('phonepe_merchant_transaction_id');
        $baseUrl = getPaymentCredentials('phonepe_env') == 'production' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        $saltKey = getPaymentCredentials('phonepe_salt_key');
        $saltIndex = getPaymentCredentials('phonepe_salt_index');
        $callbackurl = route('billing.phonepe.callback').'?'.http_build_query(['input' => $input]);

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
            'amount' => $amount * 100,
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

        $rData = json_decode($response);
        $url = $rData->data->instrumentResponse->redirectInfo->url;

        return redirect()->to($url);
    }

    public function billPhonePePaymentSuccess($input)
    {
        try {
            DB::beginTransaction();

            $bill = Bill::find($input['input']['id']);

            if (! empty($bill)) {

                BillTransaction::create([
                    'transaction_id' => $input['transactionId'],
                    'payment_type' => $input['input']['paymentType'],
                    'amount' => $input['input']['amount'],
                    'bill_id' => $input['input']['id'],
                    'status' => 1,
                    'meta' => null,
                    'is_manual_payment' => 0,
                ]);

                $bill->update(['payment_mode' => 0, 'status' => '1']);
            }
            DB::commit();

            return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
        } catch (Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title(__($e->getMessage()))
                ->danger()
                ->send();
        }

        return false;
    }

    public function flutterWavePayment($input)
    {
        $amount = $input['amount'];
        $transactionRef = time();

        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $amount,
            'email' => getLoggedInUser()->email,
            'tx_ref' => (string) $transactionRef,
            'currency' => strtoupper(getCurrentCurrency()),
            'redirect_url' => route('flutterwave.success'),
            // 'redirect_url' => route('flutterwave.success') . '?' . http_build_query(['input' => $input]),
            'customer' => [
                'email' => getLoggedInUser()->email,
            ],
            'customizations' => [
                'title' => 'Bill',
                'logo' => asset(getLogoUrl()),
            ],
            'meta' => [
                'email' => getLoggedInUser()->email,
                'currency_symbol' => strtoupper(getCurrentCurrency()),
                'patient_bill_id' => $input['id'],
                'payment_type' => $input['paymentType'],
                'amount' => $amount,
            ],
        ];

        $paymentURL = $this->createFlutterwavePaymentLink($data);

        return redirect($paymentURL);
    }

    private function createFlutterwavePaymentLink($data)
    {
        $client = new Client;
        $url = 'https://api.flutterwave.com/v3/payments';

        $flutterwaveSecretKey = getPaymentCredentials('flutterwave_secret_key');

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

        return redirect()->back();
    }

    public function flutterwavePaymentSuccess($input)
    {
        try {
            DB::beginTransaction();

            if ($input['status'] == 'successful') {
                $transactionID = Flutterwave::getTransactionIDFromCallback();
                $data = Flutterwave::verifyTransaction($transactionID);

                $billId = $data['data']['meta']['patient_bill_id'];
                $bill = Bill::find($billId);

                if (! empty($bill)) {
                    BillTransaction::create([
                        'transaction_id' => $data['data']['tx_ref'],
                        'payment_type' => BillTransaction::FLUTTERWAVE,
                        'amount' => $bill->amount,
                        'bill_id' => $bill->id,
                        'status' => 1,
                        'meta' => null,
                        'is_manual_payment' => 0,
                    ]);
                    $bill->update(['payment_mode' => BillTransaction::FLUTTERWAVE, 'status' => '1']);
                }

                DB::commit();
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return false;
    }

    // paystack payment

    public function paystackPayment(Request $request)
    {
        $data = $request->all();

        if (! isset($data['amount'], $data['id'], $data['paymentType'])) {
            return FilamentNotification::make()
                ->danger()
                ->title(__('message.common.something_want_wrong'))
                ->send();
        }

        $reference = 'SUB_' . uniqid();

        $paystackResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.getPaymentCredentials('paystack_secret_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'amount' => $data['amount'] * 100,
            'email' => getLoggedInUser()->email,
            'reference' => $reference,
            'metadata' => json_encode(['id' => $data['id'], 'paymentType' => $data['paymentType'], 'amount' => $data['amount'], 'is_patient_bill_payment' => 1]),
            'order_id' => 'order_'.$data['id'],
        ]);

        if ($paystackResponse->successful()) {
            return redirect($paystackResponse->json()['data']['authorization_url']);
        }

        FilamentNotification::make()
            ->danger()
            ->title($paystackResponse->json()['message'])
            ->send();
    }

    public function razorpayPayment($data)
    {
        $api = new Api(getPaymentCredentials('razorpay_key'), getPaymentCredentials('razorpay_secret'));

        $amount = $data['amount'] * 100;

        // Create a Razorpay order
        $orderData = [
            'amount' => $amount,
            'currency' => 'INR',
            'receipt' => 'receipt_'.$data['id'], // Use record ID as receipt
            'payment_capture' => 1, // Automatically capture payment
        ];
        $order = $api->order->create($orderData);

        return [
            'order_id' => $order->id,
            'amount' => $data['amount'],
            'currency' => 'INR',
            'id' => $data['id'],
        ];
    }

    public function paypalPayment($data)
    {
        session(['bill_id' => $data['id']]);
        $mode = getPaymentCredentials('paypal_mode');
        $clientId = getPaymentCredentials('paypal_client_id');
        $clientSecret = getPaymentCredentials('paypal_secret');

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
                    'reference_id' => $data['id'],
                    'amount' => [
                        'value' => $data['amount'],
                        'currency_code' => 'USD',
                    ],
                ],
            ],
            'application_context' => [
                'cancel_url' => route('paypal.payment.failed').'?error=subscription_failed',
                'return_url' => route('paypal.payment.success'),
            ],
        ];
        $order = $provider->createOrder($data);

        return redirect($order['links'][1]['href']);
    }
}
