<?php

namespace App\Services\Tenant;

use App\Models\TenantForm;
use Illuminate\Support\Collection;

class TenantFormService
{
    public function sync(string $tenantId, array|Collection $formIds, array $extraData = []): void
    {
        $formIds = collect($formIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        TenantForm::where('tenant_id', $tenantId)
            ->whereNotIn('form_id', $formIds)
            ->update([
                'ativo' => false,
                'principal' => false,
            ]);

        foreach ($formIds as $index => $formId) {
            $this->attachOrUpdate($tenantId, $formId, [
                'ativo' => $extraData['ativo'] ?? true,
                'principal' => $index === 0 && ($extraData['principal'] ?? true),
                'origem' => $extraData['origem'] ?? 'CENTRAL',
                'user_id' => $extraData['user_id'] ?? auth()->id(),
            ]);
        }
    }

    public function attach(string $tenantId, int $formId, array $data = []): TenantForm
    {
        return $this->attachOrUpdate($tenantId, $formId, $data);
    }

    public function detach(string $tenantId, int $formId): bool
    {
        return TenantForm::where('tenant_id', $tenantId)
            ->where('form_id', $formId)
            ->update([
                'ativo' => false,
                'principal' => false,
            ]) > 0;
    }

    public function setPrincipal(string $tenantId, int $formId): void
    {
        TenantForm::where('tenant_id', $tenantId)
            ->where('principal', true)
            ->update(['principal' => false]);

        TenantForm::where('tenant_id', $tenantId)
            ->where('form_id', $formId)
            ->update([
                'principal' => true,
                'ativo' => true,
            ]);
    }

    public function getAtivos(string $tenantId): Collection
    {
        return TenantForm::with('form')
            ->where('tenant_id', $tenantId)
            ->ativo()
            ->get();
    }

    public function getPrincipal(string $tenantId): ?TenantForm
    {
        return TenantForm::with('form')
            ->where('tenant_id', $tenantId)
            ->principal()
            ->first();
    }

    protected function attachOrUpdate(string $tenantId, int $formId, array $data = []): TenantForm
    {
        $tenantForm = TenantForm::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('form_id', $formId)
            ->first();

        $payload = array_merge([
            'tenant_id' => $tenantId,
            'form_id' => $formId,
            'ativo' => true,
            'principal' => false,
            'origem' => 'CENTRAL',
            'user_id' => auth()->id(),
        ], $data);

        if ($tenantForm) {
            if ($tenantForm->trashed()) {
                $tenantForm->restore();
            }

            $tenantForm->update($payload);

            return $tenantForm->fresh();
        }

        return TenantForm::create($payload);
    }
}
