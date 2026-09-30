<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return true;
        }

        $permissions = $actor->permissionsGroup;
        if (! $permissions || ! $permissions->roles_status) {
            return false;
        }

        return ! $permissions->view_status || (int) $target->created_by === (int) $actor->id;
    }

    public function updateProfile(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function updateAccount(User $actor, User $target): bool
    {
        return $this->view($actor, $target) && (bool) $actor->permissionsGroup?->webmaster_status;
    }

    public function changePassword(User $actor, User $target): bool
    {
        return $actor->is($target)
            || ($this->view($actor, $target) && (bool) $actor->permissionsGroup?->webmaster_status);
    }

    public function manageMcp(User $actor, User $target): bool
    {
        return $this->changePassword($actor, $target);
    }
}
