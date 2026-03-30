<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexFormCategoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search     = $request->input('search');
        $categories = FormCategory::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
        return Inertia::render('Config/Category/Form/Index', [
            'categories' => $categories,
            'filters'    => ['search' => $search],
        ]);
    }
}