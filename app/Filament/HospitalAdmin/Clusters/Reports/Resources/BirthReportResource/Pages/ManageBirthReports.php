<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\BirthReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\BirthReportResource;
use App\Models\BirthReport;
use App\Models\Doctor;
use App\Models\PatientCase;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageBirthReports extends ManageRecords
{
    protected static string $resource = BirthReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->createAnother(false)
                ->action(function (array $data) {
                    if (getLoggedInUser()->hasRole('Doctor')) {
                        $data['doctor_id'] = Doctor::where([
                            'tenant_id' => getLoggedInUser()->tenant_id,
                            'user_id' => getLoggedInUserId(),
                        ])->value('id');
                    }

                    $patientId = PatientCase::select('patient_id')->whereCaseId($data['case_id'])->first();
                    if (is_null($data['doctor_id'])) {
                        $array['doctor_id'] = auth()->user()->id;
                    }
                    $data['patient_id'] = $patientId->patient_id;

                    BirthReport::create($data);

                    return
                        Notification::make()
                            ->title(__('messages.flash.birth_report_saved'))
                            ->success()
                            ->send();
                }),
        ];
    }
}
