<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lesson6-3: After版 - Laravelの機能を活用したスッキリしたコード
 * - Route Model Binding で404チェックを自動化
 * - FormRequest でバリデーションを分離
 * - ApiResource でレスポンスを整形
 */
class ProjectController extends ApiController
{
    /**
     * プロジェクト一覧を取得
     */
    public function index(): AnonymousResourceCollection
    {
        $projects = Project::all();
        return ProjectResource::collection($projects);
    }

    /**
     * プロジェクト新規作成
     */
    public function store(StoreProjectRequest $request): ProjectResource
    {
        // ✅ FormRequestで自動的にバリデーション済み
        $project = Project::create($request->validated());
        return new ProjectResource($project);
    }

    /**
     * プロジェクト詳細を返す
     */
    public function show(Project $project): ProjectResource
    {
        // ✅ Route Model Bindingで自動的に404チェック
        return new ProjectResource($project);
    }

    /**
     * プロジェクト更新
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        // ✅ FormRequestで自動的にバリデーション済み
        // ✅ Route Model Bindingで$projectは存在保証済み
        $project->update($request->validated());
        return new ProjectResource($project);
    }

    /**
     * プロジェクト削除
     */
    public function destroy(Project $project): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $project->delete();
        return response()->json(['message' => 'プロジェクトを削除しました']);
    }
}
