<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreateCredenciasCluble extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Config/CredenciasCluble/Create');
    }
}
