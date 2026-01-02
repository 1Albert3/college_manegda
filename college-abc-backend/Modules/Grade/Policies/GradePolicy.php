<?php

namespace Modules\Grade\Policies;

use Modules\Core\Entities\User;
use Modules\Grade\Entities\Grade;
use Illuminate\Auth\Access\HandlesAuthorization;

class GradePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view-grades');
    }

    public function view(User $user, Grade $grade)
    {
        if ($user->can('view-grades')) {
            return true;
        }

        // Parent accessing their child's grade
        if ($user->hasRole('parent') && $grade->student->parents->contains($user->id)) {
            return true;
        }

        // Student accessing their own grade
        if ($user->hasRole('student') && $grade->student->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user)
    {
        return $user->can('manage-grades') || $user->can('enter-grades') || $user->hasRole('teacher');
    }

    public function update(User $user, Grade $grade)
    {
        if ($user->can('manage-grades')) {
            return true;
        }

        // Teacher can update grades for evaluations they created
        if ($user->hasRole('teacher')) {
            if ($grade->recorder_id === $user->id) return true;
            if ($grade->evaluation && $grade->evaluation->teacher_id === $user->id) return true;
        }

        return false;
    }

    public function delete(User $user, Grade $grade)
    {
        if ($user->can('manage-grades')) {
            return true;
        }

        // Teacher can delete grades for evaluations they created
        if ($user->hasRole('teacher')) {
            if ($grade->evaluation && $grade->evaluation->teacher_id === $user->id) return true;
        }

        return false;
    }
}
