<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantStoreRequest;
use App\Http\Services\Tenant\AddSmsQuotaService;
use App\Http\Services\Tenant\TenantAllService;
use App\Http\Services\Tenant\TenantDeleteService;
use App\Http\Services\Tenant\TenantService;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService,
        private TenantAllService $tenantAllService,
        private TenantDeleteService $tenantDeleteService,
        private AddSmsQuotaService $addSmsQuotaService,
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->tenantAllService->execute();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TenantStoreRequest $request)
    {
        $data = $request->validated();
        $tenant = $this->tenantService->store($data);

        return redirect(route('dashboard'))
            ->with('sucess', 'Tenant criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TenantStoreRequest $request, Tenant $tenant)
    {
        $data = $request->validated();
        $this->tenantService->update($tenant, $data);

        return redirect(route('dashboard'))
            ->with('success', 'Tenant atualizado com sucesso!');
    }

    public function addSmsQuota(Request $request, Tenant $tenant)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:10000',
            'notes'  => 'nullable|string|max:255',
        ]);

        try {
            $this->addSmsQuotaService->execute($tenant, $request->amount, $request->notes);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('success', "Cota de {$request->amount} SMS adicionada com sucesso.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $this->tenantDeleteService->execute($id);

        return redirect(route('dashboard'))
            ->with('success', 'Tenant excluído com sucesso!');
    }
}
