<?php

namespace App\Http\Controllers;

use App\Http\Services\CentralPatient\CentralPatientService;
use App\Http\Services\Question\GetAllQuestionsService;
use App\Models\CentralPatient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CentralPatientController extends Controller
{
    public function __construct(
        private CentralPatientService $service,
        private GetAllQuestionsService $questionsService,
    ) {}

    public function index()
    {
        return Inertia::render('CentralPatient/Index', [
            'patients' => $this->service->getAll(),
            'questions' => $this->questionsService->execute(),
        ]);
    }

    public function update(CentralPatient $centralPatient, Request $request)
    {
        $this->service->update($centralPatient, $request->input('answers', []));

        return redirect()->route('central-patients.index');
    }

    public function destroy(CentralPatient $centralPatient)
    {
        $this->service->delete($centralPatient);

        return redirect()->route('central-patients.index');
    }
}
