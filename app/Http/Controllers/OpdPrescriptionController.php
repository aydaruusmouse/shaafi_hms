<?php

namespace App\Http\Controllers;

use App\Models\OpdPrescription;
use App\Repositories\OpdPrescriptionRepository;
use Barryvdh\DomPDF\Facade\Pdf;

class OpdPrescriptionController extends AppBaseController
{
    public function convertToPDF($id)
    {
        if (app()->getLocale() == 'zh') {
            app()->setLocale('en');
        }
        $opdPrescription = OpdPrescription::find($id);

        $data = app(OpdPrescriptionRepository::class)->getSettingList();
        $data['app_logo'] = pdfImageToBase64($data['app_logo'] ?? null);

        $pdf = Pdf::loadView('opd_prescriptions.opd_prescription_pdf', compact('opdPrescription', 'data'));

        return $pdf->stream(__('messages.delete.prescription'));
    }
}
