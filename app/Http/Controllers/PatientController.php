<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportPatientRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Services\Patient\FichaBeneficiarioPdfService;
use App\Http\Services\Patient\PatientCardPdfService;
use App\Http\Services\Patient\PatientFiltros;
use App\Http\Services\Patient\PatientService;
use App\Http\Services\Patient\PatientsReportPdfService;
use App\Http\Services\Sms\ResendSmsService;
use App\Models\Patient;
use App\Models\SmsLogs;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use App\Models\TipoVinculoFamiliar;
use App\Services\Tenant\PacienteDependentesSiprovService;
use App\Services\Tenant\PacienteFamiliaresService;
use App\Services\Tenant\PacientePlanoService;
use App\Support\BeneficiarioPermissoes;
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
        private PacienteFamiliaresService $pacienteFamiliaresService,
        private PacienteDependentesSiprovService $pacienteDependentesSiprovService,
    ) {}

    public function index(Request $request)
    {
        $patients = $this->patientService->getPatients($request->only(PatientFiltros::CHAVES));
        // Plano de cada beneficiário da página (uma consulta para todos).
        $planos = $this->pacientePlanoService->planosPorCpf(tenant('id'), $patients->pluck('cpf')->all());
        $patients->getCollection()->each(
            fn (Patient $p) => $p->setAttribute('plano', $planos[preg_replace('/\D/', '', (string) $p->cpf)] ?? null)
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
            'filtrosOpcoes' => PatientFiltros::opcoes(tenant('id')),
            'totais' => PatientFiltros::totais(tenant('id'), $request->only(PatientFiltros::CHAVES)),
        ]);
    }

    public function create()
    {
        $tenant = Tenant::find(tenant('id'));

        return Inertia::render('Patient/Create', [
            'tenantName' => $tenant->name,
            'tenantPhoto' => $tenant->photo_url,
            'planos' => $this->pacientePlanoService->opcoes(tenant('id')),
            'tiposFamiliares' => TipoVinculoFamiliar::options(),
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
        $removidos = $this->salvarFamiliares($patient, $codPlano, $data);

        return $this->redirectAfterPlano($patient, $codPlano, 'Paciente cadastrado com sucesso.', $removidos);
    }

    public function edit(Patient $patient)
    {
        return Inertia::render('Patient/Edit', [
            'patient' => $patient,
            'planos' => $this->pacientePlanoService->opcoes(tenant('id')),
            'planoAtual' => $this->pacientePlanoService->vinculoAtual(tenant('id'), $patient->cpf),
            'familiares' => $this->pacienteFamiliaresService->doPaciente($patient),
            'tiposFamiliares' => TipoVinculoFamiliar::options(),
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
            'familiares' => $this->pacienteFamiliaresService->paraExibicao($patient),
        ]);
    }

    public function update(Patient $patient, StorePatientRequest $request)
    {
        $data = $request->validated();

        // Status só muda quando o parceiro habilitou "Alterar status".
        if (! BeneficiarioPermissoes::permite(tenant('id'), 'status')) {
            unset($data['status']);
        }
        $codPlano = $data['cod_plano'] ?? null;

        // No Edit o plano só pode ser adicionado; validar() recusa CPF que já tem vínculo.
        if ($codPlano) {
            $this->pacientePlanoService->validar(tenant('id'), $codPlano, $data);
        }

        $this->patientService->update($patient, $data);
        $removidos = $this->salvarFamiliares($patient, $codPlano, $data);

        return $this->redirectAfterPlano($patient->refresh(), $codPlano, 'Paciente atualizado com sucesso.', $removidos);
    }

    /**
     * Membros da família: gravados só quando o plano (escolhido ou já vinculado) é o familiar.
     *
     * @return array<int, int>|null codDependente SIPROV dos removidos; null quando o plano não é familiar
     */
    private function salvarFamiliares(Patient $patient, ?string $codPlano, array $data): ?array
    {
        $planoId = $this->pacienteFamiliaresService->planoFamiliar(tenant('id'), $codPlano, $patient->cpf);

        return $planoId
            ? $this->pacienteFamiliaresService->sincronizar($patient, $planoId, $data['familiares'] ?? [])
            : null;
    }

    /**
     * Registra o plano (SIPROV + telemedicina), envia os dependentes do plano
     * familiar e redireciona. Se a SIPROV falhar, o paciente continua salvo e o
     * erro é exibido junto da mensagem de sucesso.
     *
     * @param  array<int, int>|null  $removidos  null quando o plano não é familiar
     */
    private function redirectAfterPlano(Patient $patient, ?string $codPlano, string $success, ?array $removidos = null)
    {
        $redirect = redirect()->route('patients.index')->with('success', $success);

        if ($codPlano && $erro = $this->pacientePlanoService->registrar(tenant('id'), $patient, $codPlano)) {
            return $redirect->with('error', 'Não foi possível registrar o plano na SIPROV; o paciente foi salvo sem vínculo de telemedicina. Detalhe: '.$erro);
        }

        // Dependentes vão para o benefício do titular, então só depois do registro do plano.
        if ($removidos !== null && $erro = $this->pacienteDependentesSiprovService->enviar(tenant('id'), $patient, $removidos)) {
            return $redirect->with('error', 'Os dependentes foram salvos, mas não foram enviados à SIPROV. Detalhe: '.$erro);
        }

        return $redirect;
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

    public function export(Request $request, string $format)
    {
        // Mesmos filtros da lista de beneficiários.
        return $this->patientService->export($format, $request->only(PatientFiltros::CHAVES));
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

        $redirect = redirect()->route('patients.index');

        if ($result['imported'] > 0) {
            $redirect->with('success', $result['imported'].' beneficiário(s) importado(s) com sucesso.');
        }

        if (! empty($result['errors'])) {
            // Mostra as primeiras linhas com erro para não estourar o aviso.
            $erros = array_slice($result['errors'], 0, 5);
            $restantes = count($result['errors']) - count($erros);
            $redirect->with('error', count($result['errors']).' linha(s) não importada(s): '.implode(' | ', $erros)
                .($restantes > 0 ? " | e mais {$restantes}." : ''));
        }

        return $redirect;
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
        $pdf = $this->patientsReportPdfService->generate(tenant('id'), $request->only(PatientFiltros::CHAVES));

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
