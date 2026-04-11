<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use App\Models\CredenciasCluble;
use Inertia\Inertia;
use Inertia\Response;

class EditCredenciasClubleController extends Controller
{
    public function __invoke(CredenciasCluble $credenciasCluble): Response
    {
        return Inertia::render('Config/CredenciasCluble/Edit', [
            'credencial' => $credenciasCluble,
        ]);
    }
}
