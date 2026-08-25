<?php

namespace App\Policies;

use App\Models\RmcpCase;
use App\Models\User;

class RmcpCasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasScopedAccess($user, null);
    }

    public function view(User $user, RmcpCase $case): bool
    {
        return $this->hasScopedAccess($user, $case->client?->company_id);
    }

    public function create(User $user): bool
    {
        return $this->hasScopedAccess($user, null);
    }

    public function update(User $user, RmcpCase $case): bool
    {
        return $this->hasScopedAccess($user, $case->client?->company_id);
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
