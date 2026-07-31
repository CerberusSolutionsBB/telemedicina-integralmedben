<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPatientRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Services\Patient\PatientService;
use App\Http\Services\Patient\PatientsReportPdfService;
use App\Http\Services\Sms\ResendSmsService;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private ResendSmsService $resendSmsService,
        private PatientsReportPdfService $patientsReportPdfService,
    ) {}

    public function index(Request $request)
    {
        $patients = $this->patientService->getPatients(
            search: $request->search,
            status: $request->status,
            registro: $request->registro,
        );
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('Patient/Index', [
            'patients' => $patients,
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function create()
    {
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('Patient/Create', [
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
        ]);
    }

    public function store(StorePatientRequest $request)
    {
        $this->patientService->store($request->validated());

        return redirect()->route('patients.index')
            ->with('success', 'Paciente cadastrado com sucesso.');
    }

    public function edit(Patient $patient)
    {
        return Inertia::render('Patient/Edit', [
            'patient' => $patient,
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

    public function update(Patient $patient, StorePatientRequest $request)
    {
        $this->patientService->update($patient, $request->validated());

        return redirect()->route('patients.index')
            ->with('success', 'Paciente atualizado com sucesso.');
    }

    public function toggleStatus(Patient $patient)
    {
        $patient = $this->patientService->toggleStatus($patient);

        return back()->with('success', 'Status do paciente alterado para '.($patient->status ? 'Ativo' : 'Inativo').'.');
    }

    public function destroy(Patient $patient)
    {
        $this->patientService->delete($patient);

        return redirect()->route('patients.index')
            ->with('success', 'Paciente excluído com sucesso!');
    }

    public function export(string $format)
    {
        return $this->patientService->export($format);
    }

    public function template(string $format)
    {
        $tenant = Tenant::find(tenant('id'));
        $questions = $tenant->questions()->where('is_active', true)->get();

        return $this->patientService->template($questions, $format);
    }

    public function import(ImportPatientRequest $request)
    {
        $tenant = Tenant::find(tenant('id'));
        $questions = $tenant->questions()->where('is_active', true)->get();

        $result = $this->patientService->import([
            'file' => $request->file('file'),
            'questions' => $questions,
        ]);

        $message = $result['imported'].' paciente(s) importado(s) com sucesso.';
        if (! empty($result['errors'])) {
            $message .= ' Erros: '.implode(' | ', $result['errors']);
        }

        return redirect()->route('patients.index')
            ->with(
                empty($result['errors']) ? 'success' : 'warning',
                $message
            );
    }

    public function resendSms(Patient $patient)
    {
        $result = $this->resendSmsService->execute($patient, tenant('id'));

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function resendSmsLog(Patient $patient, SmsLogs $smsLog)
    {
        $result = $this->resendSmsService->executeOne($patient, $smsLog, tenant('id'));

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function reportPdf()
    {
        $pdf = $this->patientsReportPdfService->generate(tenant('id'));

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="relatorio-pacientes.pdf"',
        ]);
    }

    public function downloadPdf(Patient $patient)
    {
        $patient = $this->patientService->getPatientDetails($patient);

        $tenant = Tenant::find(tenant('id'));
        $logoBase64 = null;
        if ($tenant->photo_path) {
            $absolutePath = base_path('storage/app/public/'.$tenant->photo_path);
            if (file_exists($absolutePath)) {
                $logoBase64 = 'data:'.mime_content_type($absolutePath).';base64,'.base64_encode(file_get_contents($absolutePath));
            }
        }

        $pdf = Pdf::loadView('pdf.patient', [
            'patient' => $patient,
            'tenant' => $tenant,
            'logoBase64' => $logoBase64,
        ]);

        return $pdf->download('paciente-'.$patient->id.'.pdf');
    }
}
