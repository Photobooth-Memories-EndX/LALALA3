<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can delete the comment.
     *
     * @param  \App\Models\User  $user
     * @param  mixed  $comment
     * @return bool
     */
    public function delete(User $user, $comment): bool
    {
        $userRole = (int) ($user->role_ ?? $user->role ?? 0);

        return (int) $user->id === (int) $comment->user_id || in_array($userRole, [2, 3], true);
    }
}
