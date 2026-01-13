<?php

namespace App\UseCases\Project;

use App\Models\User;
use App\Models\Project;
use App\Http\Services\Project\ProjectRules;

class GetProjectUseCase
{
    public function execute(User $user ,Project $project): Project
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $project);

        $project->load(['users', 'tasks.createdBy']);

        return $project;
    }
}
