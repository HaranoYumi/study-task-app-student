<?php

namespace App\UseCases\Membership;

use App\Models\User;
use App\Models\Project;

use Illuminate\Auth\Access\AuthorizationException;

class AddMembershipUseCase
{
    public function execute(User $user , Project $project, int $userId, string $role): User
    {
        
// 自分がowner/adminかチェック
    $myUser = $project->users()
    ->where('users.id', $user->id)
    ->first();

if (!$myUser || !in_array($myUser->pivot->role, ['project_owner', 'project_admin'])) {
    throw new AuthorizationException(
    'メンバーを追加する権限がありません（オーナーまたは管理者のみ）',
    );
}

// 既にメンバーかチェック（users()リレーションを使用）
$existingUser = $project->users()
    ->where('users.id', $userId)
    ->first();

if ($existingUser) {
    throw new AuthorizationException(
        'このユーザーは既にプロジェクトのメンバーです',
    );
}

// 自分自身を追加しようとしていないかチェック
if ($userId == $user->id) {
    throw new AuthorizationException(
        'あなたは既にこのプロジェクトのメンバーです',
    );
}

// メンバーシップ作成（users()リレーションのattach()を使用）
$project->users()->attach($userId, [
    'role' => $role,
]);

// ユーザー情報を含めて返す
$user = $project->users()->find($userId);

return $user;
}

}