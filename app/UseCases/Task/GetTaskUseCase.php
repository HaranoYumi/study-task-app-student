<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Task;
use App\Http\Services\Project\ProjectRules;

class GetTaskUseCase
{
    public function execute(User $user , Task $task): Task
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $task->project);

        $task->load(['createdBy', 'project']);

        return $task;
    }
    
}
