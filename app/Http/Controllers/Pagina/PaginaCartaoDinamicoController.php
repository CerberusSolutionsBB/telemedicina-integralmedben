<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantsDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PaginaCartaoDinamicoController extends Controller
{
    private const TIPO_COLUNA = [
        'logo' => 'cartao_logo',
        'frente' => 'cartao_imagem_frente',
        'verso' => 'cartao_imagem_verso',
    ];

    public const FONTES_DISPONIVEIS = ['sans-serif', 'serif', 'monospace'];

    public function updateCores(Request $request, Tenant $tenant)
    {
        $request->validate([
            'cartao_cor_primaria' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cartao_cor_secundaria' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cartao_cor_texto' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cartao_fonte' => ['nullable', 'string', 'in:'.implode(',', self::FONTES_DISPONIVEIS)],
        ]);

        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenant->id]);

        $detail->update([
            'cartao_cor_primaria' => $request->input('cartao_cor_primaria'),
            'cartao_cor_secundaria' => $request->input('cartao_cor_secundaria'),
            'cartao_cor_texto' => $request->input('cartao_cor_texto'),
            'cartao_fonte' => $request->input('cartao_fonte'),
        ]);

        return redirect()
            ->route('pagina.show', $tenant->id)
            ->with('message', 'Estilo do Cartão Dinâmico atualizado com sucesso.')
            ->with('type', 'success');
    }

    public function storeImagem(Request $request, Tenant $tenant, string $tipo)
    {
        if (! isset(self::TIPO_COLUNA[$tipo])) {
            abort(404);
        }

        $request->validate([
            'imagem' => ['required', 'image', 'max:2048'],
        ]);

        $coluna = self::TIPO_COLUNA[$tipo];
        $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenant->id]);

        $file = $request->file('imagem');
        $fileName = 'cartao_'.$tipo.'_'.$detail->id.'_'.time().'.'.$file->getClientOriginalExtension();

        $this->ensureDiskRootExists('tenants');

        $dimensions = @getimagesize($file->getRealPath());

        $file->storeAs('', $fileName, 'tenants');

        if ($detail->{$coluna}) {
            $this->deleteEverywhere($tenant, $detail->{$coluna});
        }

        $detail->update([$coluna => $fileName]);

        $url = route('pagina.configuracao.cartao-dinamico.imagem.show', [$tenant->id, $tipo]).'?v='.urlencode($fileName);

        return response()->json([
            'success' => true,
            'imagem' => [
                'tipo' => $tipo,
                'url' => $url,
                'nome' => $fileName,
                'formato' => strtoupper($file->getClientOriginalExtension()),
                'tamanho' => $file->getSize(),
                'largura' => $dimensions[0] ?? null,
                'altura' => $dimensions[1] ?? null,
            ],
        ]);
    }

    public function destroyImagem(Tenant $tenant, string $tipo)
    {
        if (! isset(self::TIPO_COLUNA[$tipo])) {
            abort(404);
        }

        $coluna = self::TIPO_COLUNA[$tipo];
        $detail = TenantsDetail::where('tenant_id', $tenant->id)->first();

        if (! $detail || ! $detail->{$coluna}) {
            return response()->json([
                'success' => false,
                'message' => 'Nenhuma imagem encontrada para este tipo.',
            ], 404);
        }

        $this->deleteEverywhere($tenant, $detail->{$coluna});

        $detail->update([$coluna => null]);

        return response()->json(['success' => true]);
    }

    /**
     * Serve a imagem do cartão dinâmico diretamente, no mesmo padrão de
     * PaginaLogoController::show (sem depender do symlink público).
     */
    public function showImagem(Tenant $tenant, string $tipo)
    {
        if (! isset(self::TIPO_COLUNA[$tipo])) {
            abort(404);
        }

        $coluna = self::TIPO_COLUNA[$tipo];
        $detail = TenantsDetail::where('tenant_id', $tenant->id)->first();

        if (! $detail || ! $detail->{$coluna}) {
            abort(404);
        }

        $path = $tenant->resolveCartaoAssetPath($detail->{$coluna});

        if (! $path) {
            abort(404);
        }

        return Storage::disk('tenants')->response($path);
    }

    public function toggleEnabled(Tenant $tenant)
    {
        try {
            $detail = TenantsDetail::firstOrCreate(['tenant_id' => $tenant->id]);

            $config = $detail->configuracao ?? [];
            $current = $config['cartao_dinamico_enabled'] ?? false;
            $config['cartao_dinamico_enabled'] = ! $current;
            $detail->update(['configuracao' => $config]);

            return redirect()
                ->route('pagina.show', $tenant->id)
                ->with('message', 'Cartão Dinâmico atualizado com sucesso.')
                ->with('type', 'success');
        } catch (\Throwable $e) {
            Log::error('Erro ao atualizar cartao_dinamico_enabled', [
                'tenant_id' => $tenant->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('message', 'Não foi possível atualizar o status.')
                ->with('type', 'error');
        }
    }

    private function deleteEverywhere(Tenant $tenant, string $fileName): void
    {
        foreach ($tenant->cartaoAssetPathCandidates($fileName) as $path) {
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
