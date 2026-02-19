<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViewingRequest;

class ViewingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin;
    }

    public function view(User $user, ViewingRequest $viewingRequest): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin;
    }

    public function confirm(User $user, ViewingRequest $viewingRequest): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin;
    }

    public function cancel(User $user, ViewingRequest $viewingRequest): bool
    {
        return (bool) $user->is_admin && ! $user->is_super_admin;
    }
}
