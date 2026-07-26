<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PaginaStatusController extends Controller
{
    public function __invoke(Tenant $tenant): RedirectResponse
    {
        $newStatus = ! $tenant->status;

        DB::transaction(function () use ($tenant, $newStatus) {
            DB::table('tenants')->where('id', $tenant->id)->update(['status' => $newStatus]);

            DB::statement(
                "UPDATE tenants SET data = JSON_REMOVE(data, '$.status') WHERE id = ? AND JSON_CONTAINS_PATH(data, 'one', '$.status')",
                [$tenant->id]
            );
        });

        return back()->with('message', 'Status atualizado com sucesso!')->with('type', 'success');
    }

    public function bulkDisable(): RedirectResponse
    {
        DB::transaction(function () {
            DB::table('tenants')
                ->where('status', true)
                ->whereNull('deleted_at')
                ->update(['status' => false]);

            DB::statement(
                "UPDATE tenants SET data = JSON_REMOVE(data, '$.status') WHERE JSON_CONTAINS_PATH(data, 'one', '$.status') AND deleted_at IS NULL"
            );
        });

        return to_route('pagina.index')
            ->with('message', 'Todas as páginas foram desativadas com sucesso!')
            ->with('type', 'success');
    }
}
