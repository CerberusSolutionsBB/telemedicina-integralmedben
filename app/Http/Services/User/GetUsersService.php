<?php

namespace App\Http\Services\User;

use App\Models\User;

class GetUsersService
{
    public function execute(?string $search = null)
    {
        return User::query()
            ->with('roles')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }
}
