<?php

namespace App\Http\Controllers;

use App\Models\PathologyParameterItem;
use App\Models\PathologyTest;
use App\Repositories\PathologyTestRepository;
use App\Repositories\PatientRepository;
use Barryvdh\DomPDF\Facade\Pdf;

class PathologyTestController extends AppBaseController
{
    /** @var PathologyTestRepository */
    private $pathologyTestRepository;

    /** @var PatientRepository */
    private $patientRepository;

    public function __construct(PathologyTestRepository $pathologyTestRepo, PatientRepository $patientRepository)
    {
        $this->pathologyTestRepository = $pathologyTestRepo;
        $this->patientRepository = $patientRepository;
    }

    public function convertToPDF($id): \Illuminate\Http\Response
    {
        $data = [];
        $data['logo'] = $this->pathologyTestRepository->getSettingList();
        $data['pathologyTest'] = PathologyTest::with(['pathologycategory', 'chargecategory'])->where('id', $id)->first();
        $data['pathologyParameterItems'] = PathologyParameterItem::with('pathologyTest', 'pathologyParameter.pathologyUnit')->wherePathologyId($id)->get();

        $pdf = Pdf::loadView('pathology_test.pathology_test_pdf', compact('data'));

        return $pdf->stream('Pathology Test');
    }
}
