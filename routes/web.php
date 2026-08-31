<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentTransactionController;
use App\Http\Controllers\Landing;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\BillController;
use App\Http\Controllers\CashController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\FlutterwaveController;
use App\Http\Controllers\GoogleMeetCalendarController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IpdBillController;
use App\Http\Controllers\IpdPaymentController;
use App\Http\Controllers\LandingPaypalController;
use App\Http\Controllers\LandingPaystackController;
use App\Http\Controllers\LandingPhonePeController;
use App\Http\Controllers\LiveConsultationController;
use App\Http\Controllers\MedicineBillController;
use App\Http\Controllers\OpdPrescriptionController;
use App\Http\Controllers\PathologyTestController;
use App\Http\Controllers\PatientDiagnosisTestController;
use App\Http\Controllers\PatientPaypalController;
use App\Http\Controllers\PatientRazorpayController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\PhonePeController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PurchaseMedicineController;
use App\Http\Controllers\RazorpayController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\OdontogramController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SmartPatientCardController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\SubscriptionPricingPlanController;
use App\Http\Controllers\SuperAdminEnquiryController;
use App\Http\Controllers\Web\AppointmentController as WebAppointmentController;


Route::middleware('xss', 'languageChangeName')->group(function () {
    Route::get('/', [Landing\LandingScreenController::class, 'index'])->name('landing-home');
    Route::get('/about-us', [Landing\LandingScreenController::class, 'aboutUs'])->name('landing.about.us');
    Route::get('/our-services', [Landing\LandingScreenController::class, 'services'])->name('landing.services');
    Route::get('/pricing', [Landing\LandingScreenController::class, 'pricing'])->name('landing.pricing');
    Route::get('/contact-us', [Landing\LandingScreenController::class, 'contactUs'])->name('landing.contact.us');
    Route::get('/faqs', [Landing\LandingScreenController::class, 'faq'])->name('landing.faq');
    Route::get('/hospitals', [Landing\LandingScreenController::class, 'hospitals'])->name('landing.hospitals');
    Route::post('/subscribe', [Landing\SubscribeController::class, 'store'])->name('subscribe.store');
    Route::post('/enquiries', [SuperAdminEnquiryController::class, 'store'])->name('super.admin.enquiry.store');
});

Route::post('/change-language', [WebController::class, 'changeLanguage']);
Route::post('/language-change-name', [WebController::class, 'languageChangeName']);
Route::get(
    'appointments/{email}/patient-detail',
    [WebAppointmentController::class, 'getPatientDetails']
)->name('appointment.patient.details');
Route::get('appointment-doctors-list', [WebAppointmentController::class, 'getDoctors'])->name('appointment.doctor.list');
Route::get('appointment-doctor-list', [WebAppointmentController::class, 'getDoctorList'])->name('appointment.doctors.list');
Route::get(
    'appointment-booking-slot',
    [WebAppointmentController::class, 'getBookingSlot']
)->name('appointment.get.booking.slot');
Route::get('appointment-doctor-schedule-list', [ScheduleController::class, 'doctorScheduleList'])->name('front-doctor-schedule-list');
Route::post('appointment-store', [WebAppointmentController::class, 'store'])->name('web.appointments.store');

// Web Stripe Payment Route
Route::post('web-appointment-stripe-charge', [AppointmentTransactionController::class, 'webCreateStripeSession'])->name('web.appointment.stripe.session');
Route::get('web-appointment-stripe-success', [AppointmentTransactionController::class, 'webAppointmentStripePaymentSuccess'])->name('web.appointment.stripe.success');
Route::get('web-appointment-stripe-fail', [AppointmentTransactionController::class, 'webAppointmentStripeFailed'])->name('web.appointment.stripe.failed');
// Web Razorpay payment Route
Route::post('web-appointment-razorpay-onboard', [AppointmentTransactionController::class, 'webAppointmentRazorpayPayment'])->name('web.appointment.razorpay.init');
Route::post('web-razorpay-payment-success', [AppointmentTransactionController::class, 'WebAppointmentRazorpayPaymentSuccess'])->name('web.appointment.razorpay.success');
Route::post('web-appointment-razorpay-failed', [AppointmentTransactionController::class, 'WebAppointmentRazorPayPaymentFailed'])->name('web.appointment.razorpay.failed');

