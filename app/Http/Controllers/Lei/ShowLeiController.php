<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use App\Models\Lei;
use Inertia\Inertia;
use Inertia\Response;

class ShowLeiController extends Controller
{
    public function __invoke(Lei $lei): Response
    {
        return Inertia::render('Leis/Show', [
            'lei' => [
                'id'         => $lei->id,
                'title'      => $lei->title,
                'text'       => $lei->text,
                'type'       => $lei->type,
                'type_label' => $lei->tipo_label,
                'status'     => $lei->status ? 'ativo' : ($lei->status === false ? 'inativo' : 'rascunho'),
                'user'       => $lei->user->only('id', 'name', 'email'),
                'created_at' => $lei->created_at,
                'updated_at' => $lei->updated_at,
                'deleted_at' => $lei->deleted_at,
            ],
        ]);
    }
}