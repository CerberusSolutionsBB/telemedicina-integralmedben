<?php
namespace App\Http\Controllers\Tenant\Configuracao;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExpiresAtRequest;
use App\Http\Requests\TenantFormsRequest;
use App\Models\Tenant;
use App\Models\TenantForm;
use App\Models\TenantsDetail;
use App\Services\Tenant\TenantConfigurationService;
use App\Services\Tenant\TenantFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracaoController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $configuracaoService,
        private TenantFormService $tenantFormService,
    ) {}

    private function tenantId(): string
    {
        return (string) tenant()->id;
    }

    private function logoPath(string $fileName): string
    {
        return $this->tenantId() . '/' . $fileName;
    }

    /**
     * Gera URL usando o host atual do request (funciona com subdomínios)
     */
    private function tenantUrl(string $path): string
    {
        // Pega apenas o path relativo (ex: /storage/tenants/med_bem/logo.png)
        $relativePath = parse_url(Storage::disk('tenants')->url($path), PHP_URL_PATH);

        // Monta com o host atual do request (inclui subdomínio e porta)
        return request()->getSchemeAndHttpHost() . $relativePath;
    }

    public function index(): Response
    {
        $tenantDetail = TenantsDetail::firstOrCreate([
            'tenant_id' => $this->tenantId(),
        ]);

        $logoUrl = null;
        if ($tenantDetail->logo) {
            $fileName = basename($tenantDetail->logo);
            $logoUrl  = $this->tenantUrl(
                $this->logoPath($fileName)
            );
        }

        return Inertia::render('Tenant/Configuracao/Index', [
            'configurations' => [
                [
                    'key'         => 'logo',
                    'label'       => 'Logo do Sistema',
                    'description' => 'Imagem exibida no painel e telas públicas.',
                    'type'        => 'image',
                    'icon'        => 'image',
                    'category'    => 'Aparência',
                    'value'       => $logoUrl,
                    'updated_at'  => $tenantDetail->updated_at?->format('d/m/Y H:i'),
                ],
            ],
        ]);
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $tenantDetail = TenantsDetail::firstOrCreate([
            'tenant_id' => $this->tenantId(),
        ]);

        if ($request->hasFile('logo')) {
            $file     = $request->file('logo');
            $fileName = 'logo_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $file->storeAs($this->tenantId(), $fileName, 'tenants');

            if ($tenantDetail->logo) {
                $oldFileName = basename($tenantDetail->logo);
                $oldPath     = $this->logoPath($oldFileName);

                if (Storage::disk('tenants')->exists($oldPath)) {
                    Storage::disk('tenants')->delete($oldPath);
                }
            }

            $tenantDetail->update([
                'logo' => $fileName,
            ]);
        }

        return redirect()->back()->with('success', 'Logo atualizado com sucesso!');
    }

    public function detail(Request $request, Tenant $tenant)
    {
        try {

            $this->configuracaoService->gerarTenantsDetail($tenant);

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Configuração do tenant gerada com sucesso!')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar configuração do tenant', [
                'message'   => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível gerar as configurações do tenant.')
                ->with('type', 'error');
        }
    }
    public function createExpiresAt(
        ExpiresAtRequest $request,
        TenantForm $tenantForm
    ) {
        try {
            $validated = $request->validated();

            $tenantForm->update([
                'expires_at' => $validated['expires_at'] ?? null,
            ]);

            return redirect()
                ->route('pagina.show', [
                    'tenant' => $tenantForm->tenant_id,
                ])
                ->with('message', 'Data de expiração atualizada com sucesso.')
                ->with('type', 'success');

        } catch (\Throwable $e) {
            Log::error(
                'Erro ao atualizar data de expiração do formulário do tenant',
                [
                    'message'        => $e->getMessage(),
                    'tenant_form_id' => $tenantForm->id ?? null,
                    'tenant_id'      => $tenantForm->tenant_id ?? null,
                    'payload'        => $request->all(),
                ]
            );

            return redirect()
                ->back()
                ->with('message', 'Não foi possível atualizar a data de expiração.')
                ->with('type', 'error');
        }
    }
    public function forms(TenantFormsRequest $request, Tenant $tenant)
    {
        try {
            $validated = $request->validated();
            $this->tenantFormService->sync(
                tenantId: $tenant->id,
                formIds: $validated['forms'] ?? [],
                extraData: [
                    'user_id' => auth()->id(),
                    'origem'  => 'CENTRAL',
                    'ativo'   => true,
                ]
            );

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Configuração do tenant gerada com sucesso!')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao gerar configuração do tenant', [
                'message'   => $e->getMessage(),
                'tenant_id' => $tenant->id ?? null,
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível gerar as configurações do tenant.')
                ->with('type', 'error');
        }
    }

    public function removeVinculo(TenantForm $tenantForm)
    {
        try {
            $tenantId = $tenantForm->tenant_id;

            $tenantForm->delete();

            return redirect()
                ->route('pagina.show', [
                    'tenant' => $tenantId,
                ])
                ->with('message', 'Formulário desvinculado com sucesso.')
                ->with('type', 'success');

        } catch (\Throwable $e) {
            Log::error('Erro ao desvincular formulário do tenant', [
                'message'        => $e->getMessage(),
                'tenant_form_id' => $tenantForm->id ?? null,
                'tenant_id'      => $tenantForm->tenant_id ?? null,
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível desvincular o formulário.')
                ->with('type', 'error');
        }
    }
}