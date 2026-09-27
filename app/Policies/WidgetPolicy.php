<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Widget;

class WidgetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Widget $widget): bool
    {
        return $widget->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Widget $widget): bool
    {
        return $widget->user_id === $user->id;
    }

    public function delete(User $user, Widget $widget): bool
    {
        return $widget->user_id === $user->id;
    }
}
