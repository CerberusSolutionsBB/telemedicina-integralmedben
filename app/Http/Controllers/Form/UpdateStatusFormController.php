<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateStatusFormController extends Controller
{
    public function __invoke(Request $request, Form $form): RedirectResponse
    {
        Gate::authorize('forms.update.status');

        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in(['ativo', 'rascunho', 'pausado', 'encerrado']),
            ],
        ]);

        $newStatus     = $validated['status'];
        $currentStatus = $form->status;

        $this->validateStatusTransition($currentStatus, $newStatus);

        $form->update([
            'status'       => $newStatus,
            'activated_at' => $newStatus === 'ativo' ? now() : $form->activated_at,
        ]);

        return redirect()
            ->route('forms.index')
            ->with('success', "Status atualizado para '{$newStatus}' com sucesso.");
    }

    private function validateStatusTransition(string $current, string $new): void
    {
        $forbiddenTransitions = [
            'encerrado' => ['ativo', 'rascunho', 'pausado'],
        ];

        if (isset($forbiddenTransitions[$current]) && in_array($new, $forbiddenTransitions[$current])) {
            abort(422, "Não é possível alterar o status de '{$current}' para '{$new}'. Formulários encerrados não podem ser reativados.");
        }
    }
}