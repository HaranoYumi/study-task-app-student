<?php

namespace App\UseCases\Membership;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Project;
use App\Http\Services\Project\ProjectRules;

class GetMembershipUseCase
{
    public function execute(User $user , Project $project): Collection
    {
        // 自分が所属しているかチェック
        ProjectRules::ensureUserIsMember($user, $project);

        // メンバー一覧を取得
        $members = $project->users()
            ->withPivot('id', 'role')
            ->get();

        return $members;
        }
}



