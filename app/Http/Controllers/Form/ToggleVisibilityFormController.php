<?php

namespace App\Http\Controllers\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleVisibilityFormRequest;
use App\Models\Form;
use Illuminate\Support\Facades\Log;

class ToggleVisibilityFormController extends Controller
{
    public function __invoke(ToggleVisibilityFormRequest $request)
    {
        try {
            $validated = $request->validated();
            $form = Form::findOrFail($validated['form_id']);
            $form->update([
                'is_public' => (int) $validated['is_public'],
            ]);
            Log::info('Visibilidade alterada', [
                'form_id' => $form->id,
                'is_public' => $validated['is_public'],
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('forms.index')
                ->with('success', 'Visibilidade atualizada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao alterar visibilidade', [
                'form_id' => $request->input('form_id'),
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('forms.index')
                ->withErrors([
                    'error' => 'Erro ao alterar visibilidade: '.$e->getMessage(),
                ]);
        }
    }
}
