<?php

use App\Models\BloodBank;
use App\Models\CurrencySetting;
use App\Models\Doctor;
use App\Models\DoctorDepartment;
use App\Models\FrontSetting;
use App\Models\Invoice;
use App\Models\IpdBill;
use App\Models\IpdCharge;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPayment;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Patient;
use App\Models\PatientAdmission;
use App\Models\PatientCase;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SuperAdminCurrencySetting;
use App\Models\SuperAdminSetting;
use App\Models\User;
use App\Models\ZoomOAuth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Stripe\Stripe;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

function regionCode($regionCode)
{
    $code = str_replace('+', '', $regionCode);

    return '+' . substr($code, 0, 2);
}

function preparePhoneNumber($input, $key)
{
    return (! empty($input[$key])) ? '+' . $input['prefix_code'] . $input[$key] : null;
}

function getSuperAdminSettingValue()
{
    return SuperAdminSetting::all()->keyBy('key');
}

function getAdminCurrencySymbol($currencyCode)
{
    $currenciesData = SuperAdminCurrencySetting::pluck('currency_icon', DB::raw('LOWER(currency_code)'))->toArray();

    return $currenciesData[$currencyCode] ?? '';
}
function getAvatarUrl()
{
    return 'https://ui-avatars.com/api/';
}
function getRandomColor($userId)
{
    $colors = ['329af0', 'fc6369', 'ffaa2e', '42c9af', '7d68f0'];
    $index = $userId % 5;

    return $colors[$index];
}

function getUserImageInitial($userId, $name)
{
    return getAvatarUrl() . "?name=$name&size=100&rounded=true&color=fff&background=" . getRandomColor($userId);
}

function displayPatientEmail(?string $email): string
{
    if (blank($email) || str_ends_with(strtolower($email), '@noemail.local')) {
        return __('messages.common.n/a');
    }

    return $email;
}

function pdfImageToBase64(?string $url): string
{
    if (blank($url)) {
        return '';
    }

    $relativePath = $url;
    if (preg_match('#^https?://#i', $url)) {
        $relativePath = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    }

    $candidates = [
        public_path($relativePath),
        public_path(ltrim($url, '/')),
        storage_path('app/public/'.ltrim($relativePath, '/')),
    ];

    $path = null;
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            $path = $candidate;
            break;
        }
    }

    if (! $path) {
        return '';
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'png');
    $mime = $extension === 'jpg' ? 'jpeg' : $extension;

    return 'data:image/'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
}

function getCurrentLoginUserLanguageName()
{
    return Auth::user()->language;
}

function getSuperAdminAppName()
{
    static $appName;

    if (empty($appName)) {
        $appName = SuperAdminSetting::where('key', '=', 'app_name')->first()->value;
    }

    return $appName;
}

if (! function_exists('getBillPaymentType')) {

    function getBillPaymentType()
    {
        $billPaymentTypes = [];

        $stripe = getPaymentCredentials('stripe_enable');
        $phonepe = getPaymentCredentials('phone_pe_enable');
        $flutterWave = getPaymentCredentials('flutterwave_enable');
        $paystack = getPaymentCredentials('paystack_enable');
        $razorpay = getPaymentCredentials('razorpay_enable');
        $paypal = getPaymentCredentials('paypal_enable');

        if (! empty($stripe) && $stripe) {
            $billPaymentTypes[0] = 'Stripe';
        }

        if (! empty($phonepe) && $phonepe) {
            $billPaymentTypes[1] = 'PhonePe';
        }
        if (! empty($flutterWave) && $flutterWave) {
            $billPaymentTypes[3] = 'FlutterWave';
        }

        if (! empty($paystack) && $paystack) {
            $billPaymentTypes[4] = 'Paystack';
        }

        if (! empty($razorpay) && $razorpay) {
            $billPaymentTypes[5] = 'Razorpay';
        }

        if (! empty($paypal) && $paypal) {
            $billPaymentTypes[6] = 'Paypal';
        }

        $billPaymentTypes[2] = 'Cash';

        return $billPaymentTypes;
    }
}

function getSuperAdminAppLogoUrl()
{
    static $appLogo;

    if (empty($appLogo)) {
        $appLogo = SuperAdminSetting::where('key', '=', 'app_logo')->first();
    }

    return $appLogo->logo_url;
}

function getLogoUrl()
{
    static $appLogo;

    if (empty($appLogo)) {
        $appLogo = Setting::where('key', '=', 'app_logo')
            ->when(
                ! empty(getLoggedInUser()) && ! empty(getLoggedInUser()->tenant_id),
                function ($query) {
                    $query->where('tenant_id', getLoggedInUser()->tenant_id);
                }
            )->first();
    }

    return $appLogo->value ?? '';
}

function getAppName()
{
    static $appName;

    if (empty($appName)) {
        $appName = Setting::where('key', '=', 'app_name')
            ->when(
                ! empty(getLoggedInUser()) && ! empty(getLoggedInUser()->tenant_id),
                function ($query) {
                    $query->where('tenant_id', getLoggedInUser()->tenant_id);
                }
            )->first()->value ?? '';
    }

    return $appName;
}

function getAdminCurrencyFormat($currency, $amount): string
{
    $currencies = array_keys(Gerardojbaez\Money\Currency::getAllCurrencies());
    $is_valid_currency = in_array(strtoupper($currency), $currencies);

    if ($is_valid_currency) {
        $money = new Gerardojbaez\Money\Money($amount, strtoupper($currency));
        $curr = new Gerardojbaez\Money\Currency(strtoupper($currency));

        if ($curr->getSymbolPlacement() == 'after') {
            $value = $money->amount() . getAdminCurrencySymbol($currency);
        } else {
            $value = getAdminCurrencySymbol($currency) . $money->amount();
        }

        return $value;
    }

    return getAdminCurrencySymbol($currency) . ' ' . number_format($amount, 2);
}

function totalAmount()
{
    $totalSum = 0;
    $amount = Invoice::whereTenantId(getLoggedInUser()->tenant_id)->get();

    foreach ($amount as $amounts) {
        $total = 0;
        if ($amounts['discount'] != 0) {
            $total += $amounts['amount'] - ($amounts['amount'] * $amounts['discount'] / 100);
        } else {
            $totalSum += $amounts['amount'];
        }

        $totalSum += $total;
    }

    return (string) max(0, $totalSum);
}
function getFrontSettingValue($type, $key)
{
    $data = FrontSetting::whereType($type)
        ->where('key', $key)
        ->when(
            ! empty(getLoggedInUser()) && ! empty(getLoggedInUser()->tenant_id),
            function ($query) {
                $query->where('tenant_id', getLoggedInUser()->tenant_id);
            }
        )
        ->value('value');

    return $data;
}

function canAccessRecord($model, $id)
{
    $recordExists = $model::where('id', $id)->exists();

    if ($recordExists) {
        return true;
    }

    return false;
}
function getSettingValue()
{
    static $settingValue;

    if (empty($settingValue)) {
        $settingValue = Setting::when(
            ! empty(getLoggedInUser()) && ! empty(getLoggedInUser()->tenant_id),
            function ($query) {
                $query->where('tenant_id', getLoggedInUser()->tenant_id);
            }
        )->get()->keyBy('key');
    }

    return $settingValue;
    // return Setting::whereTenantId(getLoggedInUser()->tenant_id)->get()->keyBy('key');
}
function getBloodGroups()
{
    return BloodBank::whereTenantId(getLoggedInUser()->tenant_id)->orderBy('blood_group')->pluck('blood_group', 'blood_group')->toArray();
}
function getLoggedInUser()
{
    return Auth::user();
}

