<?php

namespace App\UseCases\Project;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateProjectUseCase
{
    public function execute(User $user ,Project $project, array $data): Project
    {
        // 自分が所属しているかチェック（users()リレーションを使用）
        $isMember = $project->users()
        ->where('users.id', $user->id)
        ->exists();

        if (!$isMember || !in_array($isMember->pivot->role, ['project_owner', 'project_admin'], true)) {
            throw new AuthorizationException(
                'プロジェクトを編集する権限がありません',
            );
        }

        //プロジェクト更新
        $project->update([
            'name' => $data['name'],
            'is_archived' => $data['is_archived'] ?? false,
        ]);

        $project->load(['users', 'tasks.createdBy']);

        return $project;
    }
}
