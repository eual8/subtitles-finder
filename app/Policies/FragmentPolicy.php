<?php

namespace App\Policies;

use App\Models\Fragment;
use App\Models\User;

class FragmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('admin.fragment.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Fragment $fragment): bool
    {
        return $user->can('admin.fragment.show');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('admin.fragment.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Fragment $fragment): bool
    {
        return $user->can('admin.fragment.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Fragment $fragment): bool
    {
        return $user->can('admin.fragment.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Fragment $fragment): bool
    {
        return $user->can('admin.fragment.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Fragment $fragment): bool
    {
        return $user->can('admin.fragment.delete');
    }
}
