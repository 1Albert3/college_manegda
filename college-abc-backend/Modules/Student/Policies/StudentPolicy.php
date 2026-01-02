<?php

namespace Modules\Student\Policies;

use App\Models\User;
use Modules\Student\Entities\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->hasRole('admin') || $user->hasRole('direction') || $user->role === 'super_admin' || $user->hasRole('secretariat')) {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return $user->can('view-students');
    }

    public function view(User $user, Student $student)
    {
        if ($user->can('view-students')) {
            return true;
        }

        // Parent accessing their child
        if ($user->hasRole('parent') && $student->parents->contains($user->id)) {
            return true;
        }

        // Student accessing themselves
        if ($user->hasRole('student') && $student->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user)
    {
        return $user->can('create-students');
    }

    public function update(User $user, Student $student)
    {
        return $user->can('update-students');
    }

    public function delete(User $user, Student $student)
    {
        return $user->can('delete-students');
    }
}
