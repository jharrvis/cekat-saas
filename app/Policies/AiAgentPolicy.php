<?php

namespace App\Policies;

use App\Models\AiAgent;
use App\Models\User;

class AiAgentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiAgent $agent): bool
    {
        return $agent->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AiAgent $agent): bool
    {
        return $agent->user_id === $user->id;
    }

    public function delete(User $user, AiAgent $agent): bool
    {
        return $agent->user_id === $user->id;
    }
}
