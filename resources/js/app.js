import './bootstrap';
import flatpickr from "flatpickr";
// import moment from 'moment';
import "flatpickr/dist/flatpickr.css";
import toastr from 'toastr';
import intlTelInput from "intl-tel-input";
// import "intl-tel-input/build/css/intlTelInput.css";

// const phoneInput = document.querySelector("#phoneNumber");
// const prefixCodeInput = document.querySelector("#prefix_code");

// if (phoneInput) {
//     const iti = intlTelInput(phoneInput, {
//         initialCountry: "auto",
//         geoIpLookup: function (callback) {
//             fetch('https://ipinfo.io/json?token=<YOUR_TOKEN>') 
//                 .then((resp) => resp.json())
//                 .then((data) => callback(data.country))
//                 .catch(() => callback("US"));
//         },
//         separateDialCode: true,
//         utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
//     });

//     phoneInput.addEventListener("countrychange", () => {
//         const dialCode = iti.getSelectedCountryData().dialCode;
//         if (prefixCodeInput) {
//             prefixCodeInput.value = dialCode;
//         }
//     });

//     phoneInput.addEventListener("load", () => {
//         const initialDialCode = iti.getSelectedCountryData().dialCode;
//         if (prefixCodeInput) {
//             prefixCodeInput.value = initialDialCode;
//         }
//     });
// }

window.flatpickr = flatpickr;
window.toastr = toastr;
window.moment = moment;
