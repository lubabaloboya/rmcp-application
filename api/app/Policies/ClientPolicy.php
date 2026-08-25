<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasScopedAccess($user, null);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->hasScopedAccess($user, $client->company_id);
    }

    public function create(User $user): bool
    {
        return $this->hasScopedAccess($user, null);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->hasScopedAccess($user, $client->company_id);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->hasScopedAccess($user, $client->company_id);
    }

    private function hasScopedAccess(User $user, ?int $companyId): bool
    {
        if (in_array('*', $user->permissions(), true)) {
            return true;
        }

        if ($user->company_id === null) {
            return true;
        }

        if ($companyId === null) {
            return true;
        }

        return (int) $user->company_id === (int) $companyId;
    }
}
