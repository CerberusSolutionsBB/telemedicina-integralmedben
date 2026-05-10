<?php
namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

class PaginaDestroyController extends Controller
{
    public function __invoke(Tenant $tenant): RedirectResponse
    {
        try {
            $tenant->load([
                'details',
                'domains',
                'forms',
            ]);
            $detail = $tenant->details->first();
            $tenant->forms()->detach();
            Domain::where('tenant_id', $tenant->id)->delete();
            if ($detail?->path_arquivos) {
                $basePath = storage_path('app/' . $detail->path_arquivos);
                if (File::exists($basePath)) {
                    File::deleteDirectory($basePath);
                }
            }
            $tenant->details()->delete();
            $tenant->delete();
            return redirect()
                ->route('pagina.index')
                ->with('message', 'Tenant removido com sucesso!')
                ->with('type', 'success');
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors([
                'general' => 'Erro ao remover tenant: ' . $e->getMessage(),
            ]);
        }
    }
}