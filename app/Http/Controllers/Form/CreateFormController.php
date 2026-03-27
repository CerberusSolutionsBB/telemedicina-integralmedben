<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreateFormController extends Controller
{
    public function __invoke(Request $request, Form $form = null): Response
    {
        $user = $request->user();
        if ($form && ! $user->can('forms.edit')) {
            abort(403);
        }
        return Inertia::render('Form/Create', [
            'form'          => $form ? $form->load('fields') : null,
            'isEdit'        => ! ! $form,
            'statusOptions' => [
                ['value' => 'rascunho', 'label' => 'Rascunho'],
                ['value' => 'ativo', 'label' => 'Ativo'],
                ['value' => 'pausado', 'label' => 'Pausado'],
                ['value' => 'encerrado', 'label' => 'Encerrado'],
            ],
            'can'           => [
                'create' => $user->can('forms.create'),
                'edit'   => $user->can('forms.edit'),
                'manage' => $user->hasAnyRole(['Admin', 'Manager']),
            ],
        ]);
    }
}