function getCurrentPlanDetails()
{
    $currentSubscription = currentActiveSubscription();
    $isExpired = $currentSubscription->isExpired();
    $currentPlan = $currentSubscription->subscriptionPlan;

    if ($currentPlan->price != $currentSubscription->plan_amount) {
        $currentPlan->price = $currentSubscription->plan_amount;
    }

    $startsAt = Carbon::now();
    $totalDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($currentSubscription->ends_at);
    $usedDays = Carbon::parse($currentSubscription->starts_at)->diffInDays($startsAt);
    $remainingDays = $totalDays - $usedDays;

    $frequency = $currentSubscription->plan_frequency == SubscriptionPlan::MONTH ? 'Monthly' : 'Yearly';

    $days = $currentSubscription->plan_frequency == SubscriptionPlan::MONTH ? 30 : 365;

    $perDayPrice = round($currentPlan->price / $days, 2);

    if (checkIfPlanIsInTrial($currentSubscription)) {
        $remainingBalance = 0.00;
        $usedBalance = 0.00;
    } else {
        $remainingBalance = $currentPlan->price - ($perDayPrice * $usedDays);
        $usedBalance = $currentPlan->price - $remainingBalance;
    }

    return [
        'name' => $currentPlan->name . ' / ' . $frequency,
        'trialDays' => $currentPlan->trial_days,
        'startAt' => Carbon::parse($currentSubscription->starts_at)->translatedFormat('jS M, Y'),
        'endsAt' => Carbon::parse($currentSubscription->ends_at)->translatedFormat('jS M, Y'),
        'usedDays' => $usedDays,
        'remainingDays' => $remainingDays,
        'totalDays' => $totalDays,
        'usedBalance' => $usedBalance,
        'remainingBalance' => $remainingBalance,
        'isExpired' => $isExpired,
        'currentPlan' => $currentPlan,
    ];
}

function getProratedPlanData($planIDChosenByUser)
{
    /** @var SubscriptionPlan $subscriptionPlan */
    $subscriptionPlan = SubscriptionPlan::findOrFail($planIDChosenByUser);
    $newPlanDays = $subscriptionPlan->frequency == SubscriptionPlan::MONTH ? 30 : 365;

    $currentSubscription = currentActiveSubscription();
    $frequency = $subscriptionPlan->frequency == SubscriptionPlan::MONTH ? 'Monthly' : 'Yearly';

    $startsAt = Carbon::now();

    $carbonParseStartAt = Carbon::parse($currentSubscription->starts_at);
    $usedDays = $carbonParseStartAt->copy()->diffInDays($startsAt);
    $totalExtraDays = 0;
    $totalDays = $newPlanDays;

    $endsAt = Carbon::now()->addDays($newPlanDays);

    $startsAt = $startsAt->copy()->format('jS M, Y');
    if ($usedDays <= 0) {
        $startsAt = $carbonParseStartAt->copy()->format('jS M, Y');
    }

    if (! $currentSubscription->isExpired() && ! checkIfPlanIsInTrial($currentSubscription)) {
        $amountToPay = 0;

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

        $remainingBalance = round($planPrice - ($perDayPrice * $usedDays), 2);

        if ($remainingBalance < $subscriptionPlan->price) { // adjust the amount in plan
            $amountToPay = round($subscriptionPlan->price - $remainingBalance, 2);
        } else {
            $endsAt = Carbon::now();
            $perDayPriceOfNewPlan = round($subscriptionPlan->price / $newPlanDays, 2);
            $totalExtraDays = round($remainingBalance / $perDayPriceOfNewPlan);

            $endsAt = $endsAt->copy()->addDays($totalExtraDays);
            $totalDays = $totalExtraDays;
        }

        return [
            'startDate' => $startsAt,
            'name' => $subscriptionPlan->name . ' / ' . $frequency,
            'trialDays' => $subscriptionPlan->trial_days,
            'remainingBalance' => $remainingBalance,
            'endDate' => $endsAt->format('jS M, Y'),
            'amountToPay' => $amountToPay,
            'usedDays' => $usedDays,
            'totalExtraDays' => $totalExtraDays,
            'totalDays' => $totalDays,
        ];
    }

    return [
        'name' => $subscriptionPlan->name . ' / ' . $frequency,
        'trialDays' => $subscriptionPlan->trial_days,
        'startDate' => $startsAt,
        'endDate' => $endsAt->format('jS M, Y'),
        'remainingBalance' => 0,
        'amountToPay' => $subscriptionPlan->price,
        'usedDays' => $usedDays,
        'totalExtraDays' => $totalExtraDays,
        'totalDays' => $totalDays,
    ];
}

function superAdminStripeApiKey()
{
    $secretKey = '';
    $stripeKey = getSuperAdminSettingKeyValue('stripe_key');
    $stripeSecret = getSuperAdminSettingKeyValue('stripe_secret');
    if (isset($stripeKey) && ! is_null($stripeKey) && isset($stripeSecret) && ! is_null($stripeSecret)) {
        $secretKey = getSuperAdminSettingKeyValue('stripe_secret');
    } else {
        $secretKey = config('services.stripe.key');
    }

    return $secretKey;
}

function storeAttachments($user, $attachment)
{
    $media = $user->addMedia($attachment)
        ->toMediaCollection(User::COLLECTION_MAIL_ATTACHMENTS, config('app.media_disk'));

    return $media;
}

function canDeletePayroll($model, $columnName, $id, $ownerType)
{
    $result = $model::where($columnName, $id)->where('owner_type', $ownerType)->exists();
    if ($result) {
        return true;
    }

    return false;
}

function canDelete($models, $columnName, $id)
{
    foreach ($models as $model) {
        $result = $model::where($columnName, $id)->exists();
        if ($result) {
            return true;
        }
    }

    return false;
}
function removeCommaFromNumbers($number)
{
    return (gettype($number) == 'string' && ! empty($number)) ? str_replace(',', '', $number) : $number;
}
function getCurrencySymbol()
{
    $currenciesData = CurrencySetting::all();

    return collect($currenciesData)->where(
        'currency_code',
        strtoupper(getCurrentCurrency())
    )->pluck('currency_icon')->first();
}

if (! function_exists('getIpdPaymentTypes')) {
    function getIpdPaymentTypes()
    {
        $ipdPaymentTypes = [];
        $stripe = getPaymentCredentials('stripe_enable');
        $payPal = getPaymentCredentials('paypal_enable');
        $razorpay = getPaymentCredentials('razorpay_enable');
        // $paytm = getPaymentCredentials('paytm_enable');
        $payStack = getPaymentCredentials('paystack_enable');
        $phonePay = getPaymentCredentials('phone_pe_enable');
        $flutterWave = getPaymentCredentials('flutterwave_enable');

        $ipdPaymentTypes[1] = 'Cash';
        $ipdPaymentTypes[2] = 'Cheque';

        if (! empty($stripe) && $stripe) {
            $ipdPaymentTypes[3] = 'Stripe';
        }
        if (! empty($payPal) && $payPal) {
            $ipdPaymentTypes[4] = 'Paypal';
        }
        if (! empty($razorpay) && $razorpay) {
            $ipdPaymentTypes[5] = 'Razorpay';
        }
        // if(!empty($paytm) && $paytm){
        //     $ipdPaymentTypes[6] = 'Paytm';
        // }
        if (! empty($payStack) && $payStack) {
            $ipdPaymentTypes[7] = 'PayStack';
        }
        if (! empty($phonePay) && $phonePay) {
            $ipdPaymentTypes[8] = 'PhonePe';
        }
        if (! empty($flutterWave) && $flutterWave) {
            $ipdPaymentTypes[9] = 'FlutterWave';
        }

        return $ipdPaymentTypes;
    }
}

