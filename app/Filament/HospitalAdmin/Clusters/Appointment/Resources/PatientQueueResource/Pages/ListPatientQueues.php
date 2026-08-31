<?php

namespace App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Appointment\Resources\PatientQueueResource;
use App\Models\Appointment;
use App\Models\PatientQueue;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPatientQueues extends ListRecords
{
    protected static string $resource = PatientQueueResource::class;

    protected function getHeaderActions(): array
    {
        if(getLoggedInUser()->hasRole('Admin')){
            $user = getLoggedInUser();
        }else{
            $user = User::whereTenantId(getLoggedInUser()->tenant_id)->whereNotNull('username')->first();
        }
        
        return [
            Action::make('googleCalendar')
                ->label('View Queue')
                ->url(fn () => route('patient-queue-url', $user->username))
                ->openUrlInNewTab()
        ];
    }

    public function changeStatus($record, $status)
    {
        $patientQueue = PatientQueue::find($record);
        $appointment = Appointment::find($patientQueue->appointment_id);

        if ($status == 0 || $status == 6) {
            $patientQueue->delete();
        }

        $appointment->update([
            'is_completed' => $status
        ]);

        return Notification::make()
            ->title(__('Staus change successfully.'))
            ->success()
            ->send();
    }
}
