<?php

namespace App\Http\Controllers\Pagina;

use App\Http\Controllers\Controller;
use App\Models\Form;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaginaCreateController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $forms = Form::orderBy('title', 'asc')->get();

        return Inertia::render('Pagina/Create', [
            'forms' => $forms,
        ]);
    }
}
