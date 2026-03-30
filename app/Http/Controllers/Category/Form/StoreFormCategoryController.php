<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\FormCategoryRequest;
use App\Models\FormCategory;
use Illuminate\Http\RedirectResponse;

class StoreFormCategoryController extends Controller
{
    public function __invoke(FormCategoryRequest $request): RedirectResponse
    {
        try {
            $category = FormCategory::create($request->validated());

            return redirect()
                ->route('configuracoes.categories.forms.index')
                ->with('success', "Categoria '{$category->name}' criada com sucesso!");

        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Erro ao criar categoria: ' . $e->getMessage());
        }
    }
}