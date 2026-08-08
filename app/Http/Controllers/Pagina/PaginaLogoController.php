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
            $fileName = 'logo_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $folder = $tenant->logoFolder();

            $this->criarDiretorioSeNaoExistir($folder, 'tenants');

            $dimensions = @getimagesize($file->getRealPath());

            $file->storeAs($folder, $fileName, 'tenants');

            if ($detail->logo) {
                $this->deleteIfExists($tenant, $detail->logo);
            }

            $detail->update(['logo' => $fileName]);

            $url = Storage::disk('tenants')->url($folder.'/'.$fileName);

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

        $this->deleteIfExists($tenant, $detail->logo);

        $detail->update(['logo' => null]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Remove o arquivo de logo, procurando tanto na pasta normalizada atual
     * quanto no id "cru" do tenant, usado por uploads antigos antes da
     * normalização (minúsculas, sem acento, espaço -> "_").
     */
    private function deleteIfExists(Tenant $tenant, string $fileName): void
    {
        $paths = array_unique([
            $tenant->logoFolder().'/'.$fileName,
            $tenant->id.'/'.$fileName,
        ]);

        foreach ($paths as $path) {
            if (Storage::disk('tenants')->exists($path)) {
                Storage::disk('tenants')->delete($path);
            }
        }
    }

    private function criarDiretorioSeNaoExistir(string $caminho, string $disk): void
    {
        $storage = Storage::disk($disk);
        if (! $storage->exists($caminho)) {
            $storage->makeDirectory($caminho);
            $caminhoCompleto = $storage->path($caminho);
            if (is_dir($caminhoCompleto)) {
                chmod($caminhoCompleto, 0755);
            }
        }
    }
}
