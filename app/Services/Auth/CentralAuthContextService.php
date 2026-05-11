<?php
namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CentralAuthContextService extends BaseAuthContextService
{
    public function current(): array
    {
        $user = Auth::user();

        if (! $user) {
            return $this->guestContext('central');
        }

        if (! $this->isValidCentralUser($user->id, $user->email)) {
            $this->logoutInvalidSession();

            return $this->guestContext('central');
        }

        return [
            'user'  => $this->userContext($user),
            'can'   => $this->permissions($user),
            'check' => true,
            'type'  => 'central',
        ];
    }

    private function isValidCentralUser(int $userId, string $email): bool
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            return false;
        }

        return DB::connection(config('database.default'))
            ->table('users')
            ->where('id', $userId)
            ->where('email', $email)
            ->exists();
    }
}
