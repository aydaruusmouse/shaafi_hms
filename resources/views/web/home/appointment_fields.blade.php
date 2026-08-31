<?php
$style = 'style=';
$display = 'display:';
?>
<div class="d-lg-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-3">{{ __('messages.web_appointment.make_an_appointment') }}</h2>
    <div class="d-flex align-items-center mb-3">
        <div class="form-check d-flex align-items-center mb-0">
            <input type="radio" name="patient_type" value="1" class="form-check-input new-patient-radio"
                id="newPatient1" checked>
            <label class="form-check-label {{ App::getLocale() == 'ar' ? 'me-5' : 'ms-4' }}" for="newPatient1">
                {{ __('messages.new_patient') }}
            </label>
        </div>
        <div class="form-check ms-4 d-flex align-items-center mb-0">
            <input type="radio" name="patient_type" value="2" class="form-check-input old-patient-radio"
                id="oldPatient1">
            <label class="form-check-label {{ App::getLocale() == 'ar' ? 'me-5' : 'ms-4' }}" for="oldPatient1">
                {{ __('messages.old_patient') }}
            </label>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-4 col-md-6 old-patient d-none">
        <div class="appointment-form__input-block">
            <label for="patient_name" class="form-label">{{ __('messages.appointment.patient_name') }} :<span
                    class="required">*</span></label>
            <input type="text" name="patient_name" class="form-control" id="patientName" autocomplete="off" required
                disabled placeholder="{{ __('messages.appointment.patient_name') }}">
            <input type="hidden" name="patient_id" id="patient">

        </div>
    </div>
    <div class="col-lg-4 col-md-6 first-name-div">
        <div class="appointment-form__input-block">
            <label for="firstName" class="form-label">{{ __('messages.user.first_name') }} :<span
                    class="required">*</span></label>
            <input type="text" name="first_name" class="form-control"
                placeholder="{{ Lang::get('messages.web_appointment.enter_your_first_name') }}" autocomplete="off"
                required id="frontAppointmentFirstName">
        </div>
    </div>
    <div class="col-lg-4 col-md-6 last-name-div">
        <div class="appointment-form__input-block">
            <label for="lastName" class="form-label">{{ __('messages.user.last_name') }} :<span
                    class="required">*</span></label>
            <input type="text" name="last_name" class="form-control"
                placeholder="{{ Lang::get('messages.web_appointment.enter_your_last_name') }}" autocomplete="off"
                required id="frontAppointmentLastName">
        </div>
    </div>
    <div class="col-lg-4 col-md-6 gender-div">
        <div class="appointment-form__input-block">
            <label for="exampleFormControlInput1" class="form-label">{{ __('messages.user.gender') }} :<span
                    class="required">*</span></label>
            <div class="grp-radio d-flex align-items-center">
                <div class="form-check d-flex align-items-center mb-0">
                    <input type="radio" name="gender" value="0" class="form-check-input ms-3 radio_btn"
                        id="flexRadioSm" checked>
                    <label class="form-check-label {{ App::getLocale() == 'ar' ? 'me-5' : 'ms-3' }}"
                        for="radioMale">{{ __('messages.user.male') }}</label>
                </div>
                <div class="form-check ms-4 d-flex align-items-center mb-0">
                    <input type="radio" name="gender" value="1" class="form-check-input radio_btn"
                        id="flexRadioSm">
                    <label class="form-check-label {{ App::getLocale() == 'ar' ? 'me-5' : 'ms-3' }}"
                        for="radioFemale">{{ __('messages.user.female') }}</label>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 patient-email-div">
        <div class="appointment-form__input-block">
            <label for="email" class="form-label">{{ __('messages.user.email') }} :
                <span class="required">*</span>
            </label>
            <input type="email" name="email" class="form-control old-patient-email" placeholder="Enter your email"
                autocomplete="off" required>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 password-div">
        <div class="appointment-form__input-block">
            <label for="password" class="form-label">{{ __('messages.user.password') }} :<span
                    class="required">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required
                min="6" max="10" id="frontAppointmentPassword">
        </div>
    </div>
    <div class="col-lg-4 col-md-6 confirm-password-div">
        <div class="appointment-form__input-block">
            <label for="confirmPassword" class="form-label">{{ __('messages.user.password_confirmation') }} :<span
                    class="required">*</span></label>
            <input type="password" name="password_confirmation" class="form-control"
                placeholder="Enter confirm password" required min="6" max="10"
                id="frontAppointmentConfirmPassword">
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="appointment-form__input-block">
            <label for="selectDepartment" class="form-label">{{ __('messages.appointment.doctor_department') }}
                :<span class="required">*</span></label>

            <select name="department_id" id="frontAppointmentDepartmentId">
                <option value="" selected disabled>Select a department</option>
                @foreach ($departments as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

        </div>
        <input type="hidden" name="doctors" id="doctor"
            value="{{ isset(session()->get('data')['doctorId']) ? session()->get('data')['doctorId'] : '' }}">
        <input type="hidden" name="apdate" id="appointmentDate"
            value="{{ isset(session()->get('data')['appointmentDate']) ? session()->get('data')['appointmentDate'] : '' }}">
        {{--        <input type="hidden" id="doctor" value="{{$data['doctorId']}}"> --}}
        {{--        <input type="hidden" id="frontAppointmentDate" value="{{$data['appointmentDate']}}"> --}}
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="appointment-form__input-block">
            <label for="selectDoctor" class="form-label">{{ __('messages.appointment.doctor') }} :<span
                    class="required">*</span></label>
            <select name="doctor_id" id="frontAppointmentDoctorId" class="selectized" autocomplete="off"
                placeholder="{{ Lang::get('messages.web_appointment.select_doctor') }}">
                <option value="" disabled selected>{{ Lang::get('messages.web_appointment.select_doctor') }}
                </option>
                @foreach ($doctors as $key => $doctor)
                    <option value="{{ $key }}">
                        {{ $doctor }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="appointment-form__input-block">
            <label for="date" class="form-label">{{ __('messages.investigation_report.date') }} :
                <span class="required">*</span>
            </label>
            <input type="text" name="opd_date" id="frontAppointmentOPDDate" class="form-control"
                autocomplete="off" required placeholder="{{ __('messages.investigation_report.date') }}">
        </div>
    </div>
    <div class="col-lg-4 col-md-6 appointmentCharge d-none">
        <div class="form-group mb-5">
            <label for="appointment_charge" class="form-label">{{ __('messages.appointment_charge') }}:</label>
            <input type="number" name="appointment_charge" id="webAppointmentCharge" class="form-control"
                placeholder="0" readonly>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 web-appointment-payment d-none">
        <div class="form-group mb-5">
            <label for="payment_type" class="form-label">{{ __('messages.ipd_payments.payment_mode') }} :
                <span class="required">*</span>
            </label>
            <select name="payment_type" id="webAppointmentPayment" class="selectized"
                placeholder="{{ __('messages.ipd_payments.select_payment_mode') }}">
                <option value="" disabled selected>{{ __('messages.ipd_payments.select_payment_mode') }}
                </option>
                @foreach (getAppointmentPaymentTypes() as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="appointment-form__input-block">
            <label for="description" class="form-label">{{ __('messages.appointment.description') }} :</label>
            <textarea name="problem" class="form-control form-textarea"
                placeholder="{{ __('messages.web_appointment.enter_description') }}" autocomplete="off" rows="4"></textarea>
        </div>
    </div>
    @if (count($customField) >= 0)
        @foreach ($customField as $field)
            @php
                $field_values = $field['values'] ? explode(',', $field['values']) : [];
                $dateType = $field['field_type'] == 6 ? 'customFieldDate' : 'customFieldDateTime';
                $field_type = \App\Models\CustomField::FIELD_TYPE_ARR[$field['field_type']];
                $fieldName = 'field' . $field['id'];
                $fieldData = isset($appointment->custom_field[$fieldName])
                    ? $appointment->custom_field[$fieldName]
                    : null;
            @endphp
            <div class="form-group col-sm-{{ $field['grid'] }} mb-5">
                <label for="{{ $fieldName }}"
                    class="{{ $field['is_required'] == 1 ? 'form-label dynamic-field' : 'form-label' }}">
                    {{ $field['field_name'] }}:
                </label>
                @if ($field['is_required'] == 1)
                    <span class="required">*</span>
                @endif

                @if ($field_type == 'date' || $field_type == 'date & Time')
                    <input type="text" name="{{ $fieldName }}" id="{{ $dateType }}"
                        class="{{ $field['is_required'] == 1 ? 'form-control dynamic-field' : 'bg-white form-control' }}"
                        autocomplete="off" placeholder="{{ $field['field_name'] }}" value="{{ $fieldData }}"
                        {{ $field['is_required'] == 1 ? 'required' : '' }}>
                @elseif ($field_type == 'toggle')
                    <div class="form-check form-switch form-check-custom">
                        <input
                            class="form-check-input w-35px h-20px is-active {{ $field['is_required'] == 1 ? 'dynamic-field' : '' }}"
                            name="{{ $fieldName }}" type="checkbox" value="1" tabindex="8"
                            {{ $fieldData == 0 ? '' : 'checked' }}>
                    </div>
                @elseif ($field_type == 'select')
                    <select name="{{ $fieldName }}" id="customFieldSelect"
                        class="{{ $field['is_required'] == 1 ? 'dynamic-field custom-field-select' : 'custom-field-select' }}">
                        <option value="" disabled selected>{{ $field['field_name'] }}</option>
                        @foreach ($field_values as $value)
                            <option value="{{ $value }}"
                                {{ isset($fieldData) && $fieldData == $value ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                @elseif ($field_type == 'multiSelect')
                    <select name="{{ $fieldName }}[]" id="customFieldMultiSelect"
                        class="{{ $field['is_required'] == 1 ? 'appointment-multi-select dynamic-field custom-field-multi-select' : 'appointment-multi-select custom-field-multi-select' }}"
                        multiple>
                        <option value="" disabled>{{ $field['field_name'] }}</option>
                        @foreach ($field_values as $value)
                            <option value="{{ $value }}"
                                {{ isset($fieldData) && in_array($value, $fieldData) ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input type="text" name="{{ $fieldName }}"
                        class="{{ $field['is_required'] == 1 ? 'form-control dynamic-field' : 'form-control' }}"
                        placeholder="{{ $field['field_name'] }}" value="{{ $fieldData }}"
                        {{ $field['is_required'] == 1 ? 'required' : '' }}>
                @endif
            </div>
        @endforeach
    @endif
    <div class="form-group col-sm-12 appointment-slot {{ App::getLocale() == 'ar' ? 'text-end' : 'text-start' }}">
        <div class="doctor-schedule" style="display: none">
            <i class="fas fa-calendar-alt"></i>
            <span class="day-name"></span>
            <span class="schedule-time"></span>
        </div>
        <strong class="error-message" style="display: none"></strong>
        <div class="slot-heading mb-4">
            <strong class="available-slot-heading" style="display: none">{{ __('messages.available_slot') }}:<span
                    class="required">*</span></strong>
        </div>
        <div class="row">
            <div class="available-slot form-group col-sm-10">
            </div>
        </div>
        <?php
        $userName = request()->segment(2);
        $isEnabledGoogleCapcha = getSettingForReCaptcha($userName);
        ?>
        @if ($isEnabledGoogleCapcha == true)
            <div class="form-group col-xl-12">
                @if (config('app.recaptcha.key'))
                    <div class="g-recaptcha" id="appointment-g-recaptcha"
                        data-sitekey="{{ config('app.recaptcha.key') }}">
                    </div>
                @endif
            </div>
        @endif
        <input type="hidden" name="appointment-g-recaptcha" id="appointmentGRecaptcha"
            value="{{ config('app.recaptcha.key') }}">
        <div class="color-information d-none" align="right" style="display: none">
            <span><i class="fa fa-circle fa-xs" aria-hidden="true"> </i>
                {{ __('messages.bed.not_available') }}</span>
        </div>
    </div>
    <div class="col-lg-12 text-center mt-4">
        <button type="submit" class="btn btn-primary custom-btn-lg"
            id="btnSave">{{ __('messages.common.save') }}</button>
    </div>
</div>