// Web Paypal Payment Route
Route::get('web-appointment-paypal-onboard', [AppointmentTransactionController::class, 'webAppointmentPaypalOnBoard'])->name('web.appointment.paypal.init');
Route::get('web-appointment-paypal-payment-success', [AppointmentTransactionController::class, 'webAppointmentPaypalSuccess'])->name('web.appointment.paypal.success');
Route::get('web-appointment-paypal-payment-failed', [AppointmentTransactionController::class, 'webAppointmentPaypalFailed'])->name('web.appointment.paypal.failed');

// Web FlutterWave Payment
Route::get('web-flutter-wave-payment', [AppointmentTransactionController::class, 'webFlutterWavePayment'])->name('web.appointment.flutterwave');
Route::get('web-flutter-wave-payment-success', [AppointmentTransactionController::class, 'webFlutterWavePaymentSuccess'])->name('web.appointment.flutterwave.success');

// phonePay web appointment transaction
Route::get('web-phone-pay-init', [AppointmentTransactionController::class, 'wenPhonePayInit'])->name('web.appointment.phone.pay.init');
Route::post('web-phonepe-payment-success', [AppointmentTransactionController::class, 'webPhonePePaymentSuccess'])->name('web.appointment.phonepe.callback');

// Appointment PayStack Payment
Route::get('web-appointment-paystack-payment', [AppointmentTransactionController::class, 'webAppointmentPaystackPayment'])->name('web.appointment.paystack.init');

Route::post('patient-razorpay-payment-failed', [PatientRazorpayController::class, 'paymentFailed'])
    ->name('patient.razorpay.failed');

Route::prefix('h/{username}')->group(function () {
    Route::middleware('setLanguage', 'xss', 'setTenantFromUsername')->group(function () {
        Route::get('/', [WebController::class, 'index'])->name('front');
        // Routes for Enquiry Form
        Route::post('send-enquiry', [EnquiryController::class, 'store'])->name('send.enquiry');
        Route::get('/contact-us', [EnquiryController::class, 'contactUs'])->name('contact');
        Route::get('/about-us', [WebController::class, 'aboutUs'])->name('aboutUs');
        Route::get('/appointment', [WebController::class, 'appointment'])->name('appointment');
        Route::post('/appointment-form', [WebController::class, 'appointmentFromOther'])->name('appointment.post');
        Route::get('/our-services', [WebController::class, 'services'])->name('our-services');
        Route::get('/our-doctors', [WebController::class, 'doctors'])->name('our-doctors');
        Route::get('/terms-of-service', [WebController::class, 'termsOfService'])->name('terms-of-service');
        Route::get('/privacy-policy', [WebController::class, 'privacyPolicy'])->name('privacy-policy');
        Route::get('/working-hours', [WebController::class, 'workingHours'])->name('working-hours');
        Route::get('/testimonial', [WebController::class, 'testimonials'])->name('testimonials');
        Route::get('/patient-details/{uniqueCode}', [WebController::class, 'patientDetails'])->name('patient.details');
        Route::get('/doctor-details/{id}', [WebController::class, 'doctorDetails'])->name('doctor.details');
    });
});

