@extends('web.layouts.front')
@section('title')
    {{ __('messages.appointments') }}
@endsection
@section('page_css')
    {{--    <link href="{{ mix('assets/css/custom.css') }}" rel="stylesheet" type="text/css"/> --}}
    {{--    <link href="{{ mix('assets/css/selectize-input.css') }}" rel="stylesheet" type="text/css"/> --}}
    {{--    <link rel="stylesheet" href="{{ mix('web_front/css/hospital-appointment.css') }}"> --}}
@endsection
@routes
@section('content')
    @php
        $hospitalSettingValue = getSettingValue();
    @endphp

    <div class="appointment-page">
        <!-- start hero section -->
        <section
            class="hero-section position-relative p-t-60 border-bottom-right-rounded border-bottom-left-rounded bg-gray overflow-hidden">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 {{ App::getLocale() == 'ar' ? 'text-lg-end' : 'text-lg-start' }} text-center">
                        <div class="hero-content">
                            <h1 class="mb-3 pb-1">
                                {{ __('messages.web_home.make_appointment') }}
                            </h1>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb justify-content-lg-start justify-content-center mb-lg-0 mb-5">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('landing-home') }}">{{ __('messages.web_home.home') }}</a>
                                    </li>
                                    <li class="breadcrumb-item active" aria-current="page">
                                        {{ __('messages.web_home.make_appointment') }}
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <div class="col-lg-6 text-lg-end text-center">
                        <img src="{{ asset('web_front/images/page-banner/make-appointment.png') }}" alt="Infy Care"
                            class="img-fluid" loading="lazy" />
                    </div>
                </div>
            </div>
        </section><!-- end hero section -->

        <?php
        $userName = request()->segment(2);
        ?>
        <section class="appointment-section p-t-120 position-relative">
            <div class="container">
                @include('flash::message')
                <div class="alert alert-danger" id="validationErrorsBox" style="display: none"></div>
                <form id="appointmentForm" class="appointment-form" method="POST">
                <input type="hidden" name="hospital_username" value="{{ request()->segment(2) }}">
                @include('web.home.appointment_fields')
                </form>
            </div>
        </section>
        <!-- start contact section -->
        <section class="contact-details-section p-t-120 p-b-120">
            <div class="container">
                <div class="row mt-xl-5">
                    <div class="col-lg-6">
                        <div class="text-lg-start text-center mb-lg-0 mb-5">
                            <h2 class="mb-3">
                                {{ getFrontSettingValue(\App\Models\FrontSetting::APPOINTMENT, 'appointment_title') }}</h2>
                            <p class="mb-0">
                                {!! getFrontSettingValue(\App\Models\FrontSetting::APPOINTMENT, 'appointment_description') !!}
                            </p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="row">
                            <div class="col-md-6 contact-details-block d-flex align-items-stretch">
                                <div class="card text-center mx-xl-2 flex-fill">
                                    <div class="icon-details-box d-flex align-items-center justify-content-center mx-auto">
                                        <i class="fa-solid fa-phone fs-3"></i>
                                    </div>
                                    <div class="card-body text-center d-flex flex-column pb-4">
                                        <a href="tel:{{ $hospitalSettingValue['hospital_phone']['value'] }}"
                                            class="text-decoration-none fs-5 text-success my-2">
                                            {{ $hospitalSettingValue['hospital_phone']['value'] }}
                                        </a>
                                        <span class="text-secondary fw-light">
                                            {{ __('messages.web_appointment.call_now_and_get_a_free_consulting') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 contact-details-block d-flex align-items-stretch">
                                <div class="card text-center mx-xl-2 flex-fill">
                                    <div class="icon-details-box d-flex align-items-center justify-content-center mx-auto">
                                        <i class="fa-solid fa-envelope fs-3"></i>
                                    </div>
                                    <div class="card-body text-center d-flex flex-column pb-4">
                                        <a href="mailto:{{ $hospitalSettingValue['hospital_email']['value'] }}"
                                            class="text-decoration-none fs-5 text-success my-2">
                                            {{ $hospitalSettingValue['hospital_email']['value'] }}
                                        </a>
                                        <span class="text-secondary fw-light">Contact Hospital</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 text-center">
                    <div class="btn-group mt-4 mt-xl-5">
                        @if ($hospitalSettingValue['facebook_url']['value'] != '' && !empty($hospitalSettingValue['facebook_url']['value']))
                            <a href="{{ $hospitalSettingValue['facebook_url']['value'] }}" class="btn btn-primary fs-4"
                                target="_blank" style="{{ App::getLocale() == 'ar'
                                ? '    border-top-left-radius: 0;
                                border-bottom-left-radius: 0;
                                border-top-right-radius: 10px;
                                border-bottom-right-radius: 10px;;'
                                : '' }}">
                                <i class="fa-brands fa-facebook"></i>
                            </a>
                        @endif
                        @if ($hospitalSettingValue['twitter_url']['value'] != '' && !empty($hospitalSettingValue['twitter_url']['value']))
                            <a href="{{ $hospitalSettingValue['twitter_url']['value'] }}" class="btn btn-primary fs-4"
                                target="_blank">
                                <i class="fa-brands fa-twitter"></i>
                            </a>
                        @endif
                        @if ($hospitalSettingValue['instagram_url']['value'] != '' && !empty($hospitalSettingValue['instagram_url']['value']))
                            <a href="{{ $hospitalSettingValue['instagram_url']['value'] }}" class="btn btn-primary fs-4"
                                target="_blank">
                                <i class="fa-brands fa-instagram"></i>
                            </a>
                        @endif
                        @if ($hospitalSettingValue['linkedIn_url']['value'] != '' && !empty($hospitalSettingValue['linkedIn_url']['value']))
                            <a href="{{ $hospitalSettingValue['linkedIn_url']['value'] }}" class="btn btn-primary fs-4"
                                target="_blank" style="{{ App::getLocale() == 'ar'
                                ? '    border-top-right-radius: 0;
                                border-bottom-right-radius: 0;
                                border-top-left-radius: 10px;
                                border-bottom-left-radius: 10px;;'
                                : '' }}">
                                <i class="fa-brands fa-linkedin-in"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @include('appointments.templates.appointment_slot')
        <input type="hidden" name="doctorDepartmentUrl" class="doctorDepartmentUrl" value="{{ route('appointment.doctor.list') }}">
        <input type="hidden" name="doctorUrl" class="doctorUrl" value="{{ route('appointment.doctors.list') }}">
        <input type="hidden" name="appointmentSaveUrl" class="appointmentSaveUrl" value="{{ route('web.appointments.store') }}">
        <input type="hidden" name="doctorScheduleList" class="doctorScheduleList" value="{{ url('appointment-doctor-schedule-list') }}">
        <input type="hidden" name="isEdit" class="isEdit" value="false">
        <input type="hidden" name="isCreate" class="isCreate" value="true">
        <input type="hidden" name="stripeConfigKey" id="webStripeConfigKey" value="{{ $stripeKey }}">
        <input type="hidden" name="razorpayDataKey" class="razorpayDataKey" value="{{ getSelectedPaymentGateway('razorpay_key') }}">
        <input type="hidden" name="razorpayDataName" class="razorpayDataName" value="{{ getAppName() }}">
        <input type="hidden" name="razorpayDataImage" class="webRazorpayDataImage" value="{{ asset(getLogoUrl()) }}">
        <input type="hidden" name="razorpayDataCallBackURL" class="webRazorpayDataCallBackURL" value="{{ route('web.appointment.razorpay.success') }}">
        <input type="hidden" name="razorpayPaymentFailed" class="patientRazorpayPaymentFailed" value="{{ route('patient.razorpay.failed') }}">
        <input type="hidden" name="getBookingSlot" class="getBookingSlot" value="{{ route('appointment.get.booking.slot') }}">

        @php
            $tenant = Auth::user()->tenant_id;
            $user = App\Models\User::whereTenantId($tenant)->whereNotNull('username')->first();
        @endphp
        @if (getLoggedInUser()->hasRole('Admin'))
        <input type="hidden" name="backUrl" class="backUrl" value="{{ route('appointment', ['username' => Auth::user()->username]) }}">

        @else
        <input type="hidden" name="backUrl" class="backUrl" value="{{ route('appointment', ['username' => $user->username]) }}">

        @endif
        @if (getSettingForReCaptcha($userName))
        <input type="hidden" name="isGoogleCaptchaEnabled" class="isGoogleCaptchaEnabled" value="{{ getSettingForReCaptcha($userName) }}">

        @endif
    </div>

    <!-- End Appointment Form Area -->
