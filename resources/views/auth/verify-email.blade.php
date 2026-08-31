<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="title" content="{{ config('app.name') }}">
    {{-- <meta name="keywords" content="{{getCompanyName()}}"/> --}}
    <meta name="description" content="{{ getAppName() }}" />
    {{-- <meta name="author" content="{{getCompanyName()}}"> --}}
    @php
        $hospitalSettingValue = getSettingValue();
        App::setLocale('en');
    @endphp
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset($hospitalSettingValue['favicon']['value']) }}" type="image/png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Email Verification | {{ getSuperAdminSettingKeyValue('app_name') }}</title>  
    @yield('page_css')
</head>

<body style="font-family: system-ui, apple-system; background-color: #f9fafb; margin: 0; padding: 0; ">
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; width: 100hh;  text-align: center;">
        <div style="max-width: 500px; width: 100%; padding: 20px;">
            <div style="display: flex; justify-content: center; align-items: center;">
                <img src="{{ asset('web/img/verification.png') }}"
                    style="max-width: 1700px; width: 170%; height: auto; margin-bottom: 20px;" alt="Verification">
            </div>
            <h2 style="font-size: 2rem; color: #333;">{{ __('auth.verify_email') }}</h2>
            <p style="font-size: 16px; color: #666; text-align: center; margin: 0 auto;">
                {{ __('auth.check_email_before_verify') }}
            </p>

            <form method="POST" action="{{ route('verification.send') }}" style="margin-top: 20px;">
                @csrf
                <button type="submit"
                    style="background-color: #fd8e4b; color: rgb(0, 0, 0); padding: 10px 20px; font-size: 16px; border: none; border-radius: 5px; cursor: pointer;">
                    {{ __('auth.request_another') }}
                </button>
            </form>

            <form action="{{ route('auth.logout') }}" method="post">
                @csrf
                <button type="submit"
                    style="display: inline-block; margin-top: 10px; background-color: #fd8e4b;; color: rgb(0, 0, 0); padding: 10px 20px; font-size: 16px; border: none; border-radius: 5px; text-decoration: none; cursor: pointer;">
                    {{ __('messages.user.logout') }}
                </button>
            </form>
        </div>
    </div>
</body>

</html>
