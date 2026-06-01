<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;

class GetPatientsService
{
    public function execute(?string $search = null, ?string $status = null)
    {
        return Patient::with('answers.question')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nome', 'like', "%{$search}%")
                        ->orWhere('cpf', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }
}
