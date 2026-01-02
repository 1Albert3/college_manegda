<?php

namespace Modules\Academic\Policies;

use Modules\Core\Entities\User;
use Modules\Academic\Entities\Semester;
use Illuminate\Auth\Access\HandlesAuthorization;

class SemesterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-academic');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Semester $semester): bool
    {
        return $user->can('view-academic');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage-academic');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Semester $semester): bool
    {
        return $user->can('manage-academic');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Semester $semester): bool
    {
        return $user->can('manage-academic');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Semester $semester): bool
    {
        return $user->can('manage-academic');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Semester $semester): bool
    {
        return $user->can('manage-academic');
    }
}