Route::middleware('auth', 'verified', 'xss', 'checkUserStatus', 'role:Admin')->group(function () {
    Route::get(
        'subscription-plans',
        [SubscriptionPricingPlanController::class, 'index']
    )->name('subscription.pricing.plans.index');
    Route::get(
        'choose-payment-type/{planId}/{context?}/{fromScreen?}',
        [SubscriptionPricingPlanController::class, 'choosePaymentType']
    )->name('choose.payment.type');
    Route::post(
        'purchase-subscription',
        [SubscriptionController::class, 'purchaseSubscription']
    )->name('purchase-subscription');
    Route::get('payment-success', [SubscriptionController::class, 'paymentSuccess'])->name('payment-success');
    Route::get('failed-payment', [SubscriptionController::class, 'handleFailedPayment'])->name('failed-payment');
});

Route::get('purchase-subscription-flutterwave', [SubscriptionController::class, 'flutterWavePayment'])->name('purchase.subscription.flutterwave');
Route::get('purchase-subscription-flutterwave-success', [SubscriptionController::class, 'flutterWavePaymentSuccess'])->name('purchase.subscription.flutterwave.success');

Route::middleware('role:Admin|Patient|Doctor|Receptionist|Nurse')->group(function () {
    Route::resource('appointments', AppointmentController::class);
    Route::get('get-appointment-charge', [AppointmentController::class, 'getAppointmentCharge'])->name('get-appointment-charge');
    Route::post('appointment-stripe-charge', [AppointmentTransactionController::class, 'createStripeSession'])->name('appointment.stripe.session');
    Route::get('appointment-stripe-success', [AppointmentTransactionController::class, 'appointmentStripePaymentSuccess'])->name('appointment.stripe.success');
    Route::get('appointment-stripe-fail', [AppointmentTransactionController::class, 'appointmentStripeFailed'])->name('appointment.stripe.failure');
});

Route::middleware('role:Admin|Patient|Receptionist')->group(function () {
    Route::get('smart-patient-card-download/{id}', [SmartPatientCardController::class, 'downloadSmartCard'])->name('smart-patient-cards.download');
});

Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'convertToPdf'])
    ->where('invoice', '[0-9]+')->name('invoices.pdf');
Route::get('invoices/{invoice}/send-mail', [InvoiceController::class, 'sendMail'])->name('invoices.send.mail');
Route::get('pathology-test-pdf/{id}', [PathologyTestController::class, 'convertToPDF'])->name('pathology.test.pdf');
Route::get('medicine-bills-pdf/{id}', [MedicineBillController::class, 'convertToPDF'])->name('medicine.bill.pdf');
Route::get('prescription-pdf/{id}', [PrescriptionController::class, 'convertToPDF'])->name('prescriptions.pdf');
Route::get('bills/{bill}/pdf', [BillController::class, 'convertToPdf'])->name('bills.pdf');
Route::get('hospital-admin/diagnosis/diagnosis-tests/{patientDiagnosisTest}/pdf', [PatientDiagnosisTestController::class, 'convertToPdf'])->name('patient.diagnosis.test.pdf');
Route::get('opd-prescription-pdf/{id}', [OpdPrescriptionController::class, 'convertToPDF'])->name('opd.prescriptions.pdf');

Route::post('stripe/subscription-purchase', [StripeController::class, 'purchase'])->name('stripe.purchase');
Route::get('stripe-success-subscription', [StripeController::class, 'success'])->name('stripe.success');
Route::get('stripe-failed-subscription', [StripeController::class, 'failed'])->name('stripe.failed');

Route::post('paypal-purchase', [PayPalController::class, 'purchase'])->name('paypal.purchase');
Route::get('paypal-success', [PaypalController::class, 'success'])->name('paypal.success');
Route::get('paypal-failed', [PaypalController::class, 'failed'])->name('paypal.failed');

Route::get('flutterwave-subscription', [FlutterwaveController::class, 'purchase'])->name('flutterwave.subscription');
Route::get('flutterwave-subscription-success', [FlutterwaveController::class, 'success'])->name('flutterwave.subscription.success');

Route::post('subscription-phonepe-payment-success', [PhonePeController::class, 'subscriptionPhonePePaymentSuccess'])->name('subscription.phonepe.callback');

