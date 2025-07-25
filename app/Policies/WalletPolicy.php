<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;
use App\Enums\UserRole;
use Illuminate\Auth\Access\Response;

class WalletPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All roles can view wallets
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::EDITOR]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        // All roles can view individual wallets
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::EDITOR]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super_admin and admin can create wallets
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        // Only super_admin and admin can update wallets
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        // Only super_admin and admin can delete wallets
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Wallet $wallet): bool
    {
        // Only super_admin can restore wallets
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Wallet $wallet): bool
    {
        // Only super_admin can force delete wallets
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
