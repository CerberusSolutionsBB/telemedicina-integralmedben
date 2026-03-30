<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShowFormCategoryController extends Controller
{
    public function __invoke(int $id): Response | RedirectResponse
    {
        try {
            $category = FormCategory::findOrFail($id);

            return Inertia::render('Config/Category/Form/Show', [
                'category' => $category,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('configuracoes.categories.forms.index')
                ->with('error', 'Categoria não encontrada.');
        }
    }
}