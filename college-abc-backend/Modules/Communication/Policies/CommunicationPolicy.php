<?php

namespace Modules\Communication\Policies;

use Modules\Core\Entities\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunicationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view-communications') || $user->hasRole('super_admin');
    }

    public function send(User $user)
    {
        // Only staff/admins should send communications usually
        return $user->can('send-communications') || $user->hasRole('director') || $user->hasRole('admin');
    }

    public function sendBulk(User $user)
    {
        return $user->can('send-communications') || $user->hasRole('director') || $user->hasRole('admin');
    }

    public function viewLogs(User $user)
    {
        return $user->can('view-communications') || $user->hasRole('admin');
    }
}
