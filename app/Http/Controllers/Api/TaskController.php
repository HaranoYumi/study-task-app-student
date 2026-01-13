<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

/**
 * Lesson6-3: After版 - Laravelの機能を活用したスッキリしたコード
 * - Route Model Binding で404チェックを自動化
 * - FormRequest でバリデーションを分離
 */
class TaskController extends ApiController
{
    /**
     * プロジェクトのタスク一覧を取得
     */
    public function index(Project $project): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $tasks = $project->tasks;
        return response()->json($tasks);
    }

    /**
     * タスク作成
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        // ✅ FormRequestで自動的にバリデーション済み
        // ✅ Route Model Bindingで$projectは存在保証済み
        $task = $project->tasks()->create($request->validated());
        return response()->json($task, 201);
    }

    /**
     * タスク詳細を取得
     */
    public function show(Task $task): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        return response()->json($task);
    }

    /**
     * タスク更新
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        // ✅ FormRequestで自動的にバリデーション済み
        // ✅ Route Model Bindingで$taskは存在保証済み
        $task->update($request->validated());
        return response()->json($task);
    }

    /**
     * タスク削除
     */
    public function destroy(Task $task): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $task->delete();
        return response()->json(['message' => 'タスクを削除しました']);
    }

    /**
     * タスクを開始（todo → doing）
     */
    public function start(Task $task): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $task->update(['status' => 'doing']);
        return response()->json($task);
    }

    /**
     * タスクを完了（doing → done）
     */
    public function complete(Task $task): JsonResponse
    {
        // ✅ Route Model Bindingで自動的に404チェック
        $task->update(['status' => 'done']);
        return response()->json($task);
    }
}
