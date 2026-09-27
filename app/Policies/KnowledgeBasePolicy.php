<?php

namespace App\Policies;

use App\Models\KnowledgeBase;
use App\Models\User;

class KnowledgeBasePolicy
{
    /**
     * A knowledge base is visible/editable when the user owns it
     * through the AI Agent or through the (legacy) widget link.
     */
    protected function owns(User $user, KnowledgeBase $kb): bool
    {
        if ($kb->aiAgent && $kb->aiAgent->user_id === $user->id) {
            return true;
        }

        if ($kb->widget && $kb->widget->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function view(User $user, KnowledgeBase $kb): bool
    {
        return $this->owns($user, $kb);
    }

    public function update(User $user, KnowledgeBase $kb): bool
    {
        return $this->owns($user, $kb);
    }
}
