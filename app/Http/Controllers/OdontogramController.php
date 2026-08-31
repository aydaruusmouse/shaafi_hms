<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Odontogram;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;

class OdontogramController extends Controller
{
    public function convertToPdf(Odontogram $odontogram)
    {
        $data = [];
        $data['setting'] = Setting::all()->pluck('value', 'key')->toArray();

        $data['odontogram'] = $odontogram;
        $pdf = FacadePdf::loadView('odontogram.pdf', $data);

        return $pdf->stream('odontogram.pdf');
    }
}