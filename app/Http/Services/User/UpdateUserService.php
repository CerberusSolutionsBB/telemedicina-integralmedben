<?php

namespace App\Http\Services\User;

use App\Models\User;

class UpdateUserService
{
    public function execute(User $user, array $data): User
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return $user;
    }
}
