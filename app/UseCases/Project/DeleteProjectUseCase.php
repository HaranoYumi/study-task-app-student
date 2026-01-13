<?php

namespace App\UseCases\Project;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteProjectUseCase
{
    public function execute(User $user , Project $project): void
    {
        // 自分がオーナーかチェック（users()リレーションを使用）
        $myUser = $project->users()
            ->where('users.id', $user->id)
            ->first();

        if (!$myUser || $myUser->pivot->role !== 'project_owner') {
            throw new AuthorizationException( 
                'プロジェクトを削除する権限がありません（オーナーのみ）',
            );
        }
        $project->delete();

    }
}
