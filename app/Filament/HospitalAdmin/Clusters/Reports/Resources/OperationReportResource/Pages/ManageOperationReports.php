<?php

namespace App\Filament\HospitalAdmin\Clusters\Reports\Resources\OperationReportResource\Pages;

use App\Filament\HospitalAdmin\Clusters\Reports\Resources\OperationReportResource;
use App\Models\Doctor;
use App\Models\OperationReport;
use App\Models\PatientCase;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageOperationReports extends ManageRecords
{
    protected static string $resource = OperationReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make()->label(__('messages.operation_report.new_operation_report'))->modalWidth("xl")->createAnother(false)->successNotificationTitle(__('messages.flash.operation_report_saved'))->modalHeading(__('messages.operation_report.new_operation_report')),
            Actions\CreateAction::make()->createAnother(false)->modalWidth('xl')
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

                    OperationReport::create($data);

                    return
                        Notification::make()
                            ->title(__('messages.flash.operation_report_saved'))
                            ->success()
                            ->send();
                }),
        ];
    }
}
