<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function view(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public function update(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }

    public function delete(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }

    public function restore(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }

    public function setAvailability(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }

    public function manageImages(User $user, Unit $unit): bool
    {
        return (bool) $user->is_admin;
    }
}
