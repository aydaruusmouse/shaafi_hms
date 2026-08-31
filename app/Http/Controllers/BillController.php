<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillTransaction;
use App\Models\User;
use App\Repositories\BillRepository;
use Exception;
use Filament\Notifications\Notification;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDF;
use Razorpay\Api\Api;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BillController extends Controller
{
    private $billRepository;

    public function __construct(BillRepository $billRepo)
    {
        $this->billRepository = $billRepo;
    }

    public function flutterwavePaymentSuccess(Request $request)
    {
        $flutterwavePublicKey = getPaymentCredentials('flutterwave_public_key');
        $flutterwaveSecretKey = getPaymentCredentials('flutterwave_secret_key');

        if (! $flutterwavePublicKey && ! $flutterwaveSecretKey) {
            return Notification::make()
                ->title(__('messages.flutterwave.set_flutterwave_credential'))
                ->danger()
                ->send();
        }

        config([
            'flutterwave.publicKey' => $flutterwavePublicKey,
            'flutterwave.secretKey' => $flutterwaveSecretKey,
        ]);

        if ($request['status'] == 'cancelled') {
            Notification::make()
                ->title(__('messages.payment.payment_failed'))
                ->danger()
                ->send();

            return redirect()->back();
        }

        try {
            $transactionID = $request->transaction_id;

            DB::beginTransaction();
            if ($request->status == 'successful') {
                $data = $this->verifyPayment($transactionID);

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
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
    }

    private function verifyPayment($transactionID)
    {
        $client = new Client;
        $url = "https://api.flutterwave.com/v3/transactions/{$transactionID}/verify";
        $response = $client->get($url, [
            'headers' => [
                'Authorization' => 'Bearer '.getPaymentCredentials('flutterwave_secret_key'),
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function billPhonePePaymentSuccess(Request $request)
    {
        app(BillRepository::class)->billPhonePePaymentSuccess($request->all());

        Notification::make()
            ->title(__('messages.payment.your_payment_is_successfully_completed'))
            ->success()
            ->send();

        return redirect()->back();
    }

    public function paymentSuccess(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (empty($sessionId)) {
            throw new UnprocessableEntityHttpException(__('messages.bill.session_id_required'));
        }
        $tenantId = User::findOrFail(getLoggedInUserId())->tenant_id;
        setStripeApiKey($tenantId);

        $sessionData = \Stripe\Checkout\Session::retrieve($sessionId);
        $bill = Bill::find($sessionData->client_reference_id);

        if (! empty($bill)) {
            BillTransaction::create([
                'transaction_id' => $sessionData->id,
                'payment_type' => 0,
                'amount' => $bill->amount,
                'bill_id' => $bill->id,
                'status' => 1,
                'meta' => null,
                'is_manual_payment' => 0,
            ]);
            $bill->update(['payment_mode' => 0, 'status' => '1']);
        }

        return redirect(route('filament.hospitalAdmin.billings.resources.bills.index'));
    }

    public function convertToPdf(Bill $bill)
    {
        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $bill->billItems;
        $data = $this->billRepository->getSyncListForCreate($bill->id);
        $data['bill'] = $bill;
        $imageData = Http::get($data['setting']['app_logo'])->body();
        $imageType = pathinfo($data['setting']['app_logo'], PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);

        $data['setting']['app_logo'] = $base64Image;

        $pdf = PDF::loadView('bills.bill_pdf', $data);

        return $pdf->stream('bill.pdf');
    }

    public function razorPayPaymentSuccess(Request $request)
    {
        $api = new Api(getPaymentCredentials('razorpay_key'), getPaymentCredentials('razorpay_secret'));

        if (isset($request->razorpay_payment_id) && ! empty($request->razorpay_payment_id)) {
            $payment = $api->payment->fetch($request->razorpay_payment_id);
            $response = $payment->capture(['amount' => $payment->amount]);

            $bill = Bill::find($response->notes->bill_id);
            if ($response->status == 'captured') {
                BillTransaction::create([
                    'transaction_id' => $response->id,
                    'payment_type' => 5,
                    'amount' => $response->amount / 100,
                    'bill_id' => $bill->id,
                    'status' => 1,
                    'meta' => null,
                    'is_manual_payment' => 0,
                ]);

                $bill->update(['payment_mode' => BillTransaction::RAZORPAY, 'status' => '1']);

                Notification::make()
                    ->title(__('messages.payment.your_payment_is_successfully_completed'))
                    ->success()
                    ->send();

                return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
            } else {
                Notification::make()
                    ->title(__('messages.payment.payment_failed'))
                    ->danger()
                    ->send();

                return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
            }
        }
    }

    public function razorPayPaymentFailed()
    {
        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
    }

    public function razorPayPaymentForm($data)
    {
        return view('razorpay.payment_form', $data);
    }

    public function paypalPaymentSuccess(Request $request)
    {
        if (isset($request->token) && ! empty($request->token) && isset($request->PayerID) && ! empty($request->PayerID)) {
            $billId = session('bill_id');

            $bill = Bill::find($billId);

            BillTransaction::create([
                'transaction_id' => $request->PayerID,
                'payment_type' => 6,
                'amount' => $bill->amount,
                'bill_id' => $bill->id,
                'status' => 1,
                'meta' => null,
                'is_manual_payment' => 0,
            ]);

            session()->forget('bill_id');
            $bill->update(['payment_mode' => BillTransaction::PAYPAL, 'status' => '1']);

            Notification::make()
                ->title(__('messages.payment.your_payment_is_successfully_completed'))
                ->success()
                ->send();

            return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
        } else {
            session()->forget('bill_id');

            Notification::make()
                ->title(__('messages.payment.payment_failed'))
                ->danger()
                ->send();

            return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
        }
    }

    public function paypalPaymentFailed()
    {
        session()->forget('bill_id');

        Notification::make()
            ->title(__('messages.payment.payment_failed'))
            ->danger()
            ->send();

        return redirect()->route('filament.hospitalAdmin.billings.resources.bills.index');
    }
}
