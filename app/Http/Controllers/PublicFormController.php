<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicFormRequest;
use App\Http\Services\PublicForm\PublicFormService;
use App\Models\Tenant;
use Inertia\Inertia;

class PublicFormController extends Controller
{
    public function __construct(private PublicFormService $publicFormService) {}

    public function index() {}

    public function show()
    {
        $tenantId = tenant('id');
        $questions = $this->publicFormService->getTenantQuestions($tenantId);
        $tenant = Tenant::find($tenantId);

        return Inertia::render('PublicForm', [
            'questions' => $questions,
            'tenantId' => $tenantId,
            'tenantPhoto' => $tenant->photo_url,
            'tenantBgColor' => $tenant->bg_color,
            'tenantButtonColor' => $tenant->button_color,
            'tenantName' => $tenant->name,
        ]);
    }

    public function store(StorePublicFormRequest $request)
    {
        $data = $request->validated();

        $this->publicFormService->storePatient($data);

        return redirect(route('public_form.show'));
    }
}