function formatCurrency($currencyValue)
{
    $isIndianCur = getCurrencySymbol() == '₹';

    $amountValue = $currencyValue;
    $precision = 2;
    if ($amountValue < 900) {
        // 0 - 900
        $numberFormat = number_format($amountValue, $precision);
        $suffix = '';
    } else {
        if ($amountValue < 900000) {
            // 0.9k-850k
            $numberFormat = number_format($amountValue / 1000, $precision);
            $suffix = 'K';
        } else {
            if ($amountValue < 900000000) {
                // 0.9m-850m
                $numberFormat = number_format($amountValue / 1000000, $precision);
                $suffix = 'M';
            } else {
                if ($amountValue < 900000000000) {
                    // 0.9b-850b
                    $numberFormat = number_format($amountValue / 1000000000, $precision);
                    $suffix = 'B';
                } else {
                    // 0.9t+
                    $numberFormat = number_format($amountValue / 1000000000000, $precision);
                    $suffix = 'T';
                }
            }
        }
    }

    // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
    // Intentionally does not affect partials, eg "1.50" -> "1.50"
    if ($precision > 0) {
        $dotZero = '.' . str_repeat('0', $precision);
        $numberFormat = str_replace($dotZero, '', $numberFormat);
    }

    //  return $formattedAmount.' '.$currencySuffix;

    return $numberFormat . $suffix;
}

function getCurrentCurrency()
{
    /** @var Setting $currentCurrency */
    static $currentCurrency;

    if (getLoggedInUser()) {
        $currentCurrency = Setting::where('key', 'current_currency')->Where('tenant_id', getLoggedInUser()->tenant_id)->first();
    } else {
        if (empty($currentCurrency)) {
            $currentCurrency = Setting::where('key', 'current_currency')->first();
        }
    }

    // this changes is like that before
    // --> return $currentCurrency->value;
    return $currentCurrency->value ?? '';
}

function getSchedulesTimingSlot()
{
    $period = new CarbonPeriod('00:00', '15 minutes', '24:00'); // for create use 24 hours format later change format
    $slots = [];
    foreach ($period as $item) {
        $slots[$item->format('H:i')] = $item->format('H:i');
    }

    return $slots;
}

function getCurrencyFormat($amount): string
{
    $currency = getCurrentCurrency();
    $currencies = array_keys(Gerardojbaez\Money\Currency::getAllCurrencies());
    $is_valid_currency = in_array(strtoupper($currency), $currencies);

    if ($is_valid_currency) {
        $money = new Gerardojbaez\Money\Money($amount, strtoupper($currency));
        $curr = new Gerardojbaez\Money\Currency(strtoupper($currency));

        if ($curr->getSymbolPlacement() == 'after') {
            $value = $money->amount() . ' ' . getCurrencySymbol();
        } else {
            $value = getCurrencySymbol() . ' ' . $money->amount();
        }

        return $value;
    }

    return getCurrencySymbol() . ' ' . number_format((float) $amount, 2);
}

function getNotificationIcon($notificationFor)
{
    switch ($notificationFor) {
        case 1:
            return 'fas-calendar-check';
        case 2:
            return 'fas-file-invoice';
        case 3:
            return 'fas-rupee-sign';
        case 7:
        case 4:
            return 'fas-notes-medical';
        case 5:
            return 'fas-stethoscope';
        case 8:
        case 6:
            return 'fas-prescription';
        case 9:
            return 'fas-diagnoses';
        case 10:
            return 'fas-chart-pie';
        case 11:
            return 'fas-money-bill-wave';
        case 12:
            return 'fas-user-injured';
        case 13:
            return 'fas-briefcase-medical';
        case 14:
            return 'fas-users';
        case 15:
            return 'fas-clipboard';
        case 16:
            return 'fas-user-plus';
        case 17:
            return 'fas-ambulance';
        case 18:
            return 'fas-box';
        case 19:
            return 'fas-wallet';
        case 20:
            return 'fas-money-check';
        case 21:
            return 'fas-video';
        case 22:
            return 'fas-file-video';
        default:
            return 'fas-inbox';
    }
}

function getAllNotificationUser($data)
{
    return array_filter($data, function ($key) {
        return $key != getLoggedInUserId();
    }, ARRAY_FILTER_USE_KEY);
}

function getLoggedInUserId()
{
    return Auth::id();
}

function getUniqueNameValidation($currentModel, $record, $data, $action, bool $isEdit, bool $isPage = false, ?string $error = null)
{
    if ($record) {
        $isExist = $currentModel::whereTenantId(getLoggedInUser()->tenant_id)->where('id', '!=', $record->id)->where('name', $data['name'])->exists();
    } else {
        $isExist = $currentModel::whereTenantId(getLoggedInUser()->tenant_id)->where('name', $data['name'])->exists();
    }
    if ($isExist) {
        FilamentNotification::make()
            ->danger()
            ->title(($error ?? __('messages.user.name')) . ' ' . __('messages.common.is_already_exists'))
            ->send();
        if ($isPage) {
            $action->halt;
        }
        if ($isEdit) {
            $action->halt();
        } else {
            $action->halt;
        }
    }
}

function getUniqueCodeValidation($currentModel, $record, $data, $action, bool $isEdit)
{
    if ($isEdit) {
        $isExist = $currentModel::whereTenantId(getLoggedInUser()->tenant_id)->where('id', '!=', $record->id)->where('code', $data['code'])->exists();
    } else {
        $isExist = $currentModel::whereTenantId(getLoggedInUser()->tenant_id)->where('code', $data['code'])->exists();
    }
    if ($isExist) {
        FilamentNotification::make()
            ->danger()
            ->title(__('messages.user.name') . ' ' . __('messages.common.is_already_exists'))
            ->send();
        $action->halt();
    }
}

function getSuperAdminStripeApiKey()
{
    $secretKey = '';
    $stripeKey = getSuperAdminSettingKeyValue('stripe_key');
    $stripeSecret = getSuperAdminSettingKeyValue('stripe_secret');
    if (isset($stripeKey) && ! is_null($stripeKey) && isset($stripeSecret) && ! is_null($stripeSecret)) {
        $secretKey = getSuperAdminSettingKeyValue('stripe_key');
    } else {
        $secretKey = config('services.stripe.secret_key');
    }

    return $secretKey;
}

function addNotification($data)
{
    $notificationRecord = [
        'type' => $data[0],
        'user_id' => $data[1],
        'notification_for' => $data[2],
        'title' => $data[3],
    ];

    if ($user = User::find($data[1])) {
        Notification::create($notificationRecord);
    }
}

function formatDaysAgo(Carbon $date)
{
    $now = Carbon::now();
    $diffInDays = round($date->diffInDays($now, false));

    // Check if the date is in the past or future
    if ($date->isPast()) {
        return "$diffInDays days ago";
    } else {
        return "In $diffInDays days";
    }
}

function formatMonthsAgo(Carbon $date)
{
    $now = Carbon::now();
    $diffInMonths = round($date->diffInMonths($now, false));

    // Check if the date is in the past or future
    if ($date->isPast()) {
        return "$diffInMonths months ago";
    } else {
        return "In $diffInMonths months";
    }
}

function getLoggedinPatient()
{
    return Auth::user()->hasRole(['Patient']);
}

function getWeekDate(): string
{
    $date = Carbon::now();
    $startOfWeek = $date->startOfWeek()->subDays(1);
    $startDate = $startOfWeek->format('Y-m-d');
    $endOfWeek = $startOfWeek->addDays(6);
    $endDate = $endOfWeek->format('Y-m-d');

    return $startDate . ' - ' . $endDate;
}

