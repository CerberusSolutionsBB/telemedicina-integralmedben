<?php
namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginaStoreRequest;
use App\Models\Tenant;
use App\Services\Tenant\TenantAdminUserService;
use App\Services\Tenant\TenantDetailRegisterService;
use App\Services\Tenant\TenantFormService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

class PaginaStoreController extends Controller
{
    public function __construct(
        private TenantDetailRegisterService $tenantDetailRegisterService,
        private TenantFormService $tenantFormService,
        private TenantAdminUserService $tenantAdminUserService,

    ) {}
    public function __invoke(PaginaStoreRequest $request)
    {
        $validated = $request->validated();
        try {
            $tenantId = $this->gerarTenantIdUnico($validated['descricao']);
            $tenant   = Tenant::create([
                'id'   => $tenantId,
                'data' => [
                    'descricao' => $validated['descricao'],
                    'nome'      => $validated['nome'],
                    'email'     => $validated['email'],
                    'senha'     => Hash::make($validated['senha']),
                    'status'    => 'ativo',
                ],
            ]);

            Domain::create([
                'domain'    => $this->gerarDominio($tenantId),
                'tenant_id' => $tenant->id,
            ]);

            $this->tenantDetailRegisterService->execute($validated, $tenant);

            $this->tenantFormService->sync(
                tenantId: $tenant->id,
                formIds: $validated['forms'] ?? [],
                extraData: [
                    'user_id' => auth()->id(),
                    'origem'  => 'CENTRAL',
                    'ativo'   => true,
                ]
            );

            $this->tenantAdminUserService->execute($validated, $tenant);

            return redirect()
                ->route('pagina.index')
                ->with('message', 'Tenant criado com sucesso!')
                ->with('type', 'success');
        } catch (Throwable $e) {
            report($e);
            return back()
                ->withErrors([
                    'general' => 'Erro ao criar tenant: ' . $e->getMessage(),
                ])
                ->withInput();
        }
    }
    private function gerarTenantId(string $descricao): string
    {
        $slug = Str::slug($descricao, '_');
        $slug = Str::limit($slug, 50, '');
        $slug = ltrim($slug, '0123456789');
        return empty($slug)
            ? 'tenant_' . time()
            : strtolower($slug);
    }
    private function gerarTenantIdUnico(string $descricao): string
    {
        $base     = $this->gerarTenantId($descricao);
        $id       = $base;
        $contador = 1;
        while (Tenant::whereKey($id)->exists()) {
            $id = "{$base}_{$contador}";
            $contador++;
        }
        return $id;
    }
    private function gerarDominio(string $tenantId): string
    {
        $centralDomains = config('tenancy.central_domains', ['localhost']);
        $dominioCentral = app()->environment('production')
            ? "telemedicinamedben.com.br"
            : $centralDomains[0];

        return "{$tenantId}.{$dominioCentral}";
    }
}
