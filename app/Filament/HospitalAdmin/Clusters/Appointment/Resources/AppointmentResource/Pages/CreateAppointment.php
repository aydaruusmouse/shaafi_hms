<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Http\Controllers\AppointmentTransactionController;
use App\Mail\NotifyMailHospitalAdminForBookingAppointment;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Models\UserTenant;
use App\Repositories\AppointmentRepository;
use App\Repositories\AppointmentTransactionRepository;
use App\Filament\HospitalAdmin\Clusters\Appointment\Concerns\HandlesAppointmentTicketSelection;
use App\Support\AppointmentTickets;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CreateAppointment extends CreateRecord
{
    use HandlesAppointmentTicketSelection;

    protected static string $resource = AppointmentResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('messages.common.back'))
                ->url(static::getResource()::getUrl('index')),

        ];
    }

    public function mount(): void
    {
        parent::mount();

        if ($patientId = request()->query('patient_id')) {
            $this->data['patient_id'] = (int) $patientId;
        }
    }

    protected function handleRecordCreation(array $input): Model
    {
        if (! getLoggedInUser()->hasRole('Patient') && isset($input['appointment_charge'])) {
            $input['appointment_charge'] = removeCommaFromNumbers(number_format($input['appointment_charge'], 2));
        }

        $appointmentTransactionRepository = app(AppointmentTransactionRepository::class);

        if ($error = AppointmentTickets::validateSelection($input)) {
            Notification::make()
                ->danger()
                ->title($error)
                ->send();
            $this->halt();
        }

        $input = AppointmentTickets::normalize($input);
        $input['payment_type'] = $input['payment_type'] ?? 4;
        $input['tenant_id'] = getLoggedInUser()->tenant_id;
        $appointmentRepository = app(AppointmentRepository::class);

        // $input['is_completed'] = $input['is_completed'] == 1 ? Appointment::STATUS_COMPLETED : Appointment::STATUS_PENDING;
        $input['is_completed'] = Appointment::STATUS_IN_VITAL;

        $input['payment_type'] = $input['payment_type'] ?? 4;
        if (auth()->user()->hasRole('Patient')) {
            $input['patient_id'] = auth()->user()->owner_id;
        }

        $jsonFields = [];

        foreach ($input as $key => $value) {
            if (strpos($key, 'field') === 0) {
                $jsonFields[$key] = $value;
            }
        }
        $input['custom_field'] = ! empty($jsonFields) ? $jsonFields : null;

        if ($input['payment_type'] != 8 && $input['payment_type'] != 7) {
            $data = $appointmentRepository->create($input);
            $appointment = Appointment::find($data['id']);
            $input['appointment_id'] = $data['id'];
        }

        $appointmentRepository->createNotification($input);
        if (Auth::check()) {
            $hospitalDefaultAdmin = User::find(Auth::user()->id);
        }

        if (in_array($input['payment_type'], [1, 2, 3, 5])) {
            $data->update(['payment_type' => 4]);
        }

        if ($input['payment_type'] == 1) {
            $appointmentTransactionController = new AppointmentTransactionController($appointmentTransactionRepository);
            if (getCurrentCurrency() == 'ngn' && $input['appointment_charge'] < 570) {
                $appointment = Appointment::find($input['appointment_id']);
                $appointment->delete();
                Notification::make()
                    ->danger()
                    ->title(__('messages.flash.appointment_charge_must_be_greater_than_570'))
                    ->send();
                Appointment::find($input['appointment_id'])->delete();
                session(['paymentError' => 'error']);

                return $appointment;
            }
            $data = $appointmentTransactionController->createStripeSession($input);
        } elseif ($input['payment_type'] == 2) {

            $data = app(AppointmentTransactionController::class)->appointmentRazorpayPayment($input);
            if (session()->has('appointmentPayment')) {
                $dataResponse = session()->get('appointmentPayment');
                session()->forget('appointmentPayment');
                $this->js('razorPay(event'.','.$dataResponse['status'].', '.$dataResponse['record'].', '.$dataResponse['amount'].')');
                $this->halt();
            }
        } elseif ($input['payment_type'] == 3) {
            if (! in_array(strtoupper(getCurrentCurrency()), getPayPalSupportedCurrencies())) {
                Appointment::whereId($input['appointment_id'])->delete();
                Notification::make()
                    ->title(__('messages.flash.currency_not_supported_paypal'))
                    ->danger()
                    ->send();
                $this->halt();
            }
            $appointmentTransactionController = new AppointmentTransactionController($appointmentTransactionRepository);
            $url = $appointmentTransactionController->paypalOnBoard($input);
        } elseif ($input['payment_type'] == 5) {
            if (! in_array(strtoupper(getCurrentCurrency()), flutterWaveSupportedCurrencies())) {
                Appointment::find($input['appointment_id'])->delete();
                Notification::make()
                    ->title(__('messages.common.something_want_wrong').'!')
                    ->body(__('messages.flutterwave.currency_allowed'))
                    ->danger()
                    ->send();
                $this->halt();
            }
            $appointmentTransactionController = new AppointmentTransactionController($appointmentTransactionRepository);
            $data = $appointmentTransactionController->appointmentFlutterWavePayment($input);
        } elseif ($input['payment_type'] == 7) {
            $appointmentTransactionController = new AppointmentTransactionController($appointmentTransactionRepository);
            $data = $appointmentTransactionController->phonePayInit($input);
        } elseif ($input['payment_type'] == 8) {
            if (! in_array(strtoupper(getCurrentCurrency()), payStackSupportedCurrencies())) {
                Appointment::find($input['appointment_id'])->delete();
                Notification::make()
                    ->title(__('messages.new_change.paystack_support_zar'))
                    ->danger()
                    ->send();
                $this->halt();
            }
            $appointmentTransactionController = new AppointmentTransactionController($appointmentTransactionRepository);
            $data = $appointmentTransactionController->appointmentPaystackPayment($input);
        } else {
            $data = $appointmentTransactionRepository->store($data);
        }

        $userId = UserTenant::whereTenantId(getLoggedInUser()->tenant_id)->value('user_id');
        $hospitalDefaultAdmin = User::whereId($userId)->first();

        if (! empty($hospitalDefaultAdmin)) {

            $hospitalDefaultAdminEmail = $hospitalDefaultAdmin->email;
            $doctor = Doctor::whereId($input['doctor_id'])->first();
            $patient = Patient::whereId($input['patient_id'])->first();

            $mailData = [
                'booking_date' => AppointmentTickets::bookingLabel($input['opd_date'], $input['ticket_number'] ?? null),
                'patient_name' => $patient->user->full_name,
                'patient_email' => $patient->user->email,
                'doctor_name' => $doctor->user->full_name,
                'doctor_department' => $doctor->department->title,
                'doctor_email' => $doctor->user->email,
            ];

            $mailData['patient_type'] = 'Old';

            Mail::to($hospitalDefaultAdminEmail)
                ->send(new NotifyMailHospitalAdminForBookingAppointment(
                    'emails.booking_appointment_mail',
                    __('messages.new_change.notify_mail_for_patient_book'),
                    $mailData
                ));
            Mail::to($doctor->user->email)
                ->send(new NotifyMailHospitalAdminForBookingAppointment(
                    'emails.booking_appointment_mail',
                    __('messages.new_change.notify_mail_for_patient_book'),
                    $mailData
                ));
        }

        $createdAppointment = Appointment::find($input['appointment_id'] ?? null);

        if (! $createdAppointment && isset($data) && $data instanceof Appointment && $data->exists) {
            $createdAppointment = $data;
        }

        return $createdAppointment ?? new Appointment($input);
    }

    protected function afterCreate(): void
    {
        if ($this->record?->exists) {
            $this->record->update(['is_completed' => Appointment::STATUS_IN_VITAL]);
        }
    }

    protected function getRedirectUrl(): string
    {
        if (session()->has('sessionUrl')) {
            $sessionUrl = session()->get('sessionUrl');
            session()->forget('sessionUrl');

            return $sessionUrl;
        }

        return \App\Filament\HospitalAdmin\Clusters\Appointment\Resources\VitalAppointmentResource::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        if (session()->has('paymentError')) {
            session()->forget('paymentError');

            return '';
        } elseif (! session()->has('sessionUrl')) {
            return __('messages.flash.appointment_created');
        }

        return '';
    }
}
