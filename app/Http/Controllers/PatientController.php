<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPatientRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Services\Patient\FichaBeneficiarioPdfService;
use App\Http\Services\Patient\PatientCardPdfService;
use App\Http\Services\Patient\PatientService;
use App\Http\Services\Patient\PatientsReportPdfService;
use App\Http\Services\Sms\ResendSmsService;
use App\Services\Tenant\PacientePlanoService;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService,
        private ResendSmsService $resendSmsService,
        private PatientsReportPdfService $patientsReportPdfService,
        private PatientCardPdfService $patientCardPdfService,
        private PacientePlanoService $pacientePlanoService,
    ) {}

    public function index(Request $request)
    {
        $patients = $this->patientService->getPatients(
            search: $request->search,
            status: $request->status,
            registro: $request->registro,
        );
        $tenant = Tenant::find(tenant('id'));

        $detail = TenantsDetail::where('tenant_id', tenant('id'))->first();
        $cartaoPacienteEnabled = $detail?->configuracao['cartao_paciente_enabled'] ?? false;
        $cartaoDinamicoEnabled = $detail?->configuracao['cartao_dinamico_enabled'] ?? false;

        return Inertia::render('Patient/Index', [
            'patients' => $patients,
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
            'cartaoPacienteEnabled' => $cartaoPacienteEnabled,
            'cartaoDinamicoEnabled' => $cartaoDinamicoEnabled,
        ]);
    }

    public function create()
    {
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('Patient/Create', [
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
            'planos' => $this->pacientePlanoService->opcoes(tenant('id')),
            'breadcrumbs' => [
                ['label' => 'Beneficiários', 'href' => route('patients.index')],
                ['label' => 'Novo beneficiário', 'href' => null],
            ],
        ]);
    }

    public function store(StorePatientRequest $request)
    {
        $data = $request->validated();
        $codPlano = $data['cod_plano'] ?? null;

        // Cota e CPF são validados antes de salvar: nesses casos o paciente não é criado.
        if ($codPlano) {
            $this->pacientePlanoService->validar(tenant('id'), $codPlano, $data);
        }

        $patient = $this->patientService->store($data);

        return $this->redirectAfterPlano($patient, $codPlano, 'Paciente cadastrado com sucesso.');
    }

    public function edit(Patient $patient)
    {
        return Inertia::render('Patient/Edit', [
            'patient' => $patient,
            'planos' => $this->pacientePlanoService->opcoes(tenant('id')),
            'planoAtual' => $this->pacientePlanoService->vinculoAtual(tenant('id'), $patient->cpf),
            'breadcrumbs' => [
                ['label' => 'Beneficiários', 'href' => route('patients.index')],
                ['label' => $patient->nome ?: "Beneficiário #{$patient->id}", 'href' => null],
            ],
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
            'registro' => $this->pacientePlanoService->detalhes(tenant('id'), $patient),
        ]);
    }

    public function update(Patient $patient, StorePatientRequest $request)
    {
        $data = $request->validated();
        $codPlano = $data['cod_plano'] ?? null;

        // No Edit o plano só pode ser adicionado; validar() recusa CPF que já tem vínculo.
        if ($codPlano) {
            $this->pacientePlanoService->validar(tenant('id'), $codPlano, $data);
        }

        $this->patientService->update($patient, $data);

        return $this->redirectAfterPlano($patient->refresh(), $codPlano, 'Paciente atualizado com sucesso.');
    }

    /**
     * Registra o plano (SIPROV + telemedicina) e redireciona. Se a SIPROV falhar,
     * o paciente continua salvo e o erro é exibido junto da mensagem de sucesso.
     */
    private function redirectAfterPlano(Patient $patient, ?string $codPlano, string $success)
    {
        $redirect = redirect()->route('patients.index')->with('success', $success);

        if (! $codPlano) {
            return $redirect;
        }

        $erro = $this->pacientePlanoService->registrar(tenant('id'), $patient, $codPlano);

        return $erro
            ? $redirect->with('error', 'Não foi possível registrar o plano na SIPROV; o paciente foi salvo sem vínculo de telemedicina. Detalhe: '.$erro)
            : $redirect;
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

    public function reportPdf(Request $request)
    {
        // Mesmos filtros da lista de beneficiários (busca, status e origem).
        $pdf = $this->patientsReportPdfService->generate(tenant('id'), $request->only(['search', 'status', 'registro']));

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="relatorio-beneficiarios-'.now('America/Sao_Paulo')->format('Y-m-d').'.pdf"',
        ]);
    }

    public function downloadPdf(Patient $patient, FichaBeneficiarioPdfService $fichaPdf)
    {
        return $fichaPdf->gerar($patient, tenant('id'))->download('ficha-beneficiario-'.$patient->id.'.pdf');
    }

    public function cartao(Patient $patient)
    {
        $pdf = $this->patientCardPdfService->execute($patient);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cartao-'.$patient->id.'.pdf"',
        ]);
    }
}
