<!DOCTYPE html>
<html @if (App::getLocale() == 'ar') direction="rtl" dir="rtl" style="direction: rtl" @endif>

<head>
    <meta charset="UTF-8">
    <title>@yield('title') | {{ getSuperAdminSettingKeyValue('app_name') }} </title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="google" content="notranslate">
    @php
        $settingValue = getSuperAdminSettingValue();
    @endphp

    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="turbo-cache-control" content="no-cache">
    {{-- @dd(App::getLocale()) --}}
    @if (App::getLocale() == 'ar')
        <link href="{{ asset('front-assets/css/landing-rtl.css') }}" rel="stylesheet" type="text/css" />
    @endif
    <link rel="icon" href="{{ asset($settingValue['favicon']['value']) }}" type="image/png">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />

    @vite('resources/css/landing_front.css')
    @vite('resources/assets/sass/selectize-input.scss')

    {{-- <link rel="stylesheet" href="{{ asset('landing_front/css/landing-third-party.css') }}"> --}}
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css"> --}}

    @vite('resources/css/landing-pages.css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    @yield('page_css')
    @yield('css')
    @routes
    <script src="{{ asset('web_front/js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/landing-third-party.js') }}"></script>
    <script src="{{ asset('/web_front/js/slick.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/jquery.meanmenu.js') }}"></script>
    <script src="{{ asset('/web_front/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/jquery-ui.js') }}"></script>
    <script src="{{ asset('/web_front/js/jquery.appear.js') }}"></script>
    <script src="{{ asset('/web_front/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/aos.js') }}"></script>
    <script src="{{ asset('/web_front/js/jquery.ajaxchimp.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/form-validator.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/selectize.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/select2.min.js') }}"></script>
    <script src="{{ asset('/web_front/js/main.js') }}"></script>
    <script src="{{ asset('/web_front/js/toastr.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <script src="//js.stripe.com/v3/"></script>
    @livewireScripts
    @livewireStyles



    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit"></script>
    @vite('resources/assets/js/turbo.js')
    @vite('resources/assets/theme/js/plugins.js')
    @vite('resources/assets/js/custom/helpers.js')
    @vite('resources/assets/js/custom/custom.js')
    {{-- @vite('resources/assets/js/super_admin/contact_enquiry/web.js') --}}
    @vite('resources/assets/js/subscriptions/free-subscription.js')
    @vite('resources/assets/js/subscriptions/subscription-option.js')
    @vite('resources/assets/js/subscriptions/subscription.js')
    @vite('resources/assets/js/subscribe/create.js')
    @vite('resources/assets/js/super_admin/contact_enquiry/contact_enquiry.js')
    @vite('resources/assets/js/landing/languageChange/languageChange.js')
    @vite('resources/js/hospital_type/hospital_type.js')
    {{-- @vite('resources/assets/js/custom/phone-number-country-code.js') --}}
    <script src="{{ asset('messages.js') }}"></script>

    @vite('resources/js/landing-front-pages.js')
    {{-- <script src="{{ mix('js/landing-front-pages.js') }}"></script> --}}
    <script>
        // $(document).ready(function(){
        //     $('.payment-type').selectize();
        // });
        if ($('.mySwiper').length) {
            var swiper = new Swiper('.mySwiper', {
                spaceBetween: 40,
                centeredSlides: false,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                breakpoints: {
                    320: {
                        slidesPerView: 1.2,
                        spaceBetween: 20,
                    },
                    576: {
                        slidesPerView: 1.5,
                        spaceBetween: 20,
                    },
                    992: {
                        slidesPerView: 2.5,
                    },
                    1400: {
                        slidesPerView: 3.5,
                    },
                },
            })
        }

        setTimeout(function() {
            $('.custom-message').fadeOut()
        }, 2000)
    </script>
    <script data-turbo-eval="false">
        let frontLanguage = "{{ checkLanguageSession() }}";
        Lang.setLocale(frontLanguage);
    </script>
</head>

<body>

    {{-- <div class="page-wrapper"> --}}
    {{--    <div id="ht-preloader"> --}}
    {{--        <div class="clear-loader"> --}}
    {{--            <div class="loader"> --}}
    {{--                <div class="loader-div"><span>{{ getAppName()}}</span> --}}
    {{--                </div> --}}
    {{--            </div> --}}
    {{--        </div> --}}
    {{--    </div> --}}
    <div class="page-wrapper">

        @include('landing.layouts.header')

        @yield('content')

        @include('landing.layouts.footer')
    </div>
    {{-- <input type="hidden" name="invalidNumber" value="{{ __('messages.common.invalid_number') }}"
        class="invalidNumber">
    <input type="hidden" name="invalidCountryNumber" value="{{ __('messages.common.invalid_country_code') }}"
        class="invalidCountryNumber">
    <input type="hidden" name="tooShort" value="{{ __('messages.common.too_short') }}" class="tooShort">
    <input type="hidden" name="tooLong" value="{{ __('messages.common.too_long') }}" class="tooLong"> --}}


    {{-- <script src="{{asset('landing_front/js/jquery.min.js')}}"></script> --}}
    {{-- <script src="{{ asset('assets/js/custom/helpers.js') }}"></script> --}}
    {{-- <script src="{{asset('landing_front/js/swiper-bundle.min.js')}}"></script> --}}
    {{-- <script src="{{asset('landing_front/js/bootstrap.bundle.min.js')}}"></script> --}}
    {{-- <script src="{{ asset('landing_front/js/jquery.toast.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('landing_front/js/toast.js') }}"></script> --}}
    {{-- <script src="{{ mix('assets/js/custom/helpers.js') }}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/common-theme.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/jquery.nice-select.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/owl-carousel/owl.carousel.min.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/magnific-popup/jquery.magnific-popup.min.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/counter/counter.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/isotope/isotope.pkgd.min.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/particles.min.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/vivus/pathformer.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/vivus/vivus.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/raindrops/jquery-ui.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/raindrops/raindrops.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/countdown/jquery.countdown.min.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/contact-form/contact-form.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/wow.min.js')}}"></script> --}}
    {{-- <script src="{{asset('assets/landing-theme/js/theme-script.js')}}"></script> --}}
    {{-- <script src="{{ asset('assets/js/landing/languageChange/languageChange.js') }}"></script> --}}
    {{-- <script src="{{ mix('assets/js/subscribe/create.js') }}"></script> --}}
    {{-- <script src="https://js.stripe.com/v3/"></script> --}}
    @yield('page_scripts')
    @yield('scripts')
    {{-- {{ Form::hidden('curruntLanguage', App::getLocale(), ['class' => 'curruntLanguage']) }} --}}
</body>

</html>

{{-- <!DOCTYPE html> --}}
{{-- <html> --}}
{{-- <head> --}}
{{--    <meta charset="UTF-8"> --}}
{{--    <title>@yield('title') | {{ getAppName()}} </title> --}}
{{--    <meta name="csrf-token" content="{{ csrf_token() }}"/> --}}
{{--    <meta name="google" content="notranslate"> --}}
{{--    @php --}}
{{--        $settingValue = getSuperAdminSettingValue(); --}}
{{--    @endphp --}}
{{--    <meta name="viewport" content="width=device-width, initial-scale=1"/> --}}
{{--    <link rel="icon" href="{{ $settingValue['favicon']['value'] }}" type="image/png"> --}}
{{--    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Poppins:300,400,500,600,700"/> --}}

{{--    <link href="{{asset('assets/landing-theme/css/bootstrap.min.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/animate.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/fontawesome-all.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/line-awesome.min.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/magnific-popup/magnific-popup.css')}}" rel="stylesheet" --}}
{{--          type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/owl-carousel/owl.carousel.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/spacing.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/base.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/shortcodes.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/style.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/responsive.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/landing-theme/css/theme-color/color-5.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    <link href="{{asset('assets/css/landing/landing.css')}}" rel="stylesheet" type="text/css"/> --}}
{{--    @yield('page_css') --}}
{{--    @yield('css') --}}
{{-- </head> --}}
{{-- <body> --}}

{{-- <div class="page-wrapper"> --}}
{{--    <div id="ht-preloader"> --}}
{{--        <div class="clear-loader"> --}}
{{--            <div class="loader"> --}}
{{--                <div class="loader-div"><span>{{ getAppName()}}</span> --}}
{{--                </div> --}}
{{--            </div> --}}
{{--        </div> --}}
{{--    </div> --}}
{{-- <div class="page-wrapper"> --}}
{{-- @include('landing.layouts.header') --}}

{{-- @yield('content') --}}

{{-- <div id="waterdrop"></div> --}}
{{-- @include('landing.layouts.footer') --}}
{{-- </div> --}}

{{-- @routes --}}
{{-- <script src="{{asset('assets/landing-theme/js/common-theme.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/jquery.nice-select.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/owl-carousel/owl.carousel.min.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/magnific-popup/jquery.magnific-popup.min.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/counter/counter.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/isotope/isotope.pkgd.min.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/particles.min.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/vivus/pathformer.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/vivus/vivus.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/raindrops/jquery-ui.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/raindrops/raindrops.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/countdown/jquery.countdown.min.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/contact-form/contact-form.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/wow.min.js')}}"></script> --}}
{{-- <script src="{{asset('assets/landing-theme/js/theme-script.js')}}"></script> --}}
{{-- <script src="{{ asset('assets/js/landing/languageChange/languageChange.js') }}"></script> --}}
{{-- <script src="{{ mix('assets/js/subscribe/create.js') }}"></script> --}}
{{-- <script> --}}
{{--    setTimeout(function () { --}}
{{--        $('.custom-message').fadeOut(); --}}
{{--    }, 2000) --}}
{{-- </script> --}}
{{-- @yield('page_scripts') --}}
{{-- @yield('scripts') --}}
{{-- </body> --}}
{{-- </html> --}}
