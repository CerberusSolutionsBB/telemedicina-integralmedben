<?php

namespace App\Http\Services\Patient;

use App\Models\Patient;

class GetPatientsService
{
    public function execute(?string $search = null, ?string $status = null, ?string $registro = null)
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
            ->when($registro !== null && $registro !== '', function ($query) use ($registro) {
                $query->where('status_registro', $registro);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }
}
