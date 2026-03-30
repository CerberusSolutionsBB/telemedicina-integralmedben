<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use App\Models\Lei;
use Inertia\Inertia;
use Inertia\Response;

class EditLeiController extends Controller
{
    public function __invoke(Lei $lei): Response
    {
        return Inertia::render('Leis/Edit', [
            'lei'    => [
                'id'      => $lei->id,
                'title'   => $lei->title,
                'text'    => $lei->text,
                'type'    => $lei->type,
                'status'  => $lei->status,
                'user_id' => $lei->user_id,
            ],
            'tipos'  => Lei::TIPOS_LABELS,
            'status' => [
                true  => 'Ativo',
                false => 'Inativo',
            ],
        ]);
    }
}