function getPaymentCredentials($key)
{
    $credentialValue = '';

    $query = Setting::whereTenantId(getLoggedInUser()->tenant_id)->pluck('value', 'key')->toArray();

    if (! empty($query)) {
        if (isset($query[$key])) {
            $credentialValue = $query[$key];
        }
    }

    return $credentialValue;
}

if (! function_exists('getPurchaseMedicinePaymentTypes')) {
    function getPurchaseMedicinePaymentTypes()
    {
        $paymentTypeArray = [];
        $stripeCheck = getPaymentCredentials('stripe_enable');
        $razorpayCheck = getPaymentCredentials('razorpay_enable');
        $paystackCheck = getPaymentCredentials('paystack_enable');
        $phonePe = getPaymentCredentials('phone_pe_enable');
        $payPal = getPaymentCredentials('paypal_enable');
        $flutterWave = getPaymentCredentials('flutterwave_enable');

        $paymentTypeArray[0] = __('messages.transaction_filter.cash');

        $paymentTypeArray[1] = __('messages.transaction_filter.cheque');

        if (! empty($stripeCheck)) {
            $paymentTypeArray[5] = __('messages.transaction_filter.stripe');
        }
        if (! empty($razorpayCheck)) {
            $paymentTypeArray[2] = __('messages.transaction_filter.razorpay');
        }
        if (! empty($paystackCheck)) {
            $paymentTypeArray[3] = __('messages.transaction_filter.paystack');
        }
        if (! empty($phonePe)) {
            $paymentTypeArray[4] = 'PhonePe';
        }
        if (! empty($flutterWave)) {
            $paymentTypeArray[6] = 'FlutterWave';
        }

        if (! empty($payPal)) {
            $paymentTypeArray[7] = 'Paypal';
        }

        return $paymentTypeArray;
    }
}

if (! function_exists('getPurchaseMedicineManualPaymentTypes')) {
    function getPurchaseMedicineManualPaymentTypes()
    {
        $paymentTypeArray = [];

        $paymentTypeArray[0] = __('messages.transaction_filter.cash');

        $paymentTypeArray[1] = __('messages.transaction_filter.cheque');

        return $paymentTypeArray;
    }
}

function generateUniquePurchaseNumber()
{
    do {
        $code = random_int(100000, 999999);
    } while (\App\Models\PurchaseMedicine::where('purchase_no', '=', $code)->first());

    return $code;
}

function getDoctorDepartment($doctorDepartmentId)
{
    return DoctorDepartment::where('id', $doctorDepartmentId)->value('title');
}

function generateUniqueBillNumber()
{
    do {
        $code = random_int(1000, 9999);
    } while (\App\Models\MedicineBill::where('bill_number', '=', $code)->first());

    return $code;
}

function isZoomTokenExpire()
{
    $isExpired = false;
    $zoomOAuth = ZoomOAuth::where('user_id', Auth::id())->first();
    $currentTime = Carbon::now();

    if (! isset($zoomOAuth) || $zoomOAuth->updated_at < $currentTime->subMinutes(57)) {
        $isExpired = true;
    }

    return $isExpired;
}

function getLoggedinDoctor()
{
    return Auth::user()->hasRole(['Doctor']);
}

if (! function_exists('getGoogleJsonFilePath')) {
    function getGoogleJsonFilePath()
    {
        $googleJsonFilePath = Doctor::whereUserId(Auth::id())->value('google_json_file_path');

        if (! empty($googleJsonFilePath)) {
            return $googleJsonFilePath;
        }

        return null;
    }
}

function getCurrentActiveSubscriptionPlan()
{
    if (! Auth::user()) {
        return null;
    }

    return Subscription::where('status', Subscription::ACTIVE)
        ->where('user_id', \Auth::user()->id)
        ->first();
}

function setPlanFeatures()
{
    $features = currentActiveSubscription()->subscriptionPlan->features->pluck('name', 'name')->toArray();

    $moduleMappings = [
        'Appointments' => ['Appointments'],
        'Blood Banks' => ['Blood Banks', 'Blood Donations', 'Blood Donors', 'Blood Issues'],
        'Documents' => ['Documents', 'Document Types'],
        'Live Consultations' => ['Live Consultations', 'Live Meetings'],
        'Inventory' => ['Issued Items', 'Item Stocks', 'Items', 'Items Categories'],
        'Vaccinations' => ['Vaccinations', 'Vaccinated Patients'],
        'SMS / Mail' => ['SMS', 'Mail'],
        'Radiology' => ['Radiology Tests', 'Radiology Categories'],
        'Reports' => ['Operation Reports', 'Investigation Reports', 'Death Reports', 'Birth Reports'],
        'Pathology' => ['Pathology Tests', 'Pathology Categories', 'Pathology Parameters', 'Pathology Units'],
    ];

    Module::where('tenant_id', getLoggedInUser()->tenant_id)->update(['is_active' => 1, 'is_hidden' => 0]);

    foreach ($moduleMappings as $key => $modules) {
        if (! array_key_exists($key, $features)) {
            Module::where('tenant_id', getLoggedInUser()->tenant_id)
                ->whereIn('name', $modules)
                ->update([
                    'is_active' => 0,
                    'is_hidden' => 1,
                ]);
        }
    }
}

function currentActiveSubscription()
{
    if (! Auth::user()) {
        return null;
    }

    return Subscription::with(['subscriptionPlan', 'user'])
        ->where('status', Subscription::ACTIVE)
        ->where('user_id', \Auth::user()->id)
        ->whereHas('user', function ($query) {
            $query->where('tenant_id', \Auth::user()->tenant_id);
        })
        ->first();
}

/**
 * @return bool
 */
function isAuth()
{
    return Auth::check() ? true : false;
}

/**
 * @return string
 */
function getParseDate($date)
{
    return Carbon::parse($date);
}

function getSuperAdminPaymentCredentials($key)
{
    $credentialValue = '';

    $query = SuperAdminSetting::pluck('value', 'key')->toArray();
    if (! empty($query)) {
        $credentialValue = $query[$key];
    }

    return $credentialValue;
}

function setSuperAdminStripeApiKey()
{
    $secretKey = '';
    $stripeKey = getSuperAdminSettingKeyValue('stripe_key');
    $stripeSecret = getSuperAdminSettingKeyValue('stripe_secret');

    if (isset($stripeKey) && ! is_null($stripeKey) && isset($stripeSecret) && ! is_null($stripeSecret)) {
        $secretKey = getSuperAdminSettingKeyValue('stripe_key');
    } else {
        $secretKey = config('services.stripe.secret_key');
    }

    Stripe::setApiKey($secretKey);
}

function getSubscriptionPlanCurrencyCode($key): string
{
    //    $currencyPath = file_get_contents(storage_path().'/currencies/defaultCurrency.json');
    //    $currenciesData = json_decode($currencyPath, true)['currencies'];
    //    $currency = collect($currenciesData)->firstWhere('code',
    //        strtoupper($key));

    $currenciesData = SuperAdminCurrencySetting::pluck('currency_code', DB::raw('LOWER(currency_code)'))->toArray();

    return $currenciesData[$key] ?? '';
}

