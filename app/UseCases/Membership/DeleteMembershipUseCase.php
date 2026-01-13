<?php

namespace App\UseCases\Membership;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Services\Project\ProjectRules;

class DeleteMembershipUseCase
{
    public function execute(User $user , Project $project): void
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $project);

        // 削除対象のユーザーを取得
        $targetUser = $project->users()
            ->where('users.id', $user->id)
            ->first();

        if (!$targetUser) {
            throw new AuthorizationException(
                'User is not a member of this project.',
            );
        }

        // 自分がowner/adminかチェック（users()リレーションを使用）
        $myUser = $project->users()
            ->where('users.id', $user->id)
            ->first();

        if (!$myUser || !in_array($myUser->pivot->role, ['project_owner', 'project_admin'])) {
            throw new AuthorizationException(
                'メンバーを削除する権限がありません（オーナーまたは管理者のみ）',
            );
        }

        // Owner維持チェック（Owner削除後に0人になる場合は不可）
        if ($targetUser->pivot->role === 'project_owner') {
            $ownerCount = $project->users()
                ->wherePivot('role', 'project_owner')
                ->count();

            if ($ownerCount <= 1) {
                throw new AuthorizationException(
                    'プロジェクトの最後のオーナーは削除できません',
                );
            }
        }

        // 未完了タスクチェック
        $hasIncompleteTasks = $project->tasks()
            ->where('created_by', $user->id)
            ->whereIn('status', ['todo', 'doing'])
            ->exists();

        if ($hasIncompleteTasks) {
            throw new AuthorizationException(
                '未完了のタスクがあるメンバーは削除できません',
            );
        }

        // 削除実行（users()リレーションのdetach()を使用）
        $project->users()->detach($user->id);

    }
    }

