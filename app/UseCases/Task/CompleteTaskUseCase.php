<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Services\Project\ProjectRules;

class CompleteTaskUseCase
{
    public function execute(User $user , Task $task): Task
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $task->project);

        // 状態チェック
        if ($task->status !== 'doing') {
            throw new AuthorizationException(
                '作業中のタスクのみ完了できます',
            );
        }

        $task->update(['status' => 'done']);
        $task->load('createdBy');

        return $task;
    }
}
