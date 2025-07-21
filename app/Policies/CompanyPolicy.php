<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only super_admin and admin can view company settings
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        // Only super_admin and admin can view company details
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super_admin can create company (should be singleton anyway)
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        // Only super_admin and admin can update company settings
        return in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only super_admin can delete company
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        // Only super_admin can restore company
        return $user->role === UserRole::SUPER_ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        // Only super_admin can force delete company
        return $user->role === UserRole::SUPER_ADMIN;
    }
}
