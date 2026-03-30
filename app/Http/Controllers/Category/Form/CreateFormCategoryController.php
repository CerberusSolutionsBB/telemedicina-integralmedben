<?php
namespace App\Http\Controllers\Category\Form;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreateFormCategoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Config/Category/Form/Create');
    }
}