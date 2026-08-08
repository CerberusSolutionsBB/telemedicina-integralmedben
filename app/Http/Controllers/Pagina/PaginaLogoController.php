<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaginaLogoController extends Controller
{
    public function store(Request $request, Tenant $tenant)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $detail = TenantsDetail::firstOrCreate([
            'tenant_id' => $tenant->id,
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $fileName = 'logo_'.$detail->id.'_'.time().'.'.$file->getClientOriginalExtension();

            $this->ensureDiskRootExists('tenants');

            $dimensions = @getimagesize($file->getRealPath());

            $file->storeAs('', $fileName, 'tenants');

            if ($detail->logo) {
                $this->deleteEverywhere($tenant, $detail->logo);
            }

            $detail->update(['logo' => $fileName]);

            $url = route('pagina.configuracao.logo.show', $tenant->id).'?v='.urlencode($fileName);

            return response()->json([
                'success' => true,
                'logo' => [
                    'id' => $detail->id,
                    'url' => $url,
                    'nome' => $fileName,
                    'formato' => strtoupper($file->getClientOriginalExtension()),
                    'tamanho' => $file->getSize(),
                    'largura' => $dimensions[0] ?? null,
                    'altura' => $dimensions[1] ?? null,
                    'ativo' => true,
                    'created_at' => now()->format('d/m/Y H:i'),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nenhum arquivo enviado.',
        ], 422);
    }

    public function destroy(Tenant $tenant)
    {
        $detail = TenantsDetail::where('tenant_id', $tenant->id)->first();

        if (! $detail || ! $detail->logo) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma logo encontrada para este parceiro.',
            ], 404);
        }

        $this->deleteEverywhere($tenant, $detail->logo);

        $detail->update(['logo' => null]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Serve a imagem da logo diretamente, em vez de expor ao frontend o path
     * de armazenamento (e depender do symlink público estar correto).
     */
    public function show(Tenant $tenant)
    {
        $detail = TenantsDetail::where('tenant_id', $tenant->id)->first();

        if (! $detail || ! $detail->logo) {
            abort(404);
        }

        $path = $tenant->resolveLogoPath($detail->logo);

        if (! $path) {
            abort(404);
        }

        return Storage::disk('tenants')->response($path);
    }

    /**
     * Remove o arquivo de logo procurando em todos os esquemas de armazenamento
     * já usados pelo projeto: o atual (arquivo direto na raiz do disco, nomeado
     * pelo id do TenantsDetail) e os antigos, por pasta (normalizada ou com o id
     * cru do tenant), para não deixar arquivo órfão de uploads anteriores.
     */
    private function deleteEverywhere(Tenant $tenant, string $fileName): void
    {
        foreach ($tenant->logoPathCandidates($fileName) as $path) {
            if (Storage::disk('tenants')->exists($path)) {
                Storage::disk('tenants')->delete($path);
            }
        }
    }

    private function ensureDiskRootExists(string $disk): void
    {
        $root = Storage::disk($disk)->path('');

        if (! is_dir($root)) {
            @mkdir($root, 0755, true);
        }

        @chmod($root, 0755);
    }
}
