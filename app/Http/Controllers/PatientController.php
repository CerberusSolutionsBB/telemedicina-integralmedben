<?php

namespace App\Http\Controllers;

use App\Http\Services\Patient\PatientService;
use App\Http\Services\Sms\ResendSmsService;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private ResendSmsService $resendSmsService,
    ) {}

    public function index()
    {
        $patients = $this->patientService->getPatients();
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('Patient/Index', [
            'patients' => $patients,
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function show(Patient $patient)
    {
        $patient = $this->patientService->getPatientDetails($patient);

        $smsLogs = SmsLogs::where('tenant_id', tenant('id'))
            ->where('patient_id', $patient->id)
            ->latest()
            ->get(['id', 'status', 'message', 'recipient', 'sent_at', 'error_message', 'created_at']);

        return Inertia::render('Patient/Show', [
            'patient' => $patient,
            'smsLogs' => $smsLogs,
        ]);
    }

    public function resendSms(Patient $patient)
    {
        $result = $this->resendSmsService->execute($patient, tenant('id'));

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function reportPdf()
    {
        $patients = Patient::with('answers.question')
            ->latest()
            ->get();

        $questions = $patients->flatMap(fn($p) => $p->answers->pluck('question'))->unique('id')->values();

        $tenant = Tenant::find(tenant('id'));
        $logoBase64 = null;
        if ($tenant->photo_path) {
            $absolutePath = base_path('storage/app/public/' . $tenant->photo_path);
            if (file_exists($absolutePath)) {
                $logoBase64 = 'data:' . mime_content_type($absolutePath) . ';base64,' . base64_encode(file_get_contents($absolutePath));
            }
        }

        $pdf = Pdf::loadView('pdf.patients-report', [
            'patients' => $patients,
            'questions' => $questions,
            'tenant' => $tenant,
            'logoBase64' => $logoBase64,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('relatorio-pacientes.pdf');
    }

    public function update(Patient $patient, Request $request)
    {
        $this->patientService->update($patient, $request->input('answers', []));

        return redirect()->route('patients.index');
    }

    public function destroy(Patient $patient)
    {
        $this->patientService->delete($patient);

        return redirect()->route('patients.index');
    }

    public function downloadPdf(Patient $patient)
    {
        $patient = $this->patientService->getPatientDetails($patient);

        $tenant = Tenant::find(tenant('id'));
        $logoBase64 = null;
        if ($tenant->photo_path) {
            $absolutePath = base_path('storage/app/public/' . $tenant->photo_path);
            if (file_exists($absolutePath)) {
                $logoBase64 = 'data:' . mime_content_type($absolutePath) . ';base64,' . base64_encode(file_get_contents($absolutePath));
            }
        }

        $pdf = Pdf::loadView('pdf.patient', [
            'patient' => $patient,
            'tenant' => $tenant,
            'logoBase64' => $logoBase64,
        ]);

        return $pdf->download('paciente-' . $patient->id . '.pdf');
    }
}
