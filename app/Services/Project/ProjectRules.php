<?php

namespace App\Services\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use App\Exceptions\ConflictException;

/**
 * プロジェクトのビジネスルール（システム全体共有）
 * 
 * 配置理由：
 * - 複数ドメイン（Project, Task, Membership等）で使用するルール
 * - ProjectとUserの関係性に関する判定を一元管理
 */
class ProjectRules
{
    /**
     * ユーザーがプロジェクトメンバーか判定
     * 
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * ユーザーがプロジェクトオーナーか判定
     * 
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function isOwner(Project $project, User $user): bool
    {
        $role = $this->getRole($project, $user);
        return $role === 'project_owner';
    }

    /**
     * ユーザーがプロジェクトオーナーまたは管理者か判定
     * 
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function isOwnerOrAdmin(Project $project, User $user): bool
    {
        $role = $this->getRole($project, $user);
        return in_array($role, ['project_owner', 'project_admin']);
    }

    /**
     * ユーザーが既にメンバーか判定
     * 
     * @param Project $project
     * @param int $userId
     * @return bool
     */
    public function hasUser(Project $project, int $userId): bool
    {
        return $project->users()
            ->where('users.id', $userId)
            ->exists();
    }

    /**
     * プロジェクトメンバーであることを保証（メンバーでなければ例外）
     * 
     * @param Project $project
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function ensureMember(Project $project, User $user)
    {
        $isMember = $project->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }
        return true;
    }

    /**
     * プロジェクトオーナーであることを保証（オーナーでなければ例外）
     * 
     * @param Project $project
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function ensureOwner(Project $project, User $user): void
    {
        if (!$this->isOwner($project, $user)) {
            throw new AuthorizationException('プロジェクトを削除する権限がありません（オーナーのみ）');
        }
    }

    /**
     * プロジェクトオーナーまたは管理者であることを保証（どちらでもなければ例外）
     * 
     * @param Project $project
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function ensureOwnerOrAdmin(Project $project, User $user): void
    {
        if (!$this->isOwnerOrAdmin($project, $user)) {
            throw new AuthorizationException('プロジェクトを更新する権限がありません（オーナー・管理者のみ）');
        }
    }

    /**
     * ユーザーが既にメンバーでないことを保証（メンバーなら例外）
     * 
     * @param Project $project
     * @param int $userId
     * @return void
     * @throws ConflictException
     */
    public function ensureNotMember(Project $project, int $userId): void
    {
        if ($this->hasUser($project, $userId)) {
            throw new ConflictException('既にメンバーです');
        }
    }

    /**
     * プロジェクト内でのユーザーのロールを取得
     * 
     * @param Project $project
     * @param User $user
     * @return string|null ロール（メンバーでない場合はnull）
     */
    private function getRole(Project $project, User $user): ?string
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->first()
            ?->pivot->role;
    }
}
