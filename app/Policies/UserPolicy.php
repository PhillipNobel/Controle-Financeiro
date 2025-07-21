<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only super_admin can view users
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Only super_admin can view individual users
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super_admin can create users
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Only super_admin can update users
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Only super_admin can delete users, but not themselves
        return $user->role === UserRole::SUPER_ADMIN && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        // Only super_admin can restore users
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only super_admin can force delete users, but not themselves
        return $user->role === UserRole::SUPER_ADMIN && $user->id !== $model->id;
    }
}
