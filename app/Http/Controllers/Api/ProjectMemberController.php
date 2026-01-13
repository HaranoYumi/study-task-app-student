<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Lesson6-2用：敢えて冗長なコード（Before版）
 * Laravelのデフォルトエラーハンドリングを学ぶための教材コード
 */
class ProjectMemberController extends ApiController
{
    /**
     * プロジェクトのメンバー一覧を取得
     */
    public function index(Request $request, $projectId): JsonResponse
    {
        // ❌ 冗長：Route Model Bindingを使わず、手動でチェック
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'message' => 'プロジェクトが見つかりません'
            ], 404);
        }

        $members = $project->members;

        return response()->json($members);
    }

    /**
     * プロジェクトにメンバーを追加
     */
    public function store(Request $request, $projectId): JsonResponse
    {
        // ❌ 冗長：手動でプロジェクトの存在チェック
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'message' => 'プロジェクトが見つかりません'
            ], 404);
        }

        // ❌ 冗長：FormRequestを使わず、手動でバリデーション
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        // ❌ 冗長：手動でユーザーの存在チェック
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'message' => 'ユーザーが見つかりません'
            ], 404);
        }

        // メンバーを追加
        $project->members()->attach($user->id);

        return response()->json($user, 201);
    }

    /**
     * プロジェクトからメンバーを削除
     */
    public function destroy(Request $request, $projectId, $userId): JsonResponse
    {
        // ❌ 冗長：手動でプロジェクトの存在チェック
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'message' => 'プロジェクトが見つかりません'
            ], 404);
        }

        // ❌ 冗長：手動でユーザーの存在チェック
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'ユーザーが見つかりません'
            ], 404);
        }

        // メンバーを削除
        $project->members()->detach($userId);

        return response()->json(['message' => 'メンバーを削除しました']);
    }
}