Route::middleware('role:Admin|Patient')->group(function () {
    Route::post('phonepe-payment-success', [BillController::class, 'billPhonePePaymentSuccess'])->name('billing.phonepe.callback');
    Route::get('bill-stripe-payment-success', [BillController::class, 'paymentSuccess'])->name('bill.stripe.payment.success');
    Route::get('flutterwave-payemnt-success', [BillController::class, 'flutterwavePaymentSuccess'])->name('flutterwave.success');
    Route::get('paystack-payment-success', [PaystackController::class, 'handleGatewayCallback'])->name('paystack.success');
    Route::post('patient-razorpay-payment-success', [BillController::class, 'razorPayPaymentSuccess'])->name('razorpay.payment.success');
    Route::get('patient-razorpay-payment-failed', [BillController::class, 'razorPayPaymentFailed'])->name('razorpay.payment.failed');
    Route::get('patient-bill-paypal-payment-success', [BillController::class, 'paypalPaymentSuccess'])->name('paypal.payment.success');
    Route::get('patient-bill-paypal-payment-failed', [BillController::class, 'paypalPaymentFailed'])->name('paypal.payment.failed');
});

// Razor Pay Routes
Route::post(
    'razorpay-purchase-subscription',
    [RazorpayController::class, 'purchaseSubscription']
)->name('razorpay.purchase.subscription');
Route::post('razorpay-payment-success', [RazorpayController::class, 'paymentSuccess'])
    ->name('razorpay.success');
Route::post('razorpay-payment-failed', [RazorpayController::class, 'paymentFailed'])
    ->name('razorpay.failed');
Route::get('razorpay-payment-failed-modal', [RazorpayController::class, 'paymentFailedModal'])
    ->name('razorpay.failed.modal');

Route::post('cash-payment', [CashController::class, 'pay'])
    ->name('cash.pay.success');

Route::get('ipd-bills/{ipdPatientDepartment}/pdf', [IpdBillController::class, 'ipdBillConvertToPdf'])
    ->where('ipdPatientDepartment', '[0-9]+');
Route::get('ipd-discharge-patient/{ipdPatientDepartment}/pdf', [IpdBillController::class, 'ipdDischargePatientToPdf'])
    ->where('ipdPatientDepartment', '[0-9]+');

// Auth::routes(['verify' => true]);