if (! function_exists('getSuperAdminPaymentTypes')) {
    function getSuperAdminPaymentTypes()
    {
        $paymentTypeArray = [];
        $stripeCheck = getSuperAdminPaymentCredentials('stripe_enable');
        $razorpayCheck = getSuperAdminPaymentCredentials('razorpay_enable');
        $paystackCheck = getSuperAdminPaymentCredentials('paystack_enable');
        $phonePe = getSuperAdminPaymentCredentials('phonepe_enable');
        $paypal = getSuperAdminPaymentCredentials('paypal_enable');
        $flutterWave = getSuperAdminPaymentCredentials('flutterwave_enable');

        $paymentTypeArray[4] = 'Manual';

        if (! empty($stripeCheck)) {
            $paymentTypeArray[1] = 'Stripe';
        }
        if (! empty($paypal)) {
            $paymentTypeArray[2] = 'Paypal';
        }
        if (! empty($razorpayCheck)) {
            $paymentTypeArray[3] = 'Razorpay';
        }
        if (! empty($paystackCheck)) {
            $paymentTypeArray[6] = 'Paystack';
        }
        if (! empty($phonePe)) {
            $paymentTypeArray[7] = 'PhonePe';
        }
        if (! empty($flutterWave)) {
            $paymentTypeArray[8] = 'FlutterWave';
        }

        return $paymentTypeArray;
    }
}

function zeroDecimalCurrencies()
{
    return [
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'UGX',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF',
    ];
}
function setStripeApiKey($tenantId)
{
    $stripeKey = Setting::whereTenantId($tenantId)->where('key', '=', 'stripe_secret')->first();
    $stripe = Stripe::setApiKey($stripeKey->value);

    return $stripe;
}

/**
 * @return array
 */
function payStackSupportedCurrencies()
{
    return ['ZAR', 'USD', 'GHS', 'NGN', 'KES'];
}

function flutterWaveSupportedCurrencies()
{
    return ['GBP', 'CAD', 'XAF', 'CLP', 'COP', 'EGP', 'EUR', 'GHS', 'GNF', 'KES', 'MWK', 'MAD', 'NGN', 'RWF', 'SLL', 'STD', 'ZAR', 'TZS', 'UGX', 'USD', 'XOF', 'ZMW'];
}

function getPayPalSupportedCurrencies()
{
    return [
        'AUD',
        'BRL',
        'CAD',
        'CNY',
        'CZK',
        'DKK',
        'EUR',
        'HKD',
        'HUF',
        'ILS',
        'JPY',
        'MYR',
        'MXN',
        'TWD',
        'NZD',
        'NOK',
        'PHP',
        'PLN',
        'GBP',
        'RUB',
        'SGD',
        'SEK',
        'CHF',
        'THB',
        'USD',
    ];
}

/**
 * @return array
 */
function getSuperAdminSettingKeyValue($key)
{
    /** @var SuperAdminSetting $setting */
    $setting = SuperAdminSetting::where('key', '=', $key)->value('value');

    return $setting;
}

function getModuleAccess($tabName)
{
    return Module::where('tenant_id', getLoggedInUser()->tenant_id)->where('name', '=', $tabName)->value('is_active');
}

if (! function_exists('hasModulePermission')) {
    function hasModulePermission(string $moduleName, string $action = 'view'): bool
    {
        $user = auth()->user() ?? getLoggedInUser();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $module = Module::where('name', $moduleName)
            ->when($user->tenant_id, fn ($query) => $query->where('tenant_id', $user->tenant_id))
            ->first();

        if (! $module) {
            return true;
        }

        $roleId = $user->department_id ?: $user->roles->first()?->id;
        if (! $roleId) {
            return false;
        }

        $access = DB::table('module_role')
            ->where('role_id', $roleId)
            ->where('module_id', $module->id)
            ->first();

        if (! $access) {
            return false;
        }

        return match ($action) {
            'view' => (bool) ($access->can_access ?? false),
            'create' => (bool) ($access->can_create ?? false),
            'edit' => (bool) ($access->can_edit ?? false),
            'delete' => (bool) ($access->can_delete ?? false),
            default => false,
        };
    }
}

if (! function_exists('modulePermissionLabel')) {
    function modulePermissionLabel(string $moduleName): string
    {
        return match ($moduleName) {
            'IPD Patients' => 'IPD',
            'OPD Patients' => 'OPD',
            default => $moduleName,
        };
    }
}

function getPatientsList($userOwnerId)
{
    $patientCase = PatientCase::with('patient.patientUser')->where(
        'doctor_id',
        '=',
        $userOwnerId
    )->where('status', '=', 1)->where('tenant_id', getLoggedInUser()->tenant_id)->get()->pluck('patient.user_id', 'id');

    $patientAdmission = PatientAdmission::with('patient.patientUser')->where(
        'doctor_id',
        '=',
        $userOwnerId
    )->where('status', '=', 1)->where('tenant_id', getLoggedInUser()->tenant_id)->get()->pluck('patient.user_id', 'id');

    $arrayMerge = array_merge($patientAdmission->toArray(), $patientCase->toArray());
    $patientIds = array_unique($arrayMerge);

    $patients = Patient::with('patientUser')->whereIn('user_id', $patientIds)
        ->whereHas('patientUser', function (Builder $query) {
            $query->where('status', 1);
        })->where('tenant_id', getLoggedInUser()->tenant_id)->get()
        ->mapWithKeys(function ($patient) {
            return [$patient->id => formatPatientOptionLabel($patient)];
        });

    return $patients;
}

/**
 * @return mixed
 */
function getAPICurrencySymbol()
{
    $currenciesData = CurrencySetting::all();

    return collect($currenciesData)->where(
        'currency_code',
        strtoupper(getAPICurrentCurrency())
    )->pluck('currency_icon')->first();
}

/**
 * @return mixed
 */
function getAPICurrentCurrency()
{
    /** @var Setting $currentCurrency */
    static $currentCurrency;

    if (empty($currentCurrency)) {
        $currentCurrency = Setting::where('key', 'current_currency')->Where('tenant_id', getLoggedInUser()->tenant_id)->first();
    }

    return $currentCurrency->value;
}

function getCurrencyFormatForPDF($amount): string
{
    $currency = getAPICurrentCurrency();
    $currencies = array_keys(Gerardojbaez\Money\Currency::getAllCurrencies());
    $is_valid_currency = in_array(strtoupper($currency), $currencies);

    if ($is_valid_currency) {
        $money = new Gerardojbaez\Money\Money($amount, strtoupper($currency));
        $curr = new Gerardojbaez\Money\Currency(strtoupper($currency));

        if ($curr->getSymbolPlacement() == 'after') {
            $value = $money->amount() . getCurrencySymbol();
        } else {
            $value = getAPICurrencySymbol() . $money->amount();
        }

        return $value;
    }

    return getAPICurrencySymbol() . ' ' . number_format($amount, 2);
}
function checkLanguageSession()
{
    $defaultLanguage = getSuperAdminSettingValue()['default_language']->value;

    if (Session::has('languageName')) {
        return Session::get('languageName');
    } elseif (Session::has('languageChangeName') && Session::get('languageChangeName') != $defaultLanguage) {
        App::setLocale(Session::get('languageChangeName'));

        return Session::get('languageChangeName');
    } else {
        if (! empty($defaultLanguage)) {
            App::setLocale($defaultLanguage);

            return $defaultLanguage;
        }
    }

    return 'en';
}
function getLanguages()
{
    return User::LANGUAGES;
}
function headerLanguageName()
{
    $defaultLanguage = getSuperAdminSettingValue()['default_language']->value;
    if (Session::has('languageChangeName') && Session::get('languageChangeName') != $defaultLanguage) {
        return Session::get('languageChangeName');
    } else {
        if (! empty($defaultLanguage)) {
            return $defaultLanguage;
        }
    }

    return 'en';
}
function getHeaderLanguageName()
{
    return getLanguages()[headerLanguageName()];
}

