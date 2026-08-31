<?php

namespace App\Http\Controllers;

use App\Models\BedAssign;
use App\Models\IpdConsultantRegister;
use App\Models\IpdDiagnosis;
use App\Models\IpdPatientDepartment;
use App\Models\IpdPrescription;
use App\Repositories\IpdBillRepository;
use Illuminate\Support\Facades\Http;
use PDF;

class IpdBillController extends AppBaseController
{
    /** @var IpdBillRepository */
    private $ipdBillRepository;

    public function __construct(IpdBillRepository $ipdBillRepo)
    {
        $this->ipdBillRepository = $ipdBillRepo;
    }

    public function ipdBillConvertToPdf(IpdPatientDepartment $ipdPatientDepartment)
    {
        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $data = $this->ipdBillRepository->getSyncListForCreate();

        $data['bill'] = $this->ipdBillRepository->getBillList($ipdPatientDepartment);
        $data['currencySymbol'] = getCurrencySymbol();
        $imageData = Http::get($data['setting']['app_logo'])->body();
        $imageType = pathinfo($data['setting']['app_logo'], PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);

        $data['setting']['app_logo'] = $base64Image;
        $data['bedAssign'] = BedAssign::whereIpdPatientDepartmentId($ipdPatientDepartment->id)->first();

        $pdf = PDF::loadView('ipd_bills.bill_pdf', $data);

        return $pdf->stream('bill.pdf');
    }

    public function ipdDischargePatientToPdf(IpdPatientDepartment $ipdPatientDepartment)
    {
        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $data = $this->ipdBillRepository->getSyncListForCreate();

        $data['bill'] = $this->ipdBillRepository->getBillList($ipdPatientDepartment);
        $data['diagnosis'] = IpdDiagnosis::whereIpdPatientDepartmentId($ipdPatientDepartment->id)->get();
        $data['instructions'] = IpdConsultantRegister::with('doctor.doctorUser')->where('ipd_patient_department_id', $ipdPatientDepartment->id)->get();
        $data['ipdPrescriptions'] = IpdPrescription::with(['patient', 'ipdPrescriptionItems'])->where('ipd_patient_department_id', $ipdPatientDepartment->id)->get();
        $data['bedAssign'] = BedAssign::whereIpdPatientDepartmentId($ipdPatientDepartment->id)->first();

        $data['currencySymbol'] = getCurrencySymbol();
        $imageData = Http::get($data['setting']['app_logo'])->body();
        $imageType = pathinfo($data['setting']['app_logo'], PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);

        $data['setting']['app_logo'] = $base64Image;

        $pdf = PDF::loadView('ipd_bills.discharge_slip', $data);

        return $pdf->stream('discharge.pdf');
    }
}