Route::middleware('role:Admin|Patient|Doctor|Receptionist|Nurse')->group(function () {

    // Razorpay
    Route::post('appointment-razorpay-onboard', [AppointmentTransactionController::class, 'appointmentRazorpayPayment'])->name('appointmentRazorpay.init');
    Route::post('appointment-razorpay-payment-success', [AppointmentTransactionController::class, 'appointmentRazorpayPaymentSuccess'])->name('appointment.razorpay.success');
    Route::get('appointment-razorpay-failed', [AppointmentTransactionController::class, 'appointmentRazorPayPaymentFailed'])->name('appointment.razorpay.failed');
    // Paypal
    Route::get('appointment-paypal-onboard', [AppointmentTransactionController::class, 'paypalOnBoard'])->name('appointment.paypal.init');
    Route::get('appointment-paypal-payment-success', [AppointmentTransactionController::class, 'paypalSuccess'])->name('appointment.paypal.success');
    Route::get('appointment-paypal-payment-failed', [AppointmentTransactionController::class, 'paypalFailed'])->name('appointment.paypal.failed');

    // Appointment FlutterWave Payment
    Route::get('appointment-flutterwave-payment', [AppointmentTransactionController::class, 'appointmentFlutterWavePayment'])->name('appointment.flutterwave.payment');
    Route::get('appointment-flutterwave-payment-success', [AppointmentTransactionController::class, 'appointmentFlutterWavePaymentSuccess'])->name('appointment.flutterwave.success');

    // Appointment PayStack Payment
    Route::get('appointment-paystack-payment', [AppointmentTransactionController::class, 'appointmentPaystackPayment'])->name('appointment.paystack.init');
    Route::get('paystack-success', [PurchaseMedicineController::class, 'PaystackPaymentSuccess'])->name('purchase.paystack.success');

    // phonePay appointment transaction
    Route::get('appointment-phone-pay-init', [AppointmentTransactionController::class, 'phonePayInit'])->name('appointment.phone.pay.init');
    Route::post('appointment-phonepe-payment-success', [AppointmentTransactionController::class, 'appointmentPhonePePaymentSuccess'])->name('appointment.phonepe.callback');

    Route::get('ipd-stripe-success', [IpdPaymentController::class, 'ipdStripePaymentSuccess'])->name('ipd.stripe.success');
    Route::get('ipd-stripe-failed-payment', [IpdPaymentController::class, 'handleFailedPayment'])->name('stripe-failed-payment');

    Route::get('patient-paypal-onboard', [PatientPaypalController::class, 'onBoard'])->name('patient.paypal.init');
    Route::get('patient-paypal-payment-success', [PatientPaypalController::class, 'success'])->name('patient.paypal.success');
    Route::get('patient-paypal-payment-failed', [PatientPaypalController::class, 'failed'])->name('patient.paypal.failed');

    Route::post('ipd-phonepe-payment-success', [IpdPaymentController::class, 'phonePePaymentSuccess'])->name('ipd.phonepe.callback');

    // IPD Payment Razorpay
    Route::post('ipd-razorpay-payment-success', [IpdPaymentController::class, 'ipdRazorpayPaymentSuccess'])->name('ipd.razorpay.success');
    Route::get('ipd-razorpay-failed', [IpdPaymentController::class, 'ipdRazorpayPaymentFailed'])->name('ipd.razorpay.failed');

    Route::get('flutterwave-payment-success', [IpdPaymentController::class, 'flutterwavePaymentSuccess'])->name('flutterwave.payment.success');

    Route::get('ipd-paystack-onboard', [IpdPaymentController::class, 'ipdPaystackPayment'])->name('ipd.paystack.init');
    Route::get(
        'paystack-payment-success',
        [PaystackController::class, 'handleGatewayCallback']
    )->name('paystack.success');

    Route::get(
        'ipd-paystack-payment-success',
        [IpdPaymentController::class, 'IpdPaystackPaystackSuccess']
    )->name('patient.paystack.success');

    Route::middleware('role:Admin|Lab Technician|Pharmacist')->group(function () {

        Route::get('stripe-success', [MedicineBillController::class, 'stripeSuccess'])->name('medicine.bill.stripe.success');
        Route::get('stripe-fail', [MedicineBillController::class, 'stripeFailed'])->name('medicine.bill.stripe.failed');

        // Medicine purchase stripe payment
        Route::get('medicine-purchase-stripe-success', [PurchaseMedicineController::class, 'stripeSuccess'])->name('medicine.purchase.stripe.success');
        Route::get('medicine-purchase-stripe-fail', [PurchaseMedicineController::class, 'stripeFail'])->name('medicine.purchase.stripe.failed');

        // Purchase medicine Paystack Payment
        Route::get('medicine-bill-paystack-onboard', [MedicineBillController::class, 'paystackPayment'])->name('medicine.bill.paystack.init');
        Route::get('medicine-bill-paystack-payment-success', [MedicineBillController::class, 'paystackPaymentSuccess'])->name('medicine.bill.paystack.success');

        // Purchase medicine Paystack Payment
        Route::get('medicine-purchase-paystack-onboard', [PurchaseMedicineController::class, 'PaystackPayment'])->name('purchase.medicine.paystack.init');

        // medicine bill phonepe payment
        Route::post('medicine-bill-phonepe-payment-success', [MedicineBillController::class, 'phonePePaymentSuccess'])->name('medicine.bill.phonepe.callback');

        Route::get('medicine-bills-paypal-payment-success', [MedicineBillController::class, 'paypalSuccess'])->name('medicine.bills.paypal.success');
        Route::get('medicine-bills-paypal-payment-failed', [MedicineBillController::class, 'paypalFailed'])->name('medicine.bills.paypal.failed');

        Route::get('medicine-bills-paypal-payment-success', [MedicineBillController::class, 'paypalSuccess'])->name('medicine.bills.paypal.success');
        Route::get('medicine-bills-paypal-payment-failed', [MedicineBillController::class, 'paypalFailed'])->name('medicine.bills.paypal.failed');

        Route::get('medicine-purchase-bills-paypal-payment-success', [PurchaseMedicineController::class, 'paypalSuccess'])->name('medicine.purchase.bills.paypal.success');
        Route::get('medicine-purchase-bills-paypal-payment-failed', [PurchaseMedicineController::class, 'paypalFailed'])->name('medicine.purchase.bills.paypal.failed');

        // purchase medicine flutterwave payment
        Route::get('purchase-medicine-flutterwave-success', [PurchaseMedicineController::class, 'flutterWavePaymentSuccess'])->name('purchase.medicine.flutterwave.success');

        // Medicine bill flutterWave payment
        Route::get('medicine-bill-flutterwave-success', [MedicineBillController::class, 'flutterWaveSuccess'])->name('medicine.bill.flutterwave.success');

        // purchase medicine phonepe payment
        Route::post('purchase-medicine-phonepe-payment-success', [PurchaseMedicineController::class, 'phonePePaymentSuccess'])->name('purchase.medicine.phonepe.callback');

        Route::post('medicine-purchase-razorpay-success', [PurchaseMedicineController::class, 'razorPaySuccess'])->name('purchase.medicine.razorpay.success');
        Route::get('medicine-purchase-razorpay-fail', [PurchaseMedicineController::class, 'razorPayFailed'])->name('purchase.medicine.razorpay.fail');

        Route::post('medicine-bill-razorpay-success', [MedicineBillController::class, 'razorPayPaymentSuccess'])->name('medicine.bill.razorpay.success');
        Route::get('medicine-bill-razorpay-failed', [MedicineBillController::class, 'razorPayPaymentFailed'])->name('medicine.bill.razorpay.failed');
    });
});

