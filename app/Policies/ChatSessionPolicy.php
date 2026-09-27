<?php

namespace App\Policies;

use App\Models\ChatSession;
use App\Models\User;

class ChatSessionPolicy
{
    public function view(User $user, ChatSession $session): bool
    {
        return $session->widget !== null && $session->widget->user_id === $user->id;
    }
}
