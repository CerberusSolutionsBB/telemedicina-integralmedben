<?php

namespace App\Http\Controllers\Tenant\Form;

use App\Http\Controllers\Controller;
use App\Models\TenantForm;
use App\Models\TenantsDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormIndexController extends Controller
{
    private const DISPONIBILIDADES = ['disponiveis', 'indisponiveis'];

    public function __invoke(Request $request): Response
    {
        $currentTenant = tenant();
        $tenantDetails = TenantsDetail::where('tenant_id', $currentTenant->id)
            ->first();

        $search = trim((string) $request->input('search', ''));
        $disponibilidade = in_array($request->input('disponibilidade'), self::DISPONIBILIDADES, true)
            ? $request->input('disponibilidade')
            : '';

        $baseQuery = TenantForm::query()
            ->where('tenant_id', $currentTenant->id)
            ->ativo()
            ->whereHas('form');

        // Apenas formulários ativos e não expirados podem ser preenchidos
        $disponivel = fn (Builder $q) => $q->where('status', 'ativo')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        $totalGeral = (clone $baseQuery)->count();
        $totalDisponiveis = (clone $baseQuery)->whereHas('form', $disponivel)->count();

        $tenantForms = (clone $baseQuery)
            ->with('form')
            ->when($search !== '', fn (Builder $q) => $q->whereHas('form', fn (Builder $f) => $f
                ->where(fn (Builder $w) => $w
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"))))
            ->when($disponibilidade === 'disponiveis', fn (Builder $q) => $q->whereHas('form', $disponivel))
            ->when($disponibilidade === 'indisponiveis', fn (Builder $q) => $q->whereDoesntHave('form', $disponivel))
            ->orderByRaw(
                "exists (select 1 from forms f where f.id = tenants_forms.form_id and f.status = 'ativo' and (f.expires_at is null or f.expires_at > ?)) desc",
                [now()]
            )
            ->orderByDesc('principal')
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(function (TenantForm $tenantForm) {
                $form = $tenantForm->form;
                $expirado = $form->expires_at && $form->expires_at->lte(now());

                $tenantForm->setAttribute('expirado', (bool) $expirado);
                $tenantForm->setAttribute('pode_preencher', $form->status === 'ativo' && ! $expirado);

                return $tenantForm;
            });

        return Inertia::render('Tenant/Form/Index', [
            'tenant' => [
                'id' => $currentTenant->id,
                'name' => $currentTenant->name ?? null,
            ],
            'tenantDetails' => $tenantDetails,
            'tenantForms' => $tenantForms,
            'stats' => [
                'total' => $totalGeral,
                'disponiveis' => $totalDisponiveis,
                'indisponiveis' => $totalGeral - $totalDisponiveis,
            ],
            'filters' => [
                'search' => $search,
                'disponibilidade' => $disponibilidade,
            ],
        ]);
    }
}
