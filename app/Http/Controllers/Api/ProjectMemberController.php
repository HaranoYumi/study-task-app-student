<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Lesson6-3: After版 - Laravelの機能を活用したスッキリしたコード
 * - Route Model Binding で404チェックを自動化
 * - シンプルなバリデーション
 */
class ProjectMemberController extends ApiController
{
    /**
     * プロジェクトのメンバー一覧を取得
     */
    public function index(Project $project): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $members = $project->members;
        return response()->json($members);
    }

    /**
     * プロジェクトにメンバーを追加
     */
    public function store(Request $request, Project $project): JsonResponse
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

        return response()->json($user, 201);
    }

    /**
     * プロジェクトからメンバーを削除
     */
    public function destroy(Project $project, User $user): JsonResponse
    {
        // ✅ Route Model Bindingで$project、$userともに存在保証済み
        
        // メンバーを削除
        $project->members()->detach($user->id);

        return response()->json(['message' => 'メンバーを削除しました']);
    }
}
