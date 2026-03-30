<?php
namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ToggleVisibilityFormController extends Controller
{
    public function __invoke(Form $form): RedirectResponse
    {
        try {
            $form = $form->fresh();
            $form->update(['is_public' => ! $form->is_public]);

            $status = $form->is_public ? 'público' : 'privado';

            return redirect()
                ->route('forms.index')
                ->with('success', "Formulário agora está {$status}.");

        } catch (\Exception $e) {
            Log::error('Erro ao alterar visibilidade', [
                'form_id' => $form->id,
                'error'   => $e->getMessage(),
            ]);

            return redirect()
                ->route('forms.index')
                ->with('error', 'Erro ao alterar visibilidade.');
        }
    }
}