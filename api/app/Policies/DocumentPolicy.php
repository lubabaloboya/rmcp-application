<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        return $this->hasScopedAccess($user, $document->company_id);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->hasScopedAccess($user, $document->company_id);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->hasScopedAccess($user, $document->company_id);
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
