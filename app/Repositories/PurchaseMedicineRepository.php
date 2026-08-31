<?php

namespace App\Repositories;

use App\Models\Accountant;
use App\Models\Address;
use App\Models\Category;
use App\Models\Medicine;
use App\Models\PurchasedMedicine;
use App\Models\PurchaseMedicine;
use App\Models\Setting;
use App\Models\SuperAdminSetting;
use App\Models\User;
use Arr;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Stripe\Checkout\Session;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Class AccountantRepository
 *
 * @version February 17, 2020, 5:34 am UTC
 */
class PurchaseMedicineRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'purchase_numeber',
        'purchase_date',
        'bill_number',
        'supplier_name',
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
        return PurchaseMedicine::class;
    }

    public function getMedicine()
    {
        $data['medicines'] = Medicine::all()->pluck('name', 'id')->toArray();

        return $data;
    }

    public function getMedicineList()
    {
        $result = Medicine::all()->pluck('name', 'id')->toArray();

        $medicines = [];
        foreach ($result as $key => $item) {
            $medicines[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $medicines;
    }

    public function getCategoryList()
    {
        $result = Category::all()->pluck('name', 'id')->toArray();

        $category = [];
        foreach ($result as $key => $item) {
            $medicines[] = [
                'key' => $key,
                'value' => $item,
            ];
        }

        return $category;
    }

    public function getCategory()
    {
        $data['categories'] = Category::all()->pluck('name', 'id')->toArray();

        return $data;
    }

    /**
     * @param  bool  $mail
     */
    public function store(array $input)
    {
        try {
            DB::beginTransaction();
            $purchaseMedicineArray = Arr::only($input, $this->model->getFillable());
            $purchaseMedicine = PurchaseMedicine::create($purchaseMedicineArray);

            $structuredMedicines = [
                'medicine' => [],
                'lot_no' => [],
                'expiry_date' => [],
                'sale_price' => [],
                'purchase_price' => [],
                'quantity' => [],
                'tax' => [],
                'amount' => [],

            ];

            foreach ($input['purchasedMedcines'] as $medicineData) {
                $structuredMedicines['medicine'][] = $medicineData['medicine'];
                $structuredMedicines['lot_no'][] = $medicineData['lot_no'];
                $structuredMedicines['expiry_date'][] = $medicineData['expiry_date'];
                $structuredMedicines['sale_price'][] = $medicineData['sale_price'];
                $structuredMedicines['purchase_price'][] = $medicineData['purchase_price'];
                $structuredMedicines['quantity'][] = $medicineData['quantity'];
                $structuredMedicines['tax'][] = $medicineData['tax'];
                $structuredMedicines['amount'][] = $medicineData['amount'];
            }

            foreach ($structuredMedicines['medicine'] as $key => $value) {

                $purchasedMedicineArray = [
                    'purchase_medicines_id' => $purchaseMedicine->id,
                    'medicine_id' => $structuredMedicines['medicine'][$key],
                    'lot_no' => $structuredMedicines['lot_no'][$key],
                    'tax' => $structuredMedicines['tax'][$key],
                    'expiry_date' => $structuredMedicines['expiry_date'][$key],
                    'quantity' => $structuredMedicines['quantity'][$key],
                    'amount' => $structuredMedicines['amount'][$key],
                    'tenant_id',
                ];

                PurchasedMedicine::create($purchasedMedicineArray);
                $medicine = Medicine::find($structuredMedicines['medicine'][$key]);
                $medicineQtyArray = [
                    'quantity' => $structuredMedicines['quantity'][$key] + $medicine->quantity,
                    'available_quantity' => $structuredMedicines['quantity'][$key] + $medicine->available_quantity,
                ];
                $medicine->update($medicineQtyArray);
            }
            DB::commit();

            return $purchaseMedicine;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return bool|Builder|Builder[]|Collection|Model
     */
    public function update($accountant, $input)
    {
        try {
            unset($input['password']);

            /** @var User $user */
            $user = User::find($accountant->user->id);
            if (isset($input['image']) && ! empty($input['image'])) {
                $mediaId = updateProfileImage($user, $input['image']);
            }
            if ($input['avatar_remove'] == 1 && isset($input['avatar_remove']) && ! empty($input['avatar_remove'])) {
                removeFile($user, User::COLLECTION_PROFILE_PICTURES);
            }

            /** @var Accountant $accountant */
            $input['phone'] = preparePhoneNumber($input, 'phone');
            $input['dob'] = (! empty($input['dob'])) ? $input['dob'] : null;
            $accountant->user->update($input);
            $accountant->update($input);

            if (! empty($accountant->address)) {
                if (empty($address = Address::prepareAddressArray($input))) {
                    $accountant->address->delete();
                }
                $accountant->address->update($input);
            } else {
                if (! empty($address = Address::prepareAddressArray($input)) && empty($accountant->address)) {
                    $ownerId = $accountant->id;
                    $ownerType = Accountant::class;
                    Address::create(array_merge($address, ['owner_id' => $ownerId, 'owner_type' => $ownerType]));
                }
            }

            return true;
        } catch (Exception $e) {
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function stripeSession($input)
    {
        $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
        $stripeKey = Setting::whereTenantId($tenantId)->where('key', '=', 'stripe_secret')->first();

        if (! empty($stripeKey->value)) {
            setStripeApiKey($tenantId);
        } else {
            throw new UnprocessableEntityHttpException(__('messages.new_change.provide_stripe_key'));
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'product_data' => [
                                'name' => 'Payment for Purchase Medicine',
                            ],
                            'unit_amount' => in_array(strtoupper(getCurrentCurrency()), zeroDecimalCurrencies()) ? $input['net_amount'] : $input['net_amount'] * 100,
                            'currency' => strtoupper(getCurrentCurrency()),
                        ],
                        'quantity' => 1,
                    ],
                ],
                'client_reference_id' => $input['purchase_no'],
                'mode' => 'payment',
                'success_url' => route('medicine.purchase.stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('medicine.purchase.stripe.failed').'?'.http_build_query(['input' => $input]),
            ]);

            session(['sessionUrl' => $session->url]);

            return;
        } catch (Exception $e) {
            $data['error'] = $e->getMessage();

            return $data;
        }
    }

    public function purchaseMedicinestripeSuccess($input)
    {
        $sessionId = $input['session_id'];
        $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;

        if (empty($sessionId)) {
            throw new UnprocessableEntityHttpException('session_id required');
        }

        $stripeKey = Setting::whereTenantId($tenantId)->where('key', '=', 'stripe_secret')->first();

        if (! empty($stripeKey->value)) {
            setStripeApiKey($tenantId);
        } else {
            throw new UnprocessableEntityHttpException(__('messages.new_change.provide_stripe_key'));
        }

        $sessionData = Session::retrieve($sessionId);

        if ($sessionData) {
            return true;
        }

        return false;
    }

    public function razorPayPayment($input)
    {

        $amount = intval($input['net_amount']);

        $api = new Api(getPaymentCredentials('razorpay_key'), getPaymentCredentials('razorpay_secret'));

        $orderData = [
            'receipt' => '1',
            'amount' => $amount * 100,
            'currency' => strtoupper(getCurrentCurrency()),
            'notes' => [
                'amount' => $amount,
            ],
        ];

        $razorpayOrder = $api->order->create($orderData);
        $data['id'] = $razorpayOrder->id;
        $data['amount'] = $amount;

        return $data;
    }

    public function razorPaySuccess($input)
    {
        $api = new Api(getPaymentCredentials('razorpay_key'), getPaymentCredentials('razorpay_secret'));

        if (count($input) && ! empty($input['razorpay_payment_id'])) {

            $payment = $api->payment->fetch($input['razorpay_payment_id']);

            if ($payment->status == 'authorized') {
                return true;
            }

            return false;
        }
    }

    public function paystackPaymentSuccess($response)
    {
        $input = $response;

        try {
            DB::beginTransaction();

            $purchaseMedicineArray = Arr::only($input, $this->model->getFillable());
            $purchaseMedicine = PurchaseMedicine::create($purchaseMedicineArray);

            foreach ($input['purchasedMedcines'] as $key => $value) {

                $purchasedMedicineArray = [
                    'purchase_medicines_id' => $purchaseMedicine->id,
                    'medicine_id' => $input['purchasedMedcines'][$key]['medicine'],
                    'lot_no' => $input['purchasedMedcines'][$key]['lot_no'],
                    'tax' => $input['purchasedMedcines'][$key]['tax'],
                    'expiry_date' => $input['purchasedMedcines'][$key]['expiry_date'] ?? null,
                    'quantity' => $input['purchasedMedcines'][$key]['quantity'],
                    'amount' => $input['purchasedMedcines'][$key]['amount'],
                    'tenant_id' => getLoggedInUser()->tenant_id,
                ];

                PurchasedMedicine::create($purchasedMedicineArray);
                $medicine = Medicine::find($input['purchasedMedcines'][$key]['medicine']);
                $medicineQtyArray = [
                    'quantity' => $input['purchasedMedcines'][$key]['quantity'] + $medicine->quantity,
                    'available_quantity' => $input['purchasedMedcines'][$key]['quantity'] + $medicine->available_quantity,
                ];
                $medicine->update($medicineQtyArray);
            }
            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    public function phonePePayment($input)
    {
        $amount = $input['net_amount'];

        $redirectbackurl = route('purchase.medicine.phonepe.callback').'?'.http_build_query(['input' => $input]);

        $merchantId = getPaymentCredentials('phonepe_merchant_id');
        $merchantUserId = getPaymentCredentials('phonepe_merchant_id');
        $merchantTransactionId = getPaymentCredentials('phonepe_merchant_transaction_id');
        $baseUrl = getPaymentCredentials('phonepe_env') == 'production' ? 'https://api.phonepe.com/apis/hermes' : 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        $saltKey = getPaymentCredentials('phonepe_salt_key');
        $saltIndex = getPaymentCredentials('phonepe_salt_index');
        $callbackurl = route('purchase.medicine.phonepe.callback').'?'.http_build_query(['input' => $input]);

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

        if ($rData->success == false) {
            $eData['error'] = 'Payment Failed,'.$rData->message;

            return $eData;
        }
        curl_close($curl);
        session(['sessionUrl' => $rData->data->instrumentResponse->redirectInfo->url]);

    }

    public function phonePePaymentSuccess($input)
    {
        $input = $input['input'];

        try {
            DB::beginTransaction();

            $purchaseMedicineArray = Arr::only($input, $this->model->getFillable());
            $purchaseMedicine = PurchaseMedicine::create($purchaseMedicineArray);

            foreach ($input['purchasedMedcines'] as $key => $value) {

                $purchasedMedicineArray = [
                    'purchase_medicines_id' => $purchaseMedicine->id,
                    'medicine_id' => $input['purchasedMedcines'][$key]['medicine'],
                    'lot_no' => $input['purchasedMedcines'][$key]['lot_no'],
                    'tax' => $input['purchasedMedcines'][$key]['tax'],
                    'expiry_date' => $input['purchasedMedcines'][$key]['expiry_date'] ?? null,
                    'quantity' => $input['purchasedMedcines'][$key]['quantity'],
                    'amount' => $input['purchasedMedcines'][$key]['amount'],
                    'tenant_id' => getLoggedInUser()->tenant_id,
                ];

                PurchasedMedicine::create($purchasedMedicineArray);
                $medicine = Medicine::find($input['purchasedMedcines'][$key]['medicine']);
                $medicineQtyArray = [
                    'quantity' => $input['purchasedMedcines'][$key]['quantity'] + $medicine->quantity,
                    'available_quantity' => $input['purchasedMedcines'][$key]['quantity'] + $medicine->available_quantity,
                ];
                $medicine->update($medicineQtyArray);
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }

        return false;
    }

    public function flutterWavePayment($input)
    {
        $reference = time();

        $data = [
            'payment_options' => 'card,banktransfer',
            'amount' => $input['net_amount'],
            'email' => getLoggedInUser()->email,
            'tx_ref' => $reference,
            'currency' => getCurrentCurrency(),
            'redirect_url' => route('purchase.medicine.flutterwave.success'),
            'customer' => [
                'email' => getLoggedInUser()->email,
            ],
            'customizations' => [
                'title' => 'Purchase Medicine Payment',
                'description' => isset($input['payment_note']) ?? '',
            ],
        ];

        $client = new Client;
        $url = 'https://api.flutterwave.com/v3/payments';
        $clientId = getPaymentCredentials('flutterwave_secret_key');

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$clientId,
                'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        $body = json_decode($response->getBody(), true);
        if ($body['status'] == 'success') {
            session()->put('sessionUrl', $body['data']['link']);
        }

    }

    public function flutterWaveSuccess($input)
    {

        try {
            DB::beginTransaction();

            if ($input['status'] == 'successful') {

                $transactionID = $input['transaction_id'];
                $flutterWaveData = $this->verifyPayment($transactionID);

                $sessionData = session()->get('purchaseMedicineDataFlutterwave');

                if (isset($sessionData) && ! empty($sessionData)) {

                    $purchaseMedicineArray = Arr::only($sessionData, $this->model->getFillable());
                    $purchaseMedicine = PurchaseMedicine::create($purchaseMedicineArray);

                    foreach ($sessionData['purchasedMedcines'] as $key => $value) {

                        $purchasedMedicineArray = [
                            'purchase_medicines_id' => $purchaseMedicine->id,
                            'medicine_id' => $sessionData['purchasedMedcines'][$key]['medicine'],
                            'lot_no' => $sessionData['purchasedMedcines'][$key]['lot_no'],
                            'tax' => $sessionData['purchasedMedcines'][$key]['tax'],
                            'expiry_date' => $sessionData['purchasedMedcines'][$key]['expiry_date'],
                            'quantity' => $sessionData['purchasedMedcines'][$key]['quantity'],
                            'amount' => $sessionData['purchasedMedcines'][$key]['amount'],
                        ];

                        PurchasedMedicine::create($purchasedMedicineArray);
                        $medicine = Medicine::find($sessionData['purchasedMedcines'][$key]['medicine']);
                        $medicineQtyArray = [
                            'quantity' => $sessionData['purchasedMedcines'][$key]['quantity'] + $medicine->quantity,
                            'available_quantity' => $sessionData['purchasedMedcines'][$key]['quantity'] + $medicine->available_quantity,
                        ];
                        $medicine->update($medicineQtyArray);
                    }
                }

                DB::commit();
                session()->forget('purchaseMedicineDataFlutterwave');

                return true;
            }
        } catch (Exception $e) {
            DB::rollback();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    private function verifyPayment($transactionID)
    {
        $client = new Client;
        $url = "https://api.flutterwave.com/v3/transactions/{$transactionID}/verify";
        $clientId = SuperAdminSetting::where('key', 'flutterwave_secret')->first()->value;

        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.$clientId,
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function paypalSuccess($input)
    {
        $input = array_merge($input, session('purchaseMedicine'));

        try {
            DB::beginTransaction();
            $purchaseMedicineArray = Arr::only($input, $this->model->getFillable());

            $purchaseMedicine = PurchaseMedicine::create($purchaseMedicineArray);

            foreach ($input['purchasedMedcines'] as $key => $value) {

                $purchasedMedicineArray = [
                    'purchase_medicines_id' => $purchaseMedicine->id,
                    'medicine_id' => $input['purchasedMedcines'][$key]['medicine'],
                    'lot_no' => $input['purchasedMedcines'][$key]['lot_no'],
                    'tax' => $input['purchasedMedcines'][$key]['tax'],
                    'expiry_date' => $input['purchasedMedcines'][$key]['expiry_date'] ?? null,
                    'quantity' => $input['purchasedMedcines'][$key]['quantity'],
                    'amount' => $input['purchasedMedcines'][$key]['amount'],
                    'tenant_id' => getLoggedInUser()->tenant_id,
                ];

                PurchasedMedicine::create($purchasedMedicineArray);
                $medicine = Medicine::find($input['purchasedMedcines'][$key]['medicine']);
                $medicineQtyArray = [
                    'quantity' => $input['purchasedMedcines'][$key]['quantity'] + $medicine->quantity,
                    'available_quantity' => $input['purchasedMedcines'][$key]['quantity'] + $medicine->available_quantity,
                ];
                $medicine->update($medicineQtyArray);
            }
            session()->forget('purchaseMedicine');
            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }
}
