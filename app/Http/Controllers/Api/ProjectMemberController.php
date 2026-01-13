<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Membership\AddMemberRequest;

class ProjectMemberController extends ApiController
{
    /**
     * プロジェクトのメンバー一覧を取得
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection|JsonResponse
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

        // 2. メンバー一覧の取得
        // withPivot に 'id' を含めることで、Membership の ID も取得できます
        $members = $project->users()
            ->withPivot('id', 'role')
            ->get();

        // 3. Resourceに渡すだけ
        return ProjectMemberResource::collection($members);
    }

    /**
     * プロジェクトにメンバーを追加
     * POST /api/projects/{project}/members
     */
    public function store(Request $request, Project $project)
    {
        // 権限チェック：オーナーまたは管理者か
        $currentUser = $project->users()
            ->where('users.id', $request->user()->id)
            ->first();

        // メンバーじゃない
        if (!$currentUser) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません'
            ], 403);
        }

        // メンバーだけど、オーナー/管理者じゃない
        if (!in_array($currentUser->pivot->role, ['project_owner', 'project_admin'])) {
            return response()->json([
                'message' => 'メンバーを追加する権限がありません（オーナーまたは管理者のみ）'
            ], 403);
        }

        // 重複チェック（409）
        $exists = $project->users()
            ->where('users.id', $request->user_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'このユーザーは既にプロジェクトのメンバーです'
            ], 409);
        }

        // メンバー追加
        $project->users()->attach($request->user_id, [
            'role' => $request->role ?? 'project_member'
        ]);

        $newMember = $project->users()
            ->where('users.id', $request->user_id)
            ->first();

        return response()->json($newMember, 201);
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
