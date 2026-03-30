<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use Illuminate\Http\RedirectResponse;

class DestroyFormCategoryController extends Controller
{
    public function __invoke(int $id): RedirectResponse
    {
        try {
            $category     = FormCategory::findOrFail($id);
            $categoryName = $category->name;
            $category->delete();
            return redirect()
                ->route('configuracoes.categories.forms.index')
                ->with('success', "Categoria '{$categoryName}' excluída com sucesso!");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()
                ->route('configuracoes.categories.forms.index')
                ->with('error', 'Categoria não encontrada.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Erro ao excluir categoria. Tente novamente.');
        }
    }
}