// paypal Route
Route::get('paypal-onboard', [LandingPaypalController::class, 'onBoard'])->name('paypal.init');
Route::get('paypal-payment-success', [LandingPaypalController::class, 'success'])->name('landing.paypal.success');
Route::get('paypal-payment-failed', [LandingPaypalController::class, 'failed'])->name('landing.paypal.failed');

// paystack
Route::get('paystack-onboard', [LandingPaystackController::class, 'redirectToGateway'])->name('paystack.init');

// phonePay subscription transaction
Route::get('subscription-phonepe-init', [LandingPhonePeController::class, 'phonePayInit'])->name('subscription.phonepe.init');
Route::post('user-subscription-phonepe-payment-success', [LandingPhonePeController::class, 'subscriptionPhonePePaymentSuccess'])->name('user.subscription.phonepe.callback');

Route::middleware('role:Admin|Patient|Doctor')->group(function () {
    Route::get('odontogram/{odontogram}/pdf', [OdontogramController::class, 'convertToPdf'])->name('odontogram.pdf');
});

include 'auth.php';

Route::any('zoom/callback', [LiveConsultationController::class, 'zoomCallback']);
Route::get('google/callback', [GoogleMeetCalendarController::class, 'googleCallback'])->name('google.callback');

Route::get('{username}/queue', [AppointmentController::class, 'showPatientQueue'])->name('patient-queue-url');

Route::get('/upgrade/database', function () {
    Artisan::call('migrate',
        [
            '--force' => true,
        ]);
});
