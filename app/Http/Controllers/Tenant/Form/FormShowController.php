<?php

namespace App\Http\Controllers\Tenant\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormsResponseTenent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormShowController extends Controller
{
    private function getLogoData(Form $form): ?array
    {
        $logoArquivo = $form->arquivos->first();
        if (! $logoArquivo) {
            return null;
        }

        return [
            'url' => $logoArquivo->url,
            'posicao' => $logoArquivo->pivot->posicao ?? 'centro',
        ];
    }

    public function __invoke(Request $request, Form $form): Response
    {
        $user = $request->user();
        if (! $user->can('forms.view') && $form->user_id !== $user->id && ! $form->is_public) {
            abort(403);
        }
        $form->load(['user:id,name', 'fields' => fn ($q) => $q->orderBy('order')]);
        $host = request()->getHost();
        $currentTenant = str($host)->before('.')->toString();
        $currentTenantResponse = FormsResponseTenent::where('form_id', $form->id)
            ->where('tenant_id', $currentTenant)
            ->whereNotNull('response_id')
            ->pluck('response_id')
            ->toArray();
        $responsesQuery = $form->responses()->with('user:id,name,email');
        if (! empty($currentTenantResponse)) {
            $responsesQuery->whereIn('id', $currentTenantResponse);
        } else {
            $responsesQuery->whereRaw('1 = 0');
        }
        $responses = $responsesQuery->latest()->paginate(20);
        $stats = [
            'total_responses' => count($currentTenantResponse),
            'last_response_at' => $responses->first()?->created_at?->format('d/m/Y H:i'),
        ];

        return Inertia::render('Tenant/Form/Show', [
            'form' => [
                'id' => $form->id,
                'code' => $form->code,
                'title' => $form->title,
                'slug' => $form->slug,
                'description' => $form->description,
                'status' => $form->status,
                'is_public' => $form->is_public,
                'responses_count' => count($currentTenantResponse),
                'created_by' => $form->user->name,
                'primary_color' => $form->primary_color,
                'secondary_color' => $form->secondary_color,
                'lei_id' => $form->lei_id,
                'categoria_id' => $form->categoria_id,
                'settings' => $form->settings,
                'lei' => $form->lei,
                'responses' => count($currentTenantResponse),
                'categoria' => $form->categoria,
                'logo' => $this->getLogoData($form),
                'fields' => $form->fields->map(fn ($f) => [
                    'id' => $f->id,
                    'type' => $f->type,
                    'label' => $f->label,
                    'required' => $f->required,
                    'options' => $f->options,
                ]),
            ],
            'stats' => $stats,
            'responses' => $responses,
            'can' => [
                'edit' => $user->can('forms.edit') || $form->user_id === $user->id,
                'delete' => $user->can('forms.delete') || $form->user_id === $user->id,
            ],
        ]);
    }
}
