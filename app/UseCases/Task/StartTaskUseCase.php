<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Task;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Services\Project\ProjectRules;

class StartTaskUseCase
{
    public function execute(User $user , Task $task): Task
{
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $task->project);

        // 状態チェック
        if ($task->status !== 'todo') {
            throw new AuthorizationException(
                '未着手のタスクのみ開始できます',
            );
        }

        $task->update(['status' => 'doing']);
        $task->load('createdBy');

        return $task;
}
}
