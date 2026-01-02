<?php

namespace Modules\Attendance\Policies;

use Modules\Core\Entities\User;
use Modules\Attendance\Entities\Attendance;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view-attendances');
    }

    public function view(User $user, Attendance $attendance)
    {
        if ($user->can('view-attendances')) { // Admin/Staff
            return true;
        }

        // Parent viewing own child
        if ($user->hasRole('parent') && $attendance->student->parents->contains($user->id)) {
            return true;
        }

        // Student viewing own
        if ($user->hasRole('student') && $attendance->student->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user)
    {
        // Teachers mark attendance
        return $user->can('mark-attendances') || $user->hasRole('teacher');
    }

    public function update(User $user, Attendance $attendance)
    {
        if ($user->can('manage-attendances')) {
            return true;
        }

        // Teacher can update if they marked it (or within a timeframe - logic not here but possible)
        if ($user->hasRole('teacher') && $attendance->recorded_by === $user->id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, Attendance $attendance)
    {
        return $user->can('manage-attendances');
    }
}
