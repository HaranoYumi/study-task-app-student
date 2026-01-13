<?php

namespace App\Http\Services\Project;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;

class ProjectRules
{
    /**
     * ユーザーがプロジェクトのメンバーかどうかをチェック

     */
    public static function ensureUserIsMember(User $user, Project $project): void
    {
        $isMember = $project->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$isMember) {
            throw new AuthorizationException(
                'このプロジェクトにアクセスする権限がありません',
            );
        }
    }
}