<?php

namespace App\Http\Controllers;

use App\Http\Services\Patient\PatientDynamicCardPdfService;
use App\Models\Patient;
use Illuminate\Http\Response;

class PatientCartaoDinamicoController extends Controller
{
    public function __construct(
        private PatientDynamicCardPdfService $patientDynamicCardPdfService,
    ) {}

    public function gerar(Patient $patient)
    {
        $pdf = $this->patientDynamicCardPdfService->execute($patient);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cartao-dinamico-'.$patient->id.'.pdf"',
        ]);
    }
}
