<?php

namespace App\Policies;

use App\Models\Screening;
use App\Models\User;

class ScreeningPolicy
{
    public function view(User $user, Screening $screening): bool
    {
        return (int) $screening->user_id === (int) $user->id;
    }

    public function delete(User $user, Screening $screening): bool
    {
        return (int) $screening->user_id === (int) $user->id;
    }
}

