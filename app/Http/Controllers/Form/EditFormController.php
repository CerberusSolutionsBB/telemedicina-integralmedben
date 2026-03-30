<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormCategory;
use App\Models\Lei;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EditFormController extends Controller
{
    public function __invoke(Request $request, Form $form): Response
    {
        $user = $request->user();

        // Autorização
        if (! $this->canView($user, $form)) {
            abort(403, 'Você não tem permissão para visualizar este formulário.');
        }

        // Carrega relações necessárias
        $form->load([
            'user:id,name',
            'fields' => fn($q) => $q->orderBy('order'),
            'categoria:id,name,description',
            'lei:id,title,type',
        ]);

        return Inertia::render('Form/Edit', [
            'form'          => $this->formatFormData($form),
            'stats'         => $this->getStats($form),
            'responses'     => $this->getResponses($form),
            'statusOptions' => $this->getStatusOptions(),
            'categorias'    => $this->getCategorias($request),
            'leis'          => $this->getLeis($request),
            'can'           => $this->getPermissions($user, $form),
        ]);
    }

    /**
     * Verifica se o usuário pode visualizar o formulário
     */
    private function canView($user, Form $form): bool
    {
        return $user->can('forms.view')
        || $form->user_id === $user->id
        || $form->is_public;
    }

    /**
     * Formata os dados do formulário
     */
    private function formatFormData(Form $form): array
    {
        return [
            'id'              => $form->id,
            'code'            => $form->code,
            'title'           => $form->title,
            'slug'            => $form->slug,
            'description'     => $form->description,
            'status'          => $form->status,
            'is_public'       => $form->is_public,
            'categoria_id'    => $form->categoria_id,
            'lei_id'          => $form->lei_id,
            'published_at'    => $form->published_at,
            'expires_at'      => $form->expires_at,
            'response_limit'  => $form->response_limit,
            'responses_count' => $form->responses_count,
            'created_by'      => $form->user->name,
            'fields'          => $form->fields->map(fn($field) => [
                'id'          => $field->id,
                'type'        => $field->type,
                'label'       => $field->label,
                'placeholder' => $field->placeholder,
                'required'    => $field->required,
                'options'     => $field->options ?? [],
                'help_text'   => $field->help_text,
                'order'       => $field->order,
            ]),
            'settings'        => $form->settings ?? [
                'allow_multiple' => false,
                'show_progress'  => true,
                'theme'          => 'default',
            ],
        ];
    }

    /**
     * Retorna estatísticas do formulário
     */
    private function getStats(Form $form): array
    {
        $lastResponse = $form->responses()->latest()->first();

        return [
            'total_responses'  => $form->responses_count ?? 0,
            'last_response_at' => $lastResponse?->created_at?->format('d/m/Y H:i'),
            'last_response_by' => $lastResponse?->user?->name,
            'completion_rate'  => $this->calculateCompletionRate($form),
        ];
    }

    /**
     * Calcula taxa de preenchimento (exemplo de métrica extra)
     */
    private function calculateCompletionRate(Form $form): ?float
    {
        if ($form->response_limit === null || $form->response_limit === 0) {
            return null;
        }

        return round(($form->responses_count / $form->response_limit) * 100, 1);
    }

    /**
     * Retorna respostas paginadas
     */
    private function getResponses(Form $form)
    {
        return $form->responses()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20)
            ->through(fn($response) => [
                'id'         => $response->id,
                'answers'    => $response->answers,
                'user'       => $response->user,
                'ip_address' => $response->ip_address,
                'user_agent' => $response->user_agent,
                'created_at' => $response->created_at->format('d/m/Y H:i'),
            ]);
    }

    /**
     * Opções de status
     */
    private function getStatusOptions(): array
    {
        return [
            ['value' => 'rascunho', 'label' => 'Rascunho'],
            ['value' => 'ativo', 'label' => 'Ativo'],
            ['value' => 'pausado', 'label' => 'Pausado'],
            ['value' => 'encerrado', 'label' => 'Encerrado'],
        ];
    }

    /**
     * Categorias disponíveis
     */
    private function getCategorias(Request $request): array
    {
        $search = $request->input('categoria_search');

        return FormCategory::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'description'])
            ->map(fn($c) => [
                'value'       => $c->id,
                'label'       => $c->name,
                'description' => $c->description,
            ])
            ->toArray();
    }

    /**
     * Leis disponíveis
     */
    private function getLeis(Request $request): array
    {
        $search = $request->input('lei_search');

        return Lei::query()
            ->when($search, function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'title', 'type'])
            ->map(fn($l) => [
                'value' => $l->id,
                'label' => "[{$l->tipo_label}] " . Str::limit($l->title, 60),
                'type'       => $l->type,
                'title'      => $l->title,
            ])
            ->toArray();
    }

    /**
     * Permissões do usuário
     */
    private function getPermissions($user, Form $form): array
    {
        $isOwner = $form->user_id === $user->id;

        return [
            'view'   => true,
            'edit'   => $user->can('forms.edit') || $isOwner,
            'delete' => $user->can('forms.delete') || $isOwner,
            'share'  => $user->can('forms.share') || $isOwner,
            'manage' => $user->hasAnyRole(['Admin', 'Manager']),
        ];
    }
}