@endsection
@section('page_scripts')
    {{-- @if (getSettingForReCaptcha($userName)) --}}
    {{--    <script src='https://www.google.com/recaptcha/api.js'></script> --}}
    {{-- @endif --}}
@endsection
@section('scripts')
    <script src="//js.stripe.com/v3/"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        let options = {
            'key': "{{ getSelectedPaymentGateway('razorpay_key') }}",
            'amount': 0, //  100 refers to 1
            'currency': 'INR',
            'name': "{{ getAppName() }}",
            'order_id': '',
            'description': '',
            'image': "{{ asset(getLogoUrl()) }}", // logo here
            'callback_url': "{{ route('web.appointment.razorpay.success') }}",
            'prefill': {
                'appointment_id': '',
                'amount': '',
                'payment_type': '',
            },
            'readonly': {
                'appointment_id': 'true',
                'amount': 'true',
                'payment_type': 'true',
            },
            'modal': {
                'ondismiss': function() {
                    $.ajax({
                        type: 'POST',
                        url: route('web.appointment.razorpay.failed'),
                        data: $("#appointmentForm").serialize(),
                        success: function(result) {
                            if (result.success) {
                                displayErrorMessage(result.message.message);
                                setTimeout(function() {
                                    window.location.href = result.message.url
                                }, 5000);
                            }
                        },
                        error: function(result) {
                            displayErrorMessage(result.responseJSON.message)
                        },
                    });
                },
            },
        }
    </script>
    <script src="{{ asset('backend/js/moment-round/moment-round.js') }}"></script>
@endsection
