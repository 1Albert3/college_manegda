<?php

namespace Modules\Grade\Policies;

use Modules\Core\Entities\User;
use Modules\Grade\Entities\Evaluation;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view-grades') || $user->can('view-academic');
    }

    public function view(User $user, Evaluation $evaluation)
    {
        return $user->can('view-grades');
    }

    public function create(User $user)
    {
        return $user->can('manage-grades') || $user->can('create-evaluations') || $user->hasRole('teacher');
    }

    public function update(User $user, Evaluation $evaluation)
    {
        if ($user->can('manage-grades')) {
            return true;
        }

        // Teacher can update their own evaluation
        if ($user->id === $evaluation->teacher_id && $user->can('update-evaluations')) { // Or general teacher permission
            return true;
        }

        // Use general role check if specific permission missing
        if ($user->hasRole('teacher') && $user->id === $evaluation->teacher_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Evaluation $evaluation)
    {
        if ($user->can('manage-grades')) {
            return true;
        }

        if ($user->hasRole('teacher') && $user->id === $evaluation->teacher_id) {
            return true;
        }

        return false;
    }
}
