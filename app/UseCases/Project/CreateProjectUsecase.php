<?php

namespace App\UseCases\Project;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Project;

class CreateProjectUseCase
{
    public function execute(User $user , string $name , bool $isArchived = false): Project
    {
        // プロジェクト作成
        $project = Project::create([
            'name' => $name,
            'is_archived' => $isArchived,
        ]);

        // 作成者をオーナーとして追加
        $project->users()->attach($user()->id, [
            'role' => 'project_owner',
        ]);

        $project->load(['users', 'tasks']);

        return $project;
    }
}
