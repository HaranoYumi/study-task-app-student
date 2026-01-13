<?php

namespace App\UseCases\Task;

use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Services\Project\ProjectRules;

class GetProjectTasksUseCase
{
    public function execute(User $user , Project $project): Collection
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $project);

        return $project->tasks()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
