<?php
namespace App\Http\Controllers\Lei;

use App\Http\Controllers\Controller;
use App\Models\Lei;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexLeiController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $search = $request->input('search');
        $type   = $request->input('type');

        $leis = Lei::query()
            ->with('user')
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->when($type, fn($q) => $q->where('type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Leis/Index', [
            'leis'    => $leis,
            'filters' => ['search' => $search, 'type' => $type],
        ]);
    }
}