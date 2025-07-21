<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All roles can view transactions
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::EDITOR]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        // All roles can view individual transactions
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::EDITOR]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All roles can create transactions
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::EDITOR]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Transaction $transaction): bool
    {
        // All roles can update transactions
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN, UserRole::EDITOR]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Transaction $transaction): bool
    {
        // Only super_admin and admin can delete transactions
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Transaction $transaction): bool
    {
        // Only super_admin can restore transactions
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Transaction $transaction): bool
    {
        // Only super_admin can force delete transactions
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
