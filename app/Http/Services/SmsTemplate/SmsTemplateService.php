<?php

namespace App\Http\Services\SmsTemplate;

use App\Models\SmsTemplate;

class SmsTemplateService
{
    public function __construct(
        private GetSmsTemplatesService $getService,
        private StoreSmsTemplateService $storeService,
        private UpdateSmsTemplateService $updateService,
        private DeleteSmsTemplateService $deleteService,
        private AssignTenantSmsTemplateService $assignService,
    ) {}

    public function getAll()
    {
        return $this->getService->execute();
    }

    public function create(array $data): SmsTemplate
    {
        return $this->storeService->execute($data);
    }

    public function update(SmsTemplate $template, array $data): void
    {
        $this->updateService->execute($template, $data);
    }

    public function delete(SmsTemplate $template): void
    {
        $this->deleteService->execute($template);
    }

    public function assignTenants(SmsTemplate $template, array $tenantIds): void
    {
        $this->assignService->execute($template, $tenantIds);
    }
}
