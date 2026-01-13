<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use App\Http\Services\Project\ProjectRules;

class CreateTaskUseCase
{
    public function execute(User $user , Project $project, string $title, string $description): Task
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $project);

        $task = Task::create([
            'project_id' => $project->id,
            'title' => $title,
            'description' => $description,
            'status' => 'todo',
            'created_by' => $user->id,
        ]);

        $task->load('createdBy');

        return $task;
    }
}
