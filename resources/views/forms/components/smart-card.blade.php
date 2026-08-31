<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">

        <div class="col-xl-8 col-md-9 mb-md-0 mb-5 px-0">
            <div class="flex items-start justify-center">
                <div class="shadow-lg bg-white dark:bg-gray-800 rounded-lg overflow-hidden border">
                    @if ($getRecord())
                        <div class="flex items-center justify-between p-4 bg-blue-600 dark:bg-blue-700 border-b"
                            id="headerColor" style="background-color:{{ $getRecord()->header_color }}">
                        @else
                            <div class="flex items-center justify-between p-4 bg-blue-600 dark:bg-blue-700 border-b"
                                id="headerColor">
                    @endif
                    <div class="flex items-center">
                        <div style="margin-inline-end: 13px;"> <!-- Increased margin here -->
                            <img src="{{ asset(getLogoUrl()) }}" alt="logo" class="h-10 w-10" />
                        </div>
                        <h4 class="text-white mb-0 fw-bold ">{{ getAppName() }}</h4>
                    </div>
                    <address class="text-white text-sm mb-0 text-right">
                        <p class="mb-0">16/A saint Joseph Park</p>
                    </address>
                </div>
                <div class="p-6 bg-white dark:bg-gray-900">
                    <div class="flex flex-wrap justify-between">
                        <div class="flex-1">
                            <div class="flex mb-4">
                                <div class="rounded-full overflow-hidden"
                                    style="margin-inline-end: 20px; width: 100px; height: 100px;">
                                    <!-- Set desired width and height -->
                                    <img src="{{ asset('front-assets/images/profile.png') }}" alt=""
                                        class="w-full h-full object-cover">
                                    <!-- Use h-full to maintain aspect ratio -->
                                </div>
                                <div class="flex-1">
                                    <table class="table-auto w-full text-left" style="margin-right:100px;">
                                        <tbody>
                                            <tr style="height: 1rem;">
                                                <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('messages.bed.name') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">James
                                                    Bond</td>
                                            </tr>
                                            @if ($getRecord())
                                                <tr style="height: 1rem;" id="email"
                                                    class={{ $getRecord()->show_email == true ? '' : 'hidden' }}>
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('auth.email') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        marian@gmail.com</td>
                                                </tr>
                                                <tr style="height: 1rem;" id="phone"
                                                    class={{ $getRecord()->show_phone == true ? '' : 'hidden' }}>
                                                    <td class="pr-2 font-medium "
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.enquiry.contact') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        1234567890</td>
                                                </tr>
                                                <tr style="height: 1rem;" id="dob"
                                                    class={{ $getRecord()->show_dob == true ? '' : 'hidden' }}>
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.lunch_break.dob') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        25/02/2006</td>
                                                </tr>
                                                <tr style="height: 1rem;" id="blood_group"
                                                    class={{ $getRecord()->blood_group == true ? '' : 'hidden' }}>
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.user.blood_group') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">A+
                                                    </td>
                                                </tr>
                                                <tr style="height: 1rem;" id="insurance"
                                                    class={{ $getRecord()->insurance == true? '' : 'hidden' }}>
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.insurance.insurance') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">1234
                                                    </td>
                                            @else
                                                <tr style="height: 1rem;" id="email">
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('auth.email') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        marian@gmail.com</td>
                                                </tr>
                                                <tr style="height: 1rem;" id="phone">
                                                    <td class="pr-2 font-medium "
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.enquiry.contact') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        1234567890</td>
                                                </tr>
                                                <tr style="height: 1rem;" id="dob">
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.lunch_break.dob') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        25/02/2006</td>
                                                </tr>
                                                <tr style="height: 1rem;" id="blood_group">
                                                    <td class="pr-2 font-medium"
                                                        style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.user.blood_group') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">A+
                                                    </td>
                                                </tr>
                                                <tr style="height: 1rem;" id="insurance">
                                                    <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ __('messages.insurance.insurance') }}:</td>
                                                    <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">1234
                                                    </td>
                                            @endif

                                        </tbody>

                                    </table>
                                </div>
                            </div>
                            @if ($getRecord())
                                <div class="flex items-center" id="address"
                                    class={{ $getRecord()->show_address == true ? '' : 'hidden' }}>
                                    <span class="font-medium"
                                        style="margin-right: 12px;">{{ __('messages.common.address') }}:</span>
                                    <address class="mb-0">
                                        D.No.1 Street name Address line 2 line 3
                                    </address>
                                </div>
                            @else
                                <div class="flex items-center" id="address">
                                    <span class="font-medium"
                                        style="margin-right: 12px;">{{ __('messages.common.address') }}:</span>
                                    <address class="mb-0">
                                        D.No.1 Street name Address line 2 line 3
                                    </address>
                                </div>
                            @endif
                        </div>

                        <div class="w-1/4">
                            <div class="text-right mb-5">
                                <div class="qr-code mb-4">
                                    {{-- {!! QrCode::size(90)->generate('https://hms-saas.test/h/sims/patient-details/700XYs') !!} --}}
                                    {!! generateQrCode('https://hms-saas.test/h/sims/patient-details/700XYs', 90) !!}
                                </div>
                                @if ($getRecord())
                                    <h6 class="text-primary" style="text-align:center;" id="patientUniqueID"
                                        class={{ $getRecord()->show_patient_unique_id == true ? '' : 'hidden' }}>
                                        {{ __('messages.lunch_break.id') }}:1001
                                    </h6>
                                @else
                                    <h6 class="text-primary" style="text-align:center;" id="patientUniqueID">
                                        {{ __('messages.lunch_break.id') }}:1001
                                    </h6>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            $(document).ready(function() {
                $('#show_email').change(function() {
                    if ($(this).is(':checked')) {
                        $("#email").removeClass('hidden');
                    } else {
                        $("#email").addClass('hidden');
                    }
                });
                $('#show_phone').change(function() {
                    if ($(this).is(':checked')) {
                        $("#phone").removeClass('hidden');
                    } else {
                        $("#phone").addClass('hidden');
                    }
                });
                $('#show_dob').change(function() {
                    if ($(this).is(':checked')) {
                        $("#dob").removeClass('hidden');
                    } else {
                        $("#dob").addClass('hidden');
                    }
                });
                $('#show_blood_group').change(function() {
                    if ($(this).is(':checked')) {
                        $("#blood_group").removeClass('hidden');
                    } else {
                        $("#blood_group").addClass('hidden');
                    }
                });
                $('#show_address').change(function() {
                    if ($(this).is(':checked')) {
                        $("#address").removeClass('hidden');
                    } else {
                        $("#address").addClass('hidden');
                    }
                });
                $('#show_patient_unique_id').change(function() {
                    if ($(this).is(':checked')) {
                        $("#patientUniqueID").removeClass('hidden');
                    } else {
                        $("#patientUniqueID").addClass('hidden');
                    }
                });
                $('#show_insurance').change(function() {
                    if ($(this).is(':checked')) {
                        $("#insurance").removeClass('hidden');
                    } else {
                        $("#insurance").addClass('hidden');
                    }
                });

            });
        });
    </script>
</x-dynamic-component>
