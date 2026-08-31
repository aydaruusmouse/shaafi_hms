<?php

namespace App\Http\Controllers;

use App\Repositories\ZoomRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Class LiveConsultationController
 */
class LiveConsultationController extends AppBaseController
{
    public function zoomCallback(Request $request)
    {
        /** $zoomRepo Zoom */
        $zoomRepo = App::make(ZoomRepository::class);
        $zoomRepo->connectWithZoom($request->get('code'));

        return redirect(route('filament.hospitalAdmin.live-consultations.resources.live-consultations.index'));
    }
}
