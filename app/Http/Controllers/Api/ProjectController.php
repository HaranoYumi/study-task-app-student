<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Lesson6-2用：敢えて冗長なコード（Before版）
 * Laravelのデフォルトエラーハンドリングを学ぶための教材コード
 */
class ProjectController extends ApiController
{
    /**
     * 自分が所属しているプロジェクト一覧を返す
     */
    public function index(Request $request): JsonResponse
    {
        $projects = Project::all();

        return response()->json($projects);
    }

    /**
     * プロジェクト新規作成
     */
    public function store(Request $request): JsonResponse
    {
        // ❌ 冗長：FormRequestを使わず、手動でバリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $project = Project::create($request->all());

        return response()->json($project, 201);
    }

    /**
     * プロジェクト詳細を返す
     */
    public function show($id): JsonResponse
    {
        // ❌ 冗長：Route Model Bindingを使わず、手動でチェック
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'message' => 'プロジェクトが見つかりません'
            ], 404);
        }

        return response()->json($project);
    }

    /**
     * プロジェクト更新
     */
    public function update(Request $request, $id): JsonResponse
    {
        // ❌ 冗長：手動で存在チェック
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'message' => 'プロジェクトが見つかりません'
            ], 404);
        }

        // ❌ 冗長：手動でバリデーション
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $project->update($request->all());

        return response()->json($project);
    }

    /**
     * プロジェクト削除
     */
    public function destroy($id): JsonResponse
    {
        // ❌ 冗長：手動で存在チェック
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'message' => 'プロジェクトが見つかりません'
            ], 404);
        }

        $project->delete();

        return response()->json(['message' => 'プロジェクトを削除しました']);
    }
}
