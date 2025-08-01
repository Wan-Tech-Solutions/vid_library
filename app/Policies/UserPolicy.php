<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        return in_array($user->role, ['admin', 'superadmin']);
    }
}
