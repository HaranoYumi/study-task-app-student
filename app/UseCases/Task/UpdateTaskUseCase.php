<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateTaskUseCase
{
    public function execute(User $user ,Task $task, array $data): Task
    {
        // 自分が所属しているかチェック（users()リレーションを使用）
        $isMember = $task->project->users()
        ->where('users.id', $user->id)
        ->exists();

        if (!$isMember || !in_array($isMember->pivot->role, ['project_owner', 'project_admin'], true)) {
            throw new AuthorizationException(
                'このプロジェクトにアクセスする権限がありません',
            );
        }

        //タスク更新
        $task->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
        ]);

        $task->load('createdBy');

        return $task;
    }
}
