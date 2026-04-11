<?php
namespace App\Http\Controllers\CredenciasCluble;

use App\Http\Controllers\Controller;
use App\Models\CredenciasCluble;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexCredenciasClubleController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search     = $request->input('search');
        $categories = CredenciasCluble::query()
            ->when($search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('client_id', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
        return Inertia::render('Config/CredenciasCluble/Index', [
            'credencias' => $categories,
            'filters'    => ['search' => $search],
        ]);
    }
}
