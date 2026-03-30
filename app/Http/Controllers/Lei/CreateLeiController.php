<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreateLeiController extends Controller
{
    public function __invoke(Request $request): Response
    {

        return Inertia::render('Leis/Create');
    }
}