function getRazorPaySupportedCurrencies(): array
{
    return [
        'USD',
        'EUR',
        'GBP',
        'SGD',
        'AED',
        'AUD',
        'CAD',
        'CNY',
        'SEK',
        'NZD',
        'MXN',
        'HKD',
        'NOK',
        'RUB',
        'ALL',
        'AMD',
        'ARS',
        'AWG',
        'BBD',
        'BDT',
        'BMD',
        'BND',
        'BOB',
        'BSD',
        'BWP',
        'BZD',
        'CHF',
        'COP',
        'CRC',
        'CUP',
        'CZK',
        'DKK',
        'DOP',
        'DZD',
        'EGP',
        'ETB',
        'FJD',
        'GIP',
        'GMD',
        'GTQ',
        'GYD',
        'HKD',
        'HNL',
        'HRK',
        'HTG',
        'HUF',
        'IDR',
        'ILS',
        'INR',
        'JMD',
        'KES',
        'KGS',
        'KHR',
        'KYD',
        'KZT',
        'LAK',
        'LBP',
        'LKR',
        'LRD',
        'LSL',
        'MAD',
        'MDL',
        'MKD',
        'MMK',
        'MNT',
        'MOP',
        'MUR',
        'MVR',
        'MWK',
        'MYR',
        'NAD',
        'NGN',
        'NIO',
        'NOK',
        'NPR',
        'PEN',
        'PGK',
        'PHP',
        'PKR',
        'QAR',
        'SAR',
        'SCR',
        'SLL',
        'SOS',
        'SSP',
        'SVC',
        'SZL',
        'THB',
        'TTD',
        'TZS',
        'UYU',
        'UZS',
        'YER',
        'ZAR',
        'GHS',
    ];
}

/**
 * @param  Subscription  $currentSubscription
 * @return bool
 */
function checkIfPlanIsInTrial($currentSubscription)
{
    $now = Carbon::now();
    if (! empty($currentSubscription->trial_ends_at) && $currentSubscription->trial_ends_at > $now) {
        return true;
    }

    return false;
}
function getSettingForReCaptcha($userName)
{
    $user = User::where('userName', $userName)->first();
    if (! $user) {
        $user = DB::table('users')->where('userName', $userName)->first();
    }
    $isEnabledGoogleCapcha = Setting::where('key', 'enable_google_recaptcha')->where(
        'tenant_id',
        $user->tenant_id
    )->value('value');

    return $isEnabledGoogleCapcha;
}
function getUser()
{
    $loggedInUser = getLoggedInUser();
    $user = null;
    if (! empty($loggedInUser) && request()->segment(2) != $loggedInUser->username || empty($loggedInUser)) {
        $uName = null;
        $uName = request()->segment(2);
        $user = User::withoutGlobalScope(new \Stancl\Tenancy\Database\TenantScope)
            ->where('username', $uName)
            ->first();

        if ($user == null) {
            return $loggedInUser;
        }
    } else {
        $user = getLoggedInUser();
    }

    return $user;
}

if (! function_exists('getAppointmentPaymentTypes')) {
    function getAppointmentPaymentTypes()
    {
        $paymentTypeArray = [];
        $stripeCheck = getPaymentCredentials('stripe_enable');
        $razorpayCheck = getPaymentCredentials('razorpay_enable');
        $phonePe = getPaymentCredentials('phone_pe_enable');
        $paystackCheck = getPaymentCredentials('paystack_enable');
        $paypal = getPaymentCredentials('paypal_enable');
        $flutterWave = getPaymentCredentials('flutterwave_enable');
        $paymentTypeArray[4] = 'Cash';
        $paymentTypeArray[6] = 'Cheque';

        if (! empty($stripeCheck)) {
            $paymentTypeArray[1] = 'Stripe';
        }
        if (! empty($razorpayCheck)) {
            $paymentTypeArray[2] = 'Razorpay';
        }
        if (! empty($paypal)) {
            $paymentTypeArray[3] = 'Paypal';
        }
        if (! empty($flutterWave)) {
            $paymentTypeArray[5] = 'FlutterWave';
        }
        if (! empty($phonePe)) {
            $paymentTypeArray[7] = 'PhonePe';
        }
        if (! empty($paystackCheck)) {
            $paymentTypeArray[8] = 'Paystack';
        }

        return $paymentTypeArray;
    }
}
// function getFrontSettingValue($type, $key)
// {
//     return FrontSetting::whereType($type)->where('key', $key)->value('value');
// }

function getSelectedPaymentGateway($keyName)
{
    $key = $keyName;
    static $settingValues;

    if (isset($settingValues[$key])) {
        return $settingValues[$key];
    }
    /** @var Setting $setting */
    $setting = Setting::where('key', '=', $keyName)->first();

    if (isset($setting->value) && $setting->value !== '') {
        $settingValues[$key] = $setting->value;
    } else {
        $envKey = strtoupper($key);
        $settingValues[$key] = env($envKey);
    }

    return $settingValues[$key];
}
function getCurrentLanguageName()
{
    return getLanguages()[checkLanguageSession()];
}

function getCountryCode($countryCode)
{
    $data = [
        'AF' => '93',
        'AL' => '355',
        'DZ' => '213',
        'AS' => '1',
        'AD' => '376',
        'AO' => '244',
        'AI' => '1',
        'AG' => '1',
        'AR' => '54',
        'AM' => '374',
        'AW' => '297',
        'AU' => '61',
        'AT' => '43',
        'AZ' => '994',
        'BS' => '1',
        'BH' => '973',
        'BD' => '880',
        'BB' => '1',
        'BY' => '375',
        'BE' => '32',
        'BZ' => '501',
        'BJ' => '229',
        'BM' => '1',
        'BT' => '975',
        'BO' => '591',
        'BA' => '387',
        'BW' => '267',
        'BR' => '55',
        'BN' => '673',
        'BG' => '359',
        'BF' => '226',
        'BI' => '257',
        'KH' => '855',
        'CM' => '237',
        'CA' => '1',
        'CV' => '238',
        'CF' => '236',
        'TD' => '235',
        'CL' => '56',
        'CN' => '86',
        'CO' => '57',
        'KM' => '269',
        'CG' => '242',
        'CD' => '243',
        'CR' => '506',
        'CI' => '225',
        'HR' => '385',
        'CU' => '53',
        'CY' => '357',
        'CZ' => '420',
        'DK' => '45',
        'DJ' => '253',
        'DM' => '1',
        'DO' => '1',
        'EC' => '593',
        'EG' => '20',
        'SV' => '503',
        'GQ' => '240',
        'ER' => '291',
        'EE' => '372',
        'SZ' => '268',
        'ET' => '251',
        'FJ' => '679',
        'FI' => '358',
        'FR' => '33',
        'GA' => '241',
        'GM' => '220',
        'GE' => '995',
        'DE' => '49',
        'GH' => '233',
        'GI' => '350',
        'GR' => '30',
        'GL' => '299',
        'GD' => '1',
        'GP' => '590',
        'GU' => '1',
        'GT' => '502',
        'GN' => '224',
        'GW' => '245',
        'GY' => '592',
        'HT' => '509',
        'HN' => '504',
        'HK' => '852',
        'HU' => '36',
        'IS' => '354',
        'IN' => '91',
        'ID' => '62',
        'IR' => '98',
        'IQ' => '964',
        'IE' => '353',
        'IL' => '972',
        'IT' => '39',
        'JM' => '1',
        'JP' => '81',
        'JO' => '962',
        'KZ' => '7',
        'KE' => '254',
        'KI' => '686',
        'KP' => '850',
        'KR' => '82',
        'KW' => '965',
        'KG' => '996',
        'LA' => '856',
        'LV' => '371',
        'LB' => '961',
        'LS' => '266',
        'LR' => '231',
        'LY' => '218',
        'LI' => '423',
        'LT' => '370',
        'LU' => '352',
        'MO' => '853',
        'MG' => '261',
        'MW' => '265',
        'MY' => '60',
        'MV' => '960',
        'ML' => '223',
        'MT' => '356',
        'MH' => '692',
        'MQ' => '596',
        'MR' => '222',
        'MU' => '230',
        'MX' => '52',
        'FM' => '691',
        'MD' => '373',
        'MC' => '377',
        'MN' => '976',
        'ME' => '382',
        'MA' => '212',
        'MZ' => '258',
        'MM' => '95',
        'NA' => '264',
        'NR' => '674',
        'NP' => '977',
        'NL' => '31',
        'NZ' => '64',
        'NI' => '505',
        'NE' => '227',
        'NG' => '234',
        'NO' => '47',
        'OM' => '968',
        'PK' => '92',
        'PW' => '680',
        'PS' => '970',
        'PA' => '507',
        'PG' => '675',
        'PY' => '595',
        'PE' => '51',
        'PH' => '63',
        'PL' => '48',
        'PT' => '351',
        'PR' => '1',
        'QA' => '974',
        'RE' => '262',
        'RO' => '40',
        'RU' => '7',
        'RW' => '250',
        'WS' => '685',
        'SM' => '378',
        'SA' => '966',
        'SN' => '221',
        'RS' => '381',
        'SC' => '248',
        'SL' => '232',
        'SG' => '65',
        'SK' => '421',
        'SI' => '386',
        'SB' => '677',
        'SO' => '252',
        'ZA' => '27',
        'ES' => '34',
        'LK' => '94',
        'SD' => '249',
        'SR' => '597',
        'SE' => '46',
        'CH' => '41',
        'SY' => '963',
        'TW' => '886',
        'TJ' => '992',
        'TZ' => '255',
        'TH' => '66',
        'TL' => '670',
        'TG' => '228',
        'TO' => '676',
        'TT' => '1',
        'TN' => '216',
        'TR' => '90',
        'TM' => '993',
        'TV' => '688',
        'UG' => '256',
        'UA' => '380',
        'AE' => '971',
        'GB' => '44',
        'US' => '1',
        'UY' => '598',
        'UZ' => '998',
        'VU' => '678',
        'VA' => '39',
        'VE' => '58',
        'VN' => '84',
        'YE' => '967',
        'ZM' => '260',
        'ZW' => '263',
    ];

    return isset($data[$countryCode]) ? '+' . $data[$countryCode] : null;
}

