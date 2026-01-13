<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteTaskUseCase
{
    public function execute(User $user , Task $task): void
    {
        // 自分がオーナーかチェック（users()リレーションを使用）
        $project = $task->project;
        $myUser = $task->project->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$myUser || $myUser->pivot->role !== 'project_owner') {
            throw new AuthorizationException( 
                'このプロジェクトにアクセスする権限がありません',
            );
        }
        $task->delete();
    }
}
