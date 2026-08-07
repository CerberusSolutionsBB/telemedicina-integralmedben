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

            $file->storeAs($tenant->id, $fileName, 'tenants');

            if ($detail->logo) {
                $oldPath = $tenant->id.'/'.$detail->logo;
                if (Storage::disk('tenants')->exists($oldPath)) {
                    Storage::disk('tenants')->delete($oldPath);
                }
            }

            $detail->update(['logo' => $fileName]);

            $url = Storage::disk('tenants')->url($tenant->id.'/'.$fileName);

            return response()->json([
                'success' => true,
                'logo' => [
                    'id' => $detail->id,
                    'url' => $url,
                    'nome' => $fileName,
                    'formato' => strtoupper($file->getClientOriginalExtension()),
                    'tamanho' => $file->getSize(),
                    'largura' => null,
                    'altura' => null,
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
}