function superAdminCurrency()
{
    $current_currency = SuperAdminSetting::where('key', '=', 'super_admin_currency')->first()->value;
    $currency = SuperAdminCurrencySetting::where('currency_code', strtoupper($current_currency))->first();
    $currencyIcon = $currency->currency_icon ?? 'inr';

    return $currencyIcon;
}
function ipdPatientPaymentRule($id, $action, $paymentId = null)
{
    $ipdPatienId = $id;

    // get total charges
    $totalCharges = IpdCharge::whereIpdPatientDepartmentId($ipdPatienId)->get()->sum('applied_charge');
    $bedDetails = IpdPatientDepartment::with('bed')->find($ipdPatienId);
    $totalCharges += $bedDetails->bed->charge;
    if ($action == 'create') {
        $ipdBill = IpdBill::whereIpdPatientDepartmentId($ipdPatienId)->first();
        $totalPayment = IpdPayment::whereIpdPatientDepartmentId($ipdPatienId)->get()->sum('amount');
        $maxAmount = ($ipdBill) ? $ipdBill->net_payable_amount : $totalCharges - $totalPayment;
    } else {
        $totalPayment = IpdPayment::whereIpdPatientDepartmentId($ipdPatienId)
            ->where('id', '!=', $paymentId)->get()->sum('amount');
        $maxAmount = $totalCharges - $totalPayment;
    }

    $maxAmount = round((float) $maxAmount, 2);

    return $maxAmount;
}

/**
 * return avatar full url.
 *
 * @param  int  $userId
 * @param  string  $name
 */
function getApiUserImageInitial($userId, $username): string
{
    $name = str_replace(' ', '', $username);

    return getAvatarUrl() . "?name=$name&color=fff&background=" . getRandomColor($userId);
}

function getMoneyFormat($currency, $amount): string
{
    $currencies = array_keys(Gerardojbaez\Money\Currency::getAllCurrencies());
    $is_valid_currency = in_array(strtoupper($currency), $currencies);

    if ($is_valid_currency) {
        $money = new Gerardojbaez\Money\Money($amount, strtoupper($currency));

        $value = $money->amount();

        return $value;
    }

    return number_format($amount, 2);
}

/**
 * @return mixed
 */
function getCurrentVersion()
{
    $composerFile = base_path('composer.json');
    if (! is_file($composerFile)) {
        return '0.0.0';
    }

    $composerData = json_decode((string) file_get_contents($composerFile), true);

    return $composerData['version'] ?? '0.0.0';
}
if (! function_exists('getSettingValueByKey')) {
    /**
     * @return mixed
     */
    function getSettingValueByKey($keyName)
    {
        /** @var Setting $setting */
        $setting = Setting::where('key', '=', $keyName)->whereTenantId(getLoggedInUser()->tenant_id)->first();
        if ($setting) {
            return $setting->value;
        }

        return false;
    }
}

function canCurrencyDelete($model, $columnName, $value): bool
{
    $result = $model::where($columnName, strtolower($value))->exists();
    if ($result) {
        return true;
    }

    return false;
}

if (! function_exists('formatPatientOptionLabel')) {
    function formatPatientOptionLabel($patient): string
    {
        $name = $patient->patientUser->full_name ?? $patient->user->full_name ?? '';
        $unique = $patient->patient_unique_id ?: $patient->id;

        return trim($name.' ('.$unique.')');
    }
}

if (! function_exists('searchPatientSelectOptions')) {
    function searchPatientSelectOptions(string $search): array
    {
        return \App\Models\Patient::with('patientUser')
            ->where('tenant_id', getLoggedInUser()->tenant_id)
            ->whereHas('patientUser', function ($query) {
                $query->where('status', 1);
            })
            ->where(function ($query) use ($search) {
                $query->where('patient_unique_id', 'like', '%'.$search.'%')
                    ->orWhere('id', $search)
                    ->orWhereHas('patientUser', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(function ($patient) {
                return [$patient->id => formatPatientOptionLabel($patient)];
            })
            ->toArray();
    }
}

if (! function_exists('getChargeTypeOptions')) {
    function getChargeTypeOptions(): array
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('charge_types')) {
                $tenantId = getLoggedInUser()?->tenant_id;
                $types = \App\Models\ChargeType::query()
                    ->where(function ($query) use ($tenantId) {
                        $query->whereNull('tenant_id');
                        if ($tenantId) {
                            $query->orWhere('tenant_id', $tenantId);
                        }
                    })
                    ->orderBy('id')
                    ->pluck('name', 'id')
                    ->toArray();

                if (! empty($types)) {
                    return $types;
                }
            }
        } catch (\Throwable $e) {
            // fall through to defaults
        }

        return \App\Models\ChargeCategory::CHARGE_TYPES;
    }
}

if (! function_exists('getChargeTypeName')) {
    function getChargeTypeName($id): string
    {
        $options = getChargeTypeOptions();

        return $options[$id] ?? __('messages.common.n/a');
    }
}

