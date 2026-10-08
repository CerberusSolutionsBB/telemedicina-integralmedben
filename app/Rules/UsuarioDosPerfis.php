<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\Permission\Models\Role;

/**
 * O usuário precisa ter ao menos um dos perfis (roles) escolhidos na meta.
 */
class UsuarioDosPerfis implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $perfis = Role::whereIn('id', (array) ($this->data['roles'] ?? []))->pluck('name');

        if ($perfis->isNotEmpty() && ! User::role($perfis)->whereKey($value)->exists()) {
            $fail('O usuário selecionado não pertence aos perfis escolhidos.');
        }
    }
}
