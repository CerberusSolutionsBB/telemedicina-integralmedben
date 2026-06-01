<?php

namespace App\Http\Controllers\Siprov;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SiprovCreateController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Siprov/Create', [
            'planos' => [
                [
                    'label' => 'Clínica Familiar',
                    'value' => 'clinica_familiar',
                ],
                [
                    'label' => 'Clínica Individual',
                    'value' => 'clinica_individual',
                ],
                [
                    'label' => 'Saúde Mental',
                    'value' => 'saude_mental',
                ],
            ],
        ]);
    }
}
