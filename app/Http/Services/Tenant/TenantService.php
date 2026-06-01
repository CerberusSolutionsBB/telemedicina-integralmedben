<?php

namespace App\Http\Services\Tenant;

class TenantService
{
    public function __construct(
        private TenantCreateService $createService,
        private TenantUpdateService $updateService,
        private TenantDeleteService $deleteService,
        // private TenantShowService $showService,
        // private TenantListService $listService
    ) {}

    public function store($data)
    {
        return $this->createService->execute($data);
    }

    public function update($tenant, $data)
    {
        return $this->updateService->execute($tenant, $data);
    }

    public function delete($id)
    {
        return $this->deleteService->execute($id);
    }

    // public function show($id)
    // {
    //     return $this->showService->execute($id);
    // }

    // public function index($filters = [])
    // {
    //     return $this->listService->execute($filters);
    // }
}
