<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lesson6-3: After版
 * - 自分が所属しているプロジェクトのみ取得
 * - 403チェック: プロジェクトメンバーのみアクセス可能
 * - FormRequestを使用
 */
class ProjectController extends ApiController
{
    /**
     * 自分が所属しているプロジェクト一覧を返す
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $request->user()
            ->projects()
            ->with('users')
            ->orderBy('created_at', 'desc')
            ->get();

        return ProjectResource::collection($projects);
    }

    /**
     * プロジェクト新規作成
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());

        // 作成者をオーナーとして追加
        $project->users()->attach($request->user()->id, [
            'role' => 'project_owner',
        ]);

        return (new ProjectResource($project))
            ->additional(['message' => 'プロジェクトを作成しました'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * プロジェクト詳細を返す
     */
    public function show(Request $request, Project $project): ProjectResource|JsonResponse
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

        $project->load(['users', 'tasks.createdBy']);

        return new ProjectResource($project);
    }

    /**
     * プロジェクト更新
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource|JsonResponse
    {
        // 自分がオーナーまたは管理者かチェック
        $myUser = $project->users()
            ->where('users.id', $request->user()->id)
            ->first();

        if (!$myUser || !in_array($myUser->pivot->role, ['project_owner', 'project_admin'])) {
            return response()->json([
                'message' => 'プロジェクトを編集する権限がありません',
            ], 403);
        }

        $project->update($request->validated());
        $project->load(['users', 'tasks.createdBy']);

        return (new ProjectResource($project))
            ->additional(['message' => 'プロジェクトを更新しました']);
    }

    /**
     * プロジェクト削除
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        // 自分がオーナーかチェック
        $myUser = $project->users()
            ->where('users.id', $request->user()->id)
            ->first();

        if (!$myUser || $myUser->pivot->role !== 'project_owner') {
            return response()->json([
                'message' => 'プロジェクトを削除する権限がありません（オーナーのみ）',
            ], 403);
        }

        $project->delete();

        return response()->json([
            'message' => 'プロジェクトを削除しました',
        ]);
    }
}
