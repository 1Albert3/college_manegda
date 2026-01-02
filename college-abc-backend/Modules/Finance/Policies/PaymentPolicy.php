<?php

namespace Modules\Finance\Policies;

use Modules\Core\Entities\User;
use Modules\Finance\Entities\Payment;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('view-finance');
    }

    public function view(User $user, Payment $payment)
    {
        if ($user->can('view-finance')) {
            return true;
        }

        // Parent viewing payment for their child
        if ($user->hasRole('parent') && $payment->student->parents->contains($user->id)) {
            return true;
        }

        // Student viewing their own payment
        if ($user->hasRole('student') && $payment->student->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function create(User $user)
    {
        return $user->can('create-payments');
    }

    public function update(User $user, Payment $payment)
    {
        return $user->can('manage-payments');
    }

    public function delete(User $user, Payment $payment)
    {
        return $user->can('manage-payments'); // Usually deleting payments is restricted
    }

    public function validate(User $user, Payment $payment)
    {
        return $user->can('manage-payments');
    }
    
    public function cancel(User $user, Payment $payment)
    {
        return $user->can('manage-payments');
    }
}