if (! function_exists('getPatientSelectOptions')) {
    function getPatientSelectOptions(): array
    {
        return \App\Models\Patient::with('patientUser')
            ->where('tenant_id', getLoggedInUser()->tenant_id)
            ->whereHas('patientUser', function ($query) {
                $query->where('status', 1);
            })
            ->get()
            ->mapWithKeys(function ($patient) {
                return [$patient->id => formatPatientOptionLabel($patient)];
            })
            ->toArray();
    }
}

if (! function_exists('getDoctorSelectOptions')) {
    function getDoctorSelectOptions(): array
    {
        return \App\Models\Doctor::with('doctorUser')
            ->where('tenant_id', getLoggedInUser()->tenant_id)
            ->get()
            ->where('doctorUser.status', 1)
            ->pluck('doctorUser.full_name', 'id')
            ->toArray();
    }
}

if (! function_exists('getNurseSelectOptions')) {
    function getNurseSelectOptions(): array
    {
        return \App\Models\Nurse::with('user')
            ->where('tenant_id', getLoggedInUser()->tenant_id)
            ->get()
            ->where('user.status', 1)
            ->pluck('user.full_name', 'id')
            ->toArray();
    }
}

function getRegionCode($countryCode)
{
    $countries = ['af' => '93', 'al' => '355', 'dz' => '213', 'as' => '1', 'ad' => '376', 'ao' => '244', 'ai' => '1', 'ag' => '1', 'ar' => '54', 'am' => '374', 'aw' => '297', 'ac' => '247', 'au' => '61', 'at' => '43', 'az' => '994', 'bs' => '1', 'bh' => '973', 'bd' => '880', 'bb' => '1', 'by' => '375', 'be' => '32', 'bz' => '501', 'bj' => '229', 'bm' => '1', 'bt' => '975', 'bo' => '591', 'ba' => '387', 'bw' => '267', 'br' => '55', 'io' => '246', 'vg' => '1', 'bn' => '673', 'bg' => '359', 'bf' => '226', 'bi' => '257', 'kh' => '855', 'cm' => '237', 'ca' => '1', 'cv' => '238', 'bq' => '599', 'ky' => '1', 'cf' => '236', 'td' => '235', 'cl' => '56', 'cn' => '86', 'cx' => '61', 'cc' => '61', 'co' => '57', 'km' => '269', 'cd' => '243', 'cg' => '242', 'ck' => '682', 'cr' => '506', 'ci' => '225', 'hr' => '385', 'cu' => '53', 'cw' => '599', 'cy' => '357', 'cz' => '420', 'dk' => '45', 'dj' => '253', 'dm' => '1', 'do' => '1', 'ec' => '593', 'eg' => '20', 'sv' => '503', 'gq' => '240', 'er' => '291', 'ee' => '372', 'sz' => '268', 'et' => '251', 'fk' => '500', 'fo' => '298', 'fj' => '679', 'fi' => '358', 'fr' => '33', 'gf' => '594', 'pf' => '689', 'ga' => '241', 'gm' => '220', 'ge' => '995', 'de' => '49', 'gh' => '233', 'gi' => '350', 'gr' => '30', 'gl' => '299', 'gd' => '1', 'gp' => '590', 'gu' => '1', 'gt' => '502', 'gg' => '44', 'gn' => '224', 'gw' => '245', 'gy' => '592', 'ht' => '509', 'hn' => '504', 'hk' => '852', 'hu' => '36', 'is' => '354', 'in' => '91', 'id' => '62', 'ir' => '98', 'iq' => '964', 'ie' => '353', 'im' => '44', 'il' => '972', 'it' => '39', 'jm' => '1', 'jp' => '81', 'je' => '44', 'jo' => '962', 'kz' => '7', 'ke' => '254', 'ki' => '686', 'xk' => '383', 'kw' => '965', 'kg' => '996', 'la' => '856', 'lv' => '371', 'lb' => '961', 'ls' => '266', 'lr' => '231', 'ly' => '218', 'li' => '423', 'lt' => '370', 'lu' => '352', 'mo' => '853', 'mg' => '261', 'mw' => '265', 'my' => '60', 'mv' => '960', 'ml' => '223', 'mt' => '356', 'mh' => '692', 'mq' => '596', 'mr' => '222', 'mu' => '230', 'yt' => '262', 'mx' => '52', 'fm' => '691', 'md' => '373', 'mc' => '377', 'mn' => '976', 'me' => '382', 'ms' => '1', 'ma' => '212', 'mz' => '258', 'mm' => '95', 'na' => '264', 'nr' => '674', 'np' => '977', 'nl' => '31', 'nc' => '687', 'nz' => '64', 'ni' => '505', 'ne' => '227', 'ng' => '234', 'nu' => '683', 'nf' => '672', 'kp' => '850', 'mk' => '389', 'mp' => '1', 'no' => '47', 'om' => '968', 'pk' => '92', 'pw' => '680', 'ps' => '970', 'pa' => '507', 'pg' => '675', 'py' => '595', 'pe' => '51', 'ph' => '63', 'pl' => '48', 'pt' => '351', 'pr' => '1', 'qa' => '974', 're' => '262', 'ro' => '40', 'ru' => '7', 'rw' => '250', 'bl' => '590', 'sh' => '290', 'kn' => '1', 'lc' => '1', 'mf' => '590', 'pm' => '508', 'vc' => '1', 'ws' => '685', 'sm' => '378', 'st' => '239', 'sa' => '966', 'sn' => '221', 'rs' => '381', 'sc' => '248', 'sl' => '232', 'sg' => '65', 'sx' => '1', 'sk' => '421', 'si' => '386', 'sb' => '677', 'so' => '252', 'za' => '27', 'kr' => '82', 'ss' => '211', 'es' => '34', 'lk' => '94', 'sd' => '249', 'sr' => '597', 'sj' => '47', 'se' => '46', 'ch' => '41', 'sy' => '963', 'tw' => '886', 'tj' => '992', 'tz' => '255', 'th' => '66', 'tl' => '670', 'tg' => '228', 'tk' => '690', 'to' => '676', 'tt' => '1', 'tn' => '216', 'tr' => '90', 'tm' => '993', 'tc' => '1', 'tv' => '688', 'vi' => '1', 'ug' => '256', 'ua' => '380', 'ae' => '971', 'gb' => '44', 'us' => '1', 'uy' => '598', 'uz' => '998', 'vu' => '678', 'va' => '39', 've' => '58', 'vn' => '84', 'wf' => '681', 'eh' => '212', 'ye' => '967', 'zm' => '260', 'zw' => '263', 'ax' => '358'];

    $countryCode = strtolower($countryCode);

    return isset($countries[$countryCode]) ? '+' . $countries[$countryCode] : '';
}

// ? use this function when phone number with dial code
if (! function_exists('getPhoneNumber')) {
    function getPhoneNumber($phoneNumber)
    {
        $sanitizedNumber = preg_replace('/[^\d]/', '', $phoneNumber);

        $sanitizedNumber = ltrim($sanitizedNumber, '0');

        return $sanitizedNumber;
    }
}

if (! function_exists('generateQrCode')) {
    function generateQrCode($data, $size = 90)
    {
        $qrCode = QrCode::create($data)
            ->setSize($size)
            ->setMargin(5);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return '<img src="data:' . $result->getMimeType() . ';base64,' . base64_encode($result->getString()) . '" />';
    }
}

if (! function_exists('generateQrCodeBase64')) {
    function generateQrCodeBase64($data, $size = 90)
    {
        $qrCode = QrCode::create($data)
            ->setSize($size)
            ->setMargin(5);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return 'data:' . $result->getMimeType() . ';base64,' . base64_encode($result->getString());
    }
}
