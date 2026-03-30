<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use Illuminate\Http\RedirectResponse; // ADICIONE ESTE
use Inertia\Inertia;
use Inertia\Response;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

class EditFormCategoryController extends Controller
{
    public function __invoke(int $id): Response | RedirectResponse
    {
        try {
            $category = FormCategory::findOrFail($id);

            return Inertia::render('Config/Category/Form/Edit', [
                'category' => $category,
            ]);

        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('configuracoes.categories.forms.index')
                ->with('error', 'Categoria não encontrada ou foi removida.');
        }
    }
}