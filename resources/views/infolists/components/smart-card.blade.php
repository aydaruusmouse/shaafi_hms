<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">

    @php
         $user = App\Models\User::whereTenantId(getLoggedInUser()->tenant_id)->first();

        if(getLoggedinPatient()){
            $username = getLoggedInUser()->username;
        }else{
            $username = $user->username;
        }

    @endphp
    <div class="col-xl-8 col-md-9 mb-md-0 mb-5 px-0">
        <div class="flex items-start justify-center">
            <div class="shadow-lg bg-white dark:bg-gray-800 rounded-lg overflow-hidden border">

                <div class="flex items-center justify-between p-4 bg-blue-600 dark:bg-blue-700 border-b" id="headerColor"
                    style="background-color:{{ $record->SmartCardTemplate->header_color }}">

                    <div class="flex items-center">
                        <div style="margin-inline-end: 13px;"> <!-- Increased margin here -->
                            <img src="{{ asset(getLogoUrl()) }}" alt="logo" class="h-10 w-10" />
                        </div>
                        <h4 class="text-white mb-0 fw-bold ">{{ getAppName() }}</h4>
                    </div>
                    <address class="text-white text-sm mb-0 text-right">
                        <p class="mb-0">{{ getSettingValueByKey('hospital_address') }}
                        </p>
                    </address>
                </div>
                <div class="p-6 bg-white dark:bg-gray-900">
                    <div class="flex flex-wrap justify-between">
                        <div class="flex-1">
                            <div class="flex mb-4">
                                <div class="rounded-full overflow-hidden"
                                    style="margin-inline-end: 20px; width: 100px; height: 100px;">
                                    <img src={{ $record->user->profile == 'http://hms-saas-filament.test/images/hms-saas-logo.png' ? getUserImageInitial($record->id, $record->user->full_name) : $record->user->profile }}
                                        alt="" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1">
                                    <table class="table-auto w-full text-left" style="margin-right:100px;">
                                        <tbody>
                                            <tr style="height: 1rem;">
                                                <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('messages.bed.name') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ $record->user->full_name }}</td>
                                            </tr>

                                            <tr style="height: 1rem;" id="email"
                                                class={{ $record->SmartCardTemplate->show_email == true ? '' : 'hidden' }}>
                                                <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('auth.email') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ $record->user->email }}</td>
                                            </tr>
                                            <tr style="height: 1rem;" id="phone"
                                                class={{ $record->SmartCardTemplate->show_phone == true ? '' : 'hidden' }}>
                                                <td class="pr-2 font-medium "
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('messages.enquiry.contact') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ $record->user->phone ?? __('messages.common.n/a') }}</td>
                                            </tr>
                                            <tr style="height: 1rem;" id="dob"
                                                class={{ $record->SmartCardTemplate->show_dob == true ? '' : 'hidden' }}>
                                                <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('messages.lunch_break.dob') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ $record->user->dob ?? __('messages.common.n/a') }}</td>
                                            </tr>
                                            <tr style="height: 1rem;" id="blood_group"
                                                class={{ $record->SmartCardTemplate->blood_group == true ? '' : 'hidden' }}>
                                                <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('messages.user.blood_group') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ $record->user->blood_group ?? __('messages.common.n/a') }}
                                                </td>
                                            </tr>
                                            <tr style="height: 1rem;" id="insurance"
                                                class={{ $record->SmartCardTemplate->show_insurance == true ? '' : 'hidden' }}>
                                                <td class="pr-2 font-medium"
                                                    style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                    {{ __('messages.insurance.insurance') }}:</td>
                                                <td style="padding-top: 0.50rem; padding-bottom: 0.25rem;">
                                                        {{ $record->admissions->first()->insurance->name ?? __('messages.common.n/a') }}
                                                </td>
                                            </tr>

                                        </tbody>

                                    </table>
                                </div>
                            </div>

                            <div class="flex items-center" id="address"
                                class={{ $record->SmartCardTemplate->show_address == true ? '' : 'hidden' }}>
                                <span class="font-medium"
                                    style="margin-right: 12px;">{{ __('messages.common.address') }}:</span>
                                <address class="mb-0">
                                    <p class="mb-0">{{ $record->address->address1 ?? ''  }}</p>
                                </address>
                            </div>

                        </div>

                        <div class="w-1/4">
                            <div class="text-right mb-5">
                                <div class="qr-code mb-4">
                                    {{-- {!! QrCode::size(90)->generate(route('patient.details',[$username,$record->patient_unique_id])) !!} --}}
                                    {!! generateQrCode(route('patient.details', [$username, $record->patient_unique_id]), 90) !!}
                                </div>

                                <h6 class="text-primary" style="text-align:center;" id="patientUniqueID"
                                    class={{ $record->SmartCardTemplate->show_patient_unique_id == true ? '' : 'hidden' }}>
                                    {{ __('messages.lunch_break.id') }}:
                                    {{ $record->patient_unique_id }}
                                </h6>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-dynamic-component>
