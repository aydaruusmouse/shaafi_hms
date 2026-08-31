<?php

namespace App\Http\Controllers;

use App\Models\PatientDiagnosisTest;
use App\Repositories\PatientDiagnosisTestRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;

class PatientDiagnosisTestController extends AppBaseController
{
    public function convertToPdf(PatientDiagnosisTest $patientDiagnosisTest)
    {
        if (! canAccessRecord(PatientDiagnosisTest::class, $patientDiagnosisTest->id)) {
            return Redirect::back();
        }

        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $data = app(PatientDiagnosisTestRepository::class)->getSettingList();
        $data['patientDiagnosisTest'] = $patientDiagnosisTest;
        $data['patientDiagnosisTests'] = app(PatientDiagnosisTestRepository::class)->getPatientDiagnosisTestProperty($patientDiagnosisTest->id);

        $imageData = Http::get($data['app_logo'])->body();
        $imageType = pathinfo($data['app_logo'], PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);

        $data['app_logo'] = $base64Image;

        $pdf = Pdf::loadView('patient_diagnosis_test.diagnosis_test_pdf', $data);

        return $pdf->stream($patientDiagnosisTest->patient->user->full_name.'-'.$patientDiagnosisTest->report_number);
    }
}
