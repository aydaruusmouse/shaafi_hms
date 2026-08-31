<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\AppointmentResource;
use App\Filament\HospitalAdmin\Clusters\Patients\Resources\PatientResource;
use App\Models\Doctor;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Models\Appointment;
use App\Models\PatientQueue;
use Filament\Notifications\Notification;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    protected $i = 1;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('googleCalendar')
                ->hiddenLabel()
                ->url(route('filament.hospitalAdmin.appointment.pages.appointment-calendar'))
                ->icon('heroicon-s-calendar'),
            Action::make('createPatientAndAppointment')
                ->label(__('messages.appointment.create_patient_and_appointment'))
                ->icon('heroicon-o-user-plus')
                ->url(PatientResource::getUrl('create', ['create_and_appointment' => 1]))
                ->visible(fn () => ! auth()->user()->hasRole('Patient') && PatientResource::canCreate() && AppointmentResource::canCreate()),
            Actions\CreateAction::make(),
            ExportAction::make()->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->disabled(! AppointmentResource::getModel()::whereTenantId(getLoggedInUser()->tenant_id)->exists())
                ->hidden(function () {
                    if (auth()->user()->hasRole(['Doctor'])) {
                        return false;
                    }

                    return true;
                })
                ->label(__('messages.common.export_to_excel'))->exports([
                    ExcelExport::make()
                        ->withFilename(__('messages.appointments').'-'.now()->format('Y-m-d').'.xlsx')
                        ->modifyQueryUsing(function (Builder $query) {
                            $query->where('tenant_id', auth()->user()->tenant_id);
                            if (! getLoggedinDoctor()) {
                                if (getLoggedinPatient()) {
                                    $patient = Auth::user();
                                    $query->whereHas('patient', function (Builder $query) use ($patient) {
                                        $query->where('user_id', '=', $patient->id);
                                    });
                                }
                            } else {
                                $doctorId = Doctor::where('user_id', getLoggedInUserId())->first();
                                $query = $query->where('doctor_id', $doctorId->id);
                            }

                            return $query;
                        })
                        ->withColumns([
                            Column::make('id')->heading('No')->formatStateUsing(function () {
                                return $this->i++;
                            }),
                            Column::make('patient.user.full_name')->heading(heading: __('messages.case.patient')),
                            Column::make('doctor.user.full_name')->heading(heading: __('messages.role.doctor')),
                            Column::make('doctor.department.title')->heading(heading: __('messages.doctor_department.doctor_department')),
                            Column::make('doctor.description')->heading(heading: __('messages.common.description')),
                            Column::make('opd_date')->heading(heading: __('messages.case.date')),
                        ]),

                ]),

        ];
    }

    public function changeStatus($record, $status)
    {
        $appointment = Appointment::find($record);
        $appointment->update(['is_completed' => $status]);

        $lastQueue = PatientQueue::max('no');
        $nextQueueNo = $lastQueue ? $lastQueue + 1 : 1;

        $input['appointment_id']  = $appointment->id;
        $input['no']  = $nextQueueNo;
        $input['tenant_id']  = $appointment->tenant_id;
        PatientQueue::create($input);

        return Notification::make()
            ->title(__('Appointment added in queue successfully.'))
            ->success()
            ->send();
    }
}
