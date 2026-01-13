<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lesson6-3: After版 - Laravelの機能を活用したスッキリしたコード
 * - Route Model Binding で404チェックを自動化
 * - シンプルなバリデーション
 * - ApiResource でレスポンスを整形
 */
class ProjectMemberController extends ApiController
{
    /**
     * プロジェクトのメンバー一覧を取得
     */
    public function index(Project $project): AnonymousResourceCollection
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $members = $project->members;
        return ProjectMemberResource::collection($members);
    }

    /**
     * プロジェクトにメンバーを追加
     */
    public function store(Request $request, Project $project): ProjectMemberResource
    {
        // ✅ Route Model Bindingで$projectは存在保証済み

        // バリデーション
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // ユーザーを取得（existsで存在確認済み）
        $user = User::findOrFail($validated['user_id']);

        // メンバーを追加
        $project->members()->attach($user->id);

        // 追加したメンバーを再取得（pivot情報を含む）
        $member = $project->members()->where('users.id', $user->id)->first();

        return new ProjectMemberResource($member);
    }

    /**
     * プロジェクトからメンバーを削除（users()リレーションを使用）
     */
    public function destroy(Request $request, Project $project, $userId): JsonResponse
    {
        // 自分が所属しているかチェック
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }

        // 削除対象のユーザーを取得
        $targetUser = $project->users()
            ->where('users.id', $userId)
            ->first();

        if (!$targetUser) {
            return response()->json([
                'message' => 'User is not a member of this project.',
            ], 404);
        }

        // 自分がowner/adminかチェック（users()リレーションを使用）
        $myUser = $project->users()
            ->where('users.id', $request->user()->id)
            ->first();

        if (!$myUser || !in_array($myUser->pivot->role, ['project_owner', 'project_admin'])) {
            return response()->json([
                'message' => 'メンバーを削除する権限がありません（オーナーまたは管理者のみ）',
            ], 403);
        }

        // Owner維持チェック（Owner削除後に0人になる場合は不可）
        if ($targetUser->pivot->role === 'project_owner') {
            $ownerCount = $project->users()
                ->wherePivot('role', 'project_owner')
                ->count();

            if ($ownerCount <= 1) {
                return response()->json([
                    'message' => 'プロジェクトの最後のオーナーは削除できません',
                ], 409);
            }
        }

        // 未完了タスクチェック
        $hasIncompleteTasks = $project->tasks()
            ->where('created_by', $userId)
            ->whereIn('status', ['todo', 'doing'])
            ->exists();

        if ($hasIncompleteTasks) {
            return response()->json([
                'message' => '未完了のタスクがあるメンバーは削除できません',
            ], 409);
        }

        // 削除実行（users()リレーションのdetach()を使用）
        $project->users()->detach($userId);

        return response()->json([
            'message' => 'メンバーを削除しました',
        ]);
    }
}
