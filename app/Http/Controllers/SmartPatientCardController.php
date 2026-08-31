<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use PDF;

class SmartPatientCardController extends AppBaseController
{
    public function downloadSmartCard($id)
    {
        $data = [];
        $imageData = Http::get(getLogoUrl())->body();
        $imageType = pathinfo(getLogoUrl(), PATHINFO_EXTENSION);
        $base64Image = 'data:image/'.$imageType.';base64,'.base64_encode($imageData);
        $data['app_logo'] = $base64Image;
        $data['app_name'] = getAppName();
        $data['hospital_address'] = getSettingValueByKey('hospital_address');
        $user = User::whereTenantId(getLoggedInUser()->tenant_id)->first();
        if (getLoggedinPatient()) {
            $data['user_name'] = getLoggedInUser()->username;
        } else {
            $data['user_name'] = $user->username;
        }
        $patient = Patient::with(['patientUser', 'SmartCardTemplate', 'address'])->find($id);

        if (empty($patient->patient_unique_id)) {
            $patient->update(['patient_unique_id' => strtoupper(Patient::generateUniquePatientId())]);
        }
        $url = $patient->patientUser->image_url;
        $arrUrl = explode('/', trim($url))[2];

        if ($arrUrl == 'ui-avatars.com') {
            $name = urlencode($patient->patientUser->full_name);
            $avatarUrl = "https://ui-avatars.com/api/?name={$name}&size=100&rounded=true&color=fff&background=fc6369";

            $avatarData = @file_get_contents($avatarUrl);
            if ($avatarData !== false) {
                $data['profile'] = base64_encode($avatarData);
            } else {
                $data['profile'] = null; // Handle the error gracefully
            }
        } else {
            $avatarUrl = $url;
            $avatarData = @file_get_contents($avatarUrl);
            if ($avatarData !== false) {
                $data['profile'] = base64_encode($avatarData);
            } else {
                $data['profile'] = null;
            }
        }

        $pdf = PDF::loadView('smart-card.smart-patient-card-pdf', compact('patient', 'data'));

        return $pdf->download('patient-smart-card.pdf');
    }
}
