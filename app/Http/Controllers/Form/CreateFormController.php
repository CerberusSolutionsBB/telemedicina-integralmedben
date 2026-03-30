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

class CreateFormController extends Controller
{
    public function __invoke(Request $request, ?Form $form = null): Response
    {
        $user = $request->user();

        if ($form && ! $user->can('forms.edit')) {
            abort(403, 'Você não tem permissão para editar este formulário.');
        }

        return Inertia::render('Form/Create', [
            'form'          => $form ? $this->loadForm($form) : null,
            'isEdit'        => $form !== null,
            'statusOptions' => $this->getStatusOptions(),
            'categorias'    => $this->getCategorias($request),
            'leis'          => $this->getLeis($request),
            'can'           => [
                'create' => $user->can('forms.create'),
                'edit'   => $user->can('forms.edit'),
                'manage' => $user->hasAnyRole(['Admin', 'Manager']),
            ],
        ]);
    }

    private function loadForm(Form $form): Form
    {
        return $form->load(['fields', 'categoria:id,name', 'lei:id,title,type']);
    }

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
     * @return array<int, array{value: int, label: string, description: ?string}>
     */
    private function getCategorias(Request $request): array
    {
        $search = $request->input('categoria_search');

        return FormCategory::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'description'])
            ->map(fn(FormCategory $c) => [
                'value'       => $c->id,
                'label'       => $c->name,
                'description' => $c->description,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{value: int, label: string, type: string, title: string}>
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
            ->map(fn(Lei $l) => [
                'value' => $l->id,
                'label' => "[{$l->tipo_label}] " . Str::limit($l->title, 60),
                'type'           => $l->type,
                'title'          => $l->title,
            ])
            ->toArray();
    }
}