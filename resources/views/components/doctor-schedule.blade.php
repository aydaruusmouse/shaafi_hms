@if (!empty($errors))
    @if ($errors->any())
        <div class="alert alert-danger">
            <div>
                <div class="d-flex">
                    <i class="fa-solid fa-face-frown me-5"></i>
                    <span class="mt-1">{{ $errors->first() }}</span>
                </div>
            </div>
        </div>
    @endif
@endif
<div class="alert alert-danger d-none" id="createAppointmentErrorsBox"></div>

<div class="form-group col-sm-6 mb-5">
    @vite('resources/css/app.css')
    <div class="doctor-schedule" style="display: none">
        <i class="fas fa-calendar-alt"></i>
        <span class="day-name"></span>
        <span class="schedule-time"></span>
    </div>
    <strong class="error-message" style="display: none"></strong>
    <div class="slot-heading">
        <h3 class="available-slot-heading required" style="display: none">
            {{ __('Available Slot:') . ':' }}</h3>
    </div>
    <div class="row">
        <div class="available-slot form-group col-sm-12">
        </div>
    </div>
    <div align="right" style="display: none">
        <span><i class="fa fa-circle color-information" aria-hidden="true"> </i>
            {{ __('messages.appointment.no_available') }}</span>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
    integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"
    integrity="sha512-K/oyQtMXpxI4+K0W7H25UopjM8pzq0yrVdFdG21Fh5dBe91I40pDd9A4lzNlHPHBIP2cwZuoxaUSX0GJSObvGA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css"
    integrity="sha512-MQXduO8IQnJVq1qmySpN87QQkiR1bZHtorbJBD0tzy7/0U9+YIC93QWHeGTEoojMVHWWNkoCp8V6OzVSYrX0oQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdn.jsdelivr.net/npm/jsrender@1.0.10/jsrender.min.js"></script>


