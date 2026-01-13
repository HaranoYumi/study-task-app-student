<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Lesson6-2用：敢えて冗長なコード（Before版）
 * Laravelのデフォルトエラーハンドリングを学ぶための教材コード
 */
class TaskController extends ApiController
{
    /**
     * プロジェクトのタスク一覧を取得
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

        $tasks = $project->tasks;

        return response()->json($tasks);
    }

    /**
     * タスク作成
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
            'title' => 'required|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,doing,done',
            'due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $task = $project->tasks()->create($request->all());

        return response()->json($task, 201);
    }

    /**
     * タスク詳細を取得
     */
    public function show($id): JsonResponse
    {
        // ❌ 冗長：Route Model Bindingを使わず、手動でチェック
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'タスクが見つかりません'
            ], 404);
        }

        return response()->json($task);
    }

    /**
     * タスク更新
     */
    public function update(Request $request, $id): JsonResponse
    {
        // ❌ 冗長：手動で存在チェック
        $task = Task::find($id);
        
        if (!$task) {
            return response()->json([
                'message' => 'タスクが見つかりません'
            ], 404);
        }

        // ❌ 冗長：手動でバリデーション
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:todo,doing,done',
            'due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors' => $validator->errors()
            ], 422);
        }

        $task->update($request->all());

        return response()->json($task);
    }

    /**
     * タスク削除
     */
    public function destroy($id): JsonResponse
    {
        // ❌ 冗長：手動で存在チェック
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'タスクが見つかりません'
            ], 404);
        }

        $task->delete();

        return response()->json(['message' => 'タスクを削除しました']);
    }

    /**
     * タスクを開始（todo → doing）
     */
    public function start($id): JsonResponse
    {
        // ❌ 冗長：手動で存在チェック
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'タスクが見つかりません'
            ], 404);
        }

        $task->update(['status' => 'doing']);

        return response()->json($task);
    }

    /**
     * タスクを完了（doing → done）
     */
    public function complete($id): JsonResponse
    {
        // ❌ 冗長：手動で存在チェック
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'message' => 'タスクが見つかりません'
            ], 404);
        }

        $task->update(['status' => 'done']);

        return response()->json($task);
    }
}
