<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

/**
 * Lesson6-3: After版 - Laravelの機能を活用したスッキリしたコード
 * - Route Model Binding で404チェックを自動化
 * - FormRequest でバリデーションを分離
 */
class ProjectController extends ApiController
{
    /**
     * プロジェクト一覧を取得
     */
    public function index(): JsonResponse
    {
        $projects = Project::all();
        return response()->json($projects);
    }

    /**
     * プロジェクト新規作成
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        // ✅ FormRequestで自動的にバリデーション済み
        $project = Project::create($request->validated());
        return response()->json($project, 201);
    }

    /**
     * プロジェクト詳細を返す
     */
    public function show(Project $project): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        return response()->json($project);
    }

    /**
     * プロジェクト更新
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        // ✅ FormRequestで自動的にバリデーション済み
        // ✅ Route Model Bindingで$projectは存在保証済み
        $project->update($request->validated());
        return response()->json($project);
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