<script>
    function prepareTemplateRender(templateSelector, data) {
        let template = $(templateSelector).html();
        return $.templates(template).render(data); // Assuming using JsRender
    }
    loadAppointmentCreateEdit();

    function loadAppointmentCreateEdit() {

        // if ($("#appointmentForm").length || $("#editAppointmentForm").length) {

        const appointmentPatientIdElement = $("#appointmentsPatientId");
        const appointmentDoctorIdElement = $("#appointmentDoctorId");
        const appointmentDepartmentIdElement = $("#appointmentDepartmentId");

        if (appointmentPatientIdElement.length) {
            $("#appointmentsPatientId").first().focus();
        }

        // if ($("#appointmentPayment").length) {


        var appointmentSelectedDate;
        var appointmentIntervals;
        var appointmentAlreadyCreateTimeSlot;
        var appointmentBreakIntervals;
        let opdDate = $(".opdDate").flatpickr({
            enableTime: false,
            // minDate: moment().subtract(1, 'days').format(),
            minDate: moment(new Date()).format("YYYY-MM-DD"),
            dateFormat: "Y-m-d",
            locale: $(".userCurrentLanguage").val(),
            onChange: function(selectedDates, dateStr, instance) {
                // if (!isEmpty(dateStr)) {
                $(".doctor-schedule").css("display", "none");
                $(".error-message").css("display", "none");
                $(".available-slot-heading").css("display", "none");
                $(".color-information").css("display", "none");
                $(".available-slot").css("display", "none");
                $(".time-slot").remove();
                // if ($("#appointmentDepartmentId").val() == null) {
                //     $("#createAppointmentErrorsBox")
                //         .show()
                //         .html(
                //             "please select doctor department"
                //         );
                //     $("#createAppointmentErrorsBox")
                //         .delay(5000)
                //         .fadeOut();
                //     $(".opdDate").val("");
                //     opdDate.clear();
                //     return false;
                // } else if ($("#appointmentDoctorId").val() == "") {
                //     console.log("doctor id", $("#appointmentDoctorId").val());
                //     $("#createAppointmentErrorsBox")
                //         .show()
                //         .html("Please select doctor");
                //     $("#createAppointmentErrorsBox")
                //         .delay(5000)
                //         .fadeOut();
                //     $(".opdDate").val("");
                //     opdDate.clear();
                //     return false;
                // }
                var weekday = [
                    "Sunday",
                    "Monday",
                    "Tuesday",
                    "Wednesday",
                    "Thursday",
                    "Friday",
                    "Saturday",
                ];

                var selected = new Date(dateStr);
                let dayName = weekday[selected.getDay()];
                appointmentSelectedDate = dateStr;

                //if dayName is blank, then ajax call not run.
                if (dayName == null || dayName == "") {
                    return false;
                }

                //get doctor schedule list with time slot.
                $.ajax({
                    type: "GET",
                    url: "{{ route('doctor.schedule') }}",
                    data: {
                        day_name: dayName,
                        doctor_id: appointmentDoctorId,
                        date: appointmentSelectedDate,
                    },
                    success: function(result) {
                        if (result.success) {
                            if (result.data != "") {
                                if (
                                    result.data.scheduleDay.length !=
                                    0 &&
                                    result.data.doctorHoliday.length ==
                                    0
                                ) {
                                    let availableFrom = "";
                                    if (
                                        moment(new Date()).format(
                                            "YYYY-MM-DD"
                                        ) === dateStr
                                    ) {
                                        availableFrom = moment().ceil(
                                            moment
                                            .duration(
                                                result.data
                                                .perPatientTime[0]
                                                .per_patient_time
                                            )
                                            .asMinutes(),
                                            "minute"
                                        );
                                        availableFrom = moment(
                                            availableFrom.toString()
                                        ).format("H:mm:ss");
                                        // availableFrom = moment(new Date()).
                                        //     add(result.data.perPatientTime[0].per_patient_time,
                                        //         'minutes').
                                        //     format('H:mm:ss');
                                    } else {
                                        availableFrom =
                                            result.data.scheduleDay[0]
                                            .available_from;
                                    }

                                    var doctorStartTime =
                                        appointmentSelectedDate +
                                        " " +
                                        availableFrom;
                                    var doctorEndTime =
                                        appointmentSelectedDate +
                                        " " +
                                        result.data.scheduleDay[0]
                                        .available_to;
                                    if (moment(doctorEndTime).isBefore(moment())) {
                                        $(".doctor-schedule").css("display",
                                            "none");
                                        $(".color-information").css("display",
                                            "none");
                                        $(".available-slot").css("display", "none");
                                        $(".error-message").css("display", "block");
                                        $(".error-message").html(
                                            "js.doctor_schedule_not_available_on_this_date"
                                        );
                                        return;
                                    }

                                    var doctorPatientTime =
                                        result.data.perPatientTime[0]
                                        .per_patient_time;
                                    //perPatientTime convert to Minute
                                    var a =
                                        doctorPatientTime.split(
                                            ":"); // split it at the colons
                                    var minutes = +a[0] * 60 + +a[
                                        1]; // convert to minute
                                    //parse In

                                    var startTime =
                                        appointmentParseIn(
                                            doctorStartTime
                                        );
                                    var endTime =
                                        appointmentParseIn(
                                            doctorEndTime
                                        );
                                    //call to getTimeIntervals function
                                    appointmentIntervals =
                                        appointmentGetTimeIntervals(
                                            startTime,
                                            endTime,
                                            minutes
                                        );
                                    if (
                                        result.data.doctorBreak != null
                                    ) {
                                        for (
                                            var breakIndex = 0; breakIndex <
                                            result.data.doctorBreak
                                            .length;
                                            ++breakIndex
                                        ) {
                                            var startBreakTime =
                                                appointmentParseIn(
                                                    appointmentSelectedDate +
                                                    " " +
                                                    result.data
                                                    .doctorBreak[
                                                        breakIndex
                                                    ].break_from
                                                );

                                            var endBreakTime =
                                                appointmentParseIn(
                                                    appointmentSelectedDate +
                                                    " " +
                                                    result.data
                                                    .doctorBreak[
                                                        breakIndex
                                                    ].break_to
                                                );

                                            appointmentBreakIntervals =
                                                appointmentGetTimeIntervals(
                                                    startBreakTime,
                                                    endBreakTime,
                                                    1
                                                );
                                            appointmentIntervals =
                                                appointmentIntervals.filter(
                                                    (slot) =>
                                                    !appointmentBreakIntervals
                                                    .includes(
                                                        slot
                                                    )
                                                );
                                        }
                                    }

                                    //if intervals array length is grater then 0 then process
                                    if (
                                        appointmentIntervals.length > 0
                                    ) {
                                        $(
                                            ".available-slot-heading"
                                        ).css("display", "block");
                                        $(".color-information").css(
                                            "display",
                                            "block"
                                        );
                                        $(".available-slot").css(
                                            "display",
                                            "block"
                                        );
                                        var index;
                                        let timeStlots = "";
                                        for (
                                            index = 0; index <
                                            appointmentIntervals.length;
                                            ++index
                                        ) {
                                            let data = [{
                                                index: index,
                                                timeSlot: appointmentIntervals[
                                                    index
                                                ],
                                            }, ];
                                            // var timeSlot =
                                            //     prepareTemplateRender(
                                            //         "#appointmentSlotTemplate",
                                            //         data
                                            //     );

                                            var timeSlot = `
                                                        <section class="time-slot">
                                                            <span class="time-interval" data-id="${index}">${appointmentIntervals[index]}</span>
                                                        </section>
                                                    `;
                                            timeStlots += timeSlot;
                                        }


                                        $(".available-slot").append(
                                            timeStlots
                                        );
                                    }
                                    console.log(availableFrom);
                                    // display Day Name and time
                                    if (availableFrom != "00:00:00" &&
                                        result.data.scheduleDay[0]
                                        .available_to !=
                                        "00:00:00" &&
                                        doctorStartTime != doctorEndTime
                                    ) {
                                        console.log("availableFrom");
                                        $(".doctor-schedule").css(
                                            "display",
                                            "block"
                                        );
                                        $(".color-information").css(
                                            "display",
                                            "none"
                                        );
                                        $(".available-slot").css(
                                            "display",
                                            "block"
                                        );
                                        $(".day-name").html(
                                            result.data
                                            .scheduleDay[0].available_on
                                        );
                                        $(".schedule-time").html(
                                            "[" +
                                            availableFrom +
                                            " - " +
                                            result.data
                                            .scheduleDay[0]
                                            .available_to +
                                            "]"
                                        );
                                    } else {
                                        $(".doctor-schedule").css(
                                            "display",
                                            "none"
                                        );
                                        $(".color-information").css(
                                            "display",
                                            "none"
                                        );
                                        $(".available-slot").css(
                                            "display",
                                            "none"
                                        );
                                        $(".error-message").css(
                                            "display",
                                            "block"
                                        );
                                        $(".error-message").html(
                                            "Doctor schedule not available on this date"
                                        );
                                    }
                                } else {
                                    $(".doctor-schedule").css(
                                        "display",
                                        "none"
                                    );
                                    $(".color-information").css(
                                        "display",
                                        "none"
                                    );
                                    $(".available-slot").css(
                                        "display",
                                        "none"
                                    );
                                    $(".error-message").css(
                                        "display",
                                        "block"
                                    );
                                    $(".error-message").html(
                                        "Doctor schedule not available on this date"
                                    );
                                }
                            }
                        }
                    },
                    error: function(error) {
                        displayErrorMessage(error.responseJSON.message);
                    },
                });
                // if ($(".isCreate").val() || $(".isEdit").val()) {

                var delayCall = 200;
                setTimeout(getCreateTimeSlot, delayCall);

                function getCreateTimeSlot() {
                    // if ($(".isCreate").val()) {
                    var data = {
                        editSelectedDate: appointmentSelectedDate,
                        doctor_id: appointmentDoctorId,
                    };
                    console.log("data", data);
                    // } else {
                    //     var data = {
                    //         editSelectedDate: appointmentSelectedDate,
                    //         editId: $("#appointmentEditsID").val(),
                    //         doctor_id: appointmentDoctorId,
                    //     };
                    // }

                    $.ajax({
                        url: "{{ route('get.booking.slot') }}",
                        type: "GET",
                        data: data,
                        success: function(result) {
                            console.log("result", result);
                            appointmentAlreadyCreateTimeSlot = result.data.bookingSlotArr;
                            console.log(appointmentAlreadyCreateTimeSlot.length, 'ddd');
                            if (
                                result.data.hasOwnProperty(
                                    "onlyTime"
                                )
                            ) {

                                if (
                                    result.data.bookingSlotArr
                                    .length > 0
                                ) {
                                    appointmentEditTimeSlot =
                                        result.data.onlyTime.toString();
                                    $.each(
                                        result.data.bookingSlotArr,
                                        function(index, value) {
                                            $.each(
                                                appointmentIntervals,
                                                function(i, v) {
                                                    if (
                                                        value == v
                                                    ) {
                                                        $(
                                                            ".time-interval"
                                                        ).each(
                                                            function() {
                                                                if (
                                                                    $(
                                                                        this
                                                                    ).data(
                                                                        "id"
                                                                    ) ==
                                                                    i
                                                                ) {
                                                                    if (
                                                                        $(
                                                                            this
                                                                        )
                                                                        .html() !=
                                                                        appointmentEditTimeSlot
                                                                    ) {
                                                                        $(
                                                                                this
                                                                            )
                                                                            .parent()
                                                                            .css({
                                                                                "background-color": "#ffa721",
                                                                                border: "1px solid #ffa721",
                                                                                color: "#ffffff",
                                                                            });
                                                                        $(
                                                                                this
                                                                            )
                                                                            .parent()
                                                                            .addClass(
                                                                                "booked"
                                                                            );
                                                                        $(
                                                                                this
                                                                            )
                                                                            .parent()
                                                                            .children()
                                                                            .prop(
                                                                                "disabled",
                                                                                true
                                                                            );
                                                                    }
                                                                }
                                                            }
                                                        );
                                                    }
                                                }
                                            );
                                        }
                                    );
                                }
                                $(".time-interval").each(
                                    function() {
                                        if (
                                            $(this).html() ==
                                            appointmentEditTimeSlot &&
                                            result.data
                                            .bookingSlotArr
                                            .length > 0
                                        ) {
                                            $(this)
                                                .parent()
                                                .addClass(
                                                    "time-slot-book"
                                                );
                                            $(this)
                                                .parent()
                                                .removeClass(
                                                    "booked"
                                                );
                                            $(this)
                                                .parent()
                                                .children()
                                                .prop(
                                                    "disabled",
                                                    false
                                                );
                                            $(this).click();
                                        }
                                    }
                                );
                            } else if (
                                appointmentAlreadyCreateTimeSlot.length >
                                0
                            ) {
                                $.each(
                                    appointmentAlreadyCreateTimeSlot,
                                    function(index, value) {
                                        $.each(
                                            appointmentIntervals,
                                            function(i, v) {
                                                if (value == v) {
                                                    $(
                                                        ".time-interval"
                                                    ).each(
                                                        function() {
                                                            if (
                                                                $(
                                                                    this
                                                                ).data(
                                                                    "id"
                                                                ) ==
                                                                i
                                                            ) {
                                                                $(
                                                                        this
                                                                    )
                                                                    .parent()
                                                                    .addClass(
                                                                        "time-slot-book"
                                                                    );
                                                                $(
                                                                    ".time-slot-book"
                                                                ).css({
                                                                    "background-color": "#ffa721",
                                                                    border: "1px solid #ffa721",
                                                                    color: "#ffffff",
                                                                });
                                                                $(
                                                                        this
                                                                    )
                                                                    .parent()
                                                                    .addClass(
                                                                        "booked"
                                                                    );
                                                                $(
                                                                        this
                                                                    )
                                                                    .parent()
                                                                    .children()
                                                                    .prop(
                                                                        "disabled",
                                                                        true
                                                                    );
                                                            }
                                                        }
                                                    );
                                                }
                                            }
                                        );
                                    }
                                );
                            }
                        },
                    });
                }
                // }
            },
        });

        var appointmentDoctorId;
        let appointmentDoctorChange = false;
        $("body").on("change", "#appointmentDoctorId", function() {
            if (appointmentDoctorChange) {
                $(".doctor-schedule").css("display", "none");
                $(".available-slot-heading").css("display", "none");
                $(".available-slot").css("display", "none");
                $(".error-message").css("display", "none");
                $("#appointmentCharge").val("");
                $("#appointmentPayment").prop("required", false);
                opdDate.clear();
                appointmentDoctorChange = true;
            }
            $(".error-message").css("display", "none");
            appointmentDoctorId = $(this).val();
            appointmentDoctorChange = true;
            $.ajax({
                url: "{{ route('get-appointment-charge') }}",
                type: "get",
                dataType: "json",
                data: {
                    doctor_id: appointmentDoctorId,
                },
                success: function(result) {
                    if (result.success) {
                        if (result.data != null) {
                            let charge = result.data.appointment_charge;

                            if (charge >= 0 && charge != 0) {
                                $(".appointmentCharge").removeClass(
                                    "d-none"
                                );
                                $("#appointmentCharge").val(charge);
                                $(".appointment-payment").removeClass(
                                    "d-none"
                                );
                                $("#appointmentPayment").prop(
                                    "required",
                                    true
                                );
                            }
                            if (charge <= 0 || charge == undefined) {
                                $(".appointment-payment").addClass(
                                    "d-none"
                                );
                                $(".appointmentCharge").addClass("d-none");
                            }
                        }
                    }
                },
                error: function(result) {
                    printErrorMessage("#editAppointmentErrorsBox", result);
                },
            });
        });

        // if edit record then trigger change
        var appointmentEditTimeSlot;
        // if ($(".isEdit").val()) {
        //     $("#appointmentDoctorId").trigger("change", function(event) {
        //         appointmentDoctorId = $(this).val();
        //     });

        //     $(".opdDate").trigger("dp.change", function() {
        //         var selected = new Date($(this).val());
        //     });
        // }

        //parseIn date_time
        function appointmentParseIn(date_time) {
            var d = new Date();
            d.setHours(date_time.substring(11, 13));
            d.setMinutes(date_time.substring(14, 16));

            return d;
        }

        //make time slot list
        function appointmentGetTimeIntervals(time1, time2, duration) {
            var arr = [];

            while (time1 < time2) {
                arr.push(time1.toTimeString().substring(0, 5));
                time1.setMinutes(time1.getMinutes() + duration);
            }
            return arr;
        }

        var appointmentEditTimeSlot;
        $("body").on("click", ".time-interval", function() {
            appointmentEditTimeSlot = $(this).text();
        });
        // } else {
        //     return false;
        // }
        // }
    }

    //slot click change color
    var appointmentSelectedTime;
    $("body").on("click", ".time-interval", function() {
        let appointmentId = $(event.currentTarget).attr("data-id");
        if ($(this).data("id") == appointmentId) {
            if ($(this).parent().hasClass("booked")) {
                $(".time-slot-book").css("background-color", "#ffa0a0");
            }
        }
        appointmentSelectedTime = $(this).text();
        $(".time-slot").removeClass("time-slot-book");
        $(this).parent().addClass("time-slot-book");
        if ($(".isEdit").val()) {
            $("#editTimeSlot").val(appointmentSelectedTime);
        }
    });

    //create appointment
    listenSubmit("#appointmentForm", function(event) {
        let appointmentOpdDate = $("#appointmentOpdDate").val();

        var isValid = true;



        if (isEmpty(appointmentOpdDate)) {
            $("#createAppointmentErrorsBox")
                .show()
                .removeClass("d-none")
                .html(Lang.get("js.select_appointment_date"))
                .delay(5000)
                .slideUp(300);
            return false;
        }
        if ($("#appointmentCharge").val() != null) {
            if ($("#appointmentPayment").val() == null) {
                displayErrorMessage(Lang.get("Select Payment Mode"));
                hideScreenLoader();
                return false;
            }
        }
        if (appointmentSelectedTime == null || appointmentSelectedTime == "") {
            $("#createAppointmentErrorsBox")
                .show()
                .removeClass("d-none")
                .html(Lang.get("js.select_time_slot"))
                .delay(5000)
                .slideUp(300);
            return false;
        }

        event.preventDefault();
        screenLock();
        let formData = $(this).serialize() + "&time=" + appointmentSelectedTime;
        $.ajax({
            url: $("#saveAppointmentURLID").val(),
            type: "POST",
            dataType: "json",
            data: formData,
            success: function(result) {
                screenUnLock();

                    displaySuccessMessage(result.message);
                    setTimeout(function() {
                        window.location.href = $(".appointmentIndexPage").val();
                    }, 2000);

            },
            error: function(result) {
                printErrorMessage("#createAppointmentErrorsBox", result);
                screenUnLock();
            },
            complete: function() {
                processingBtn("#appointmentForm", "#saveAppointment");
            },
        });
    });
</script>
