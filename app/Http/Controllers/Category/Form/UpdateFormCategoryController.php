<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use App\Http\Requests\Form\FormCategoryUpdateRequest;
use App\Models\FormCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class UpdateFormCategoryController extends Controller
{
    public function __invoke(FormCategoryUpdateRequest $request, int $id): RedirectResponse
    {
        $category = FormCategory::findOrFail($id);
        $baseSlug = Str::slug($request->name);
        $slug     = $baseSlug;
        $count    = 1;
        while (FormCategory::where('slug', $slug)
            ->where('id', '!=', $id)
            ->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }
        $category->update([
            'name'        => $request->name,
            'description' => $request->description,
            'slug'        => $slug,
        ]);
        return redirect()
            ->route('configuracoes.categories.forms.index')
            ->with('success', "Categoria '{$category->name}' atualizada com sucesso!");
    }
}