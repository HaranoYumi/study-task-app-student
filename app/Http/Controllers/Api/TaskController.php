<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\DatabaseErrorAlert;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TaskController extends ApiController
{
    /**
     * プロジェクトのタスク一覧を取得
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

        $tasks = $project->tasks()
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->get();

        return TaskResource::collection($tasks);
    }

    /**
     * タスク作成
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
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

        $task = $project->tasks()->create($request->validated());
        $task->load('createdBy');

        return (new TaskResource($task))
            ->additional(['message' => 'タスクを作成しました'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * タスク詳細を取得
     */
    public function show(Request $request, Task $task): TaskResource|JsonResponse
    {
        // 自分が所属しているかチェック
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }

        $task->load(['createdBy', 'project']);

        return new TaskResource($task);
    }

    /**
     * タスク更新
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource|JsonResponse
    {
        // 自分が所属しているかチェック
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }

        // 409チェック：完了済みタスクは編集不可
        if ($task->status === 'done') {
            return response()->json([
                'message' => '完了済みのタスクは編集できません'
            ], 409);
        }

        $task->update($request->validated());
        $task->load('createdBy');

        return (new TaskResource($task))
            ->additional(['message' => 'タスクを更新しました']);
    }

    /**
     * タスク削除
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        // 自分が所属しているかチェック
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }

        // 409チェック：完了済みタスクは削除不可
        if ($task->status === 'done') {
            return response()->json([
                'message' => '完了済みのタスクは削除できません'
            ], 409);
        }

        $task->delete();

        return response()->json([
            'message' => 'タスクを削除しました',
        ]);
    }

    /**
     * タスクを開始（todo → doing）
     */
    public function start(Request $request, Task $task): TaskResource|JsonResponse
    {
        // 自分が所属しているかチェック
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }

        // 状態チェック
        if ($task->status !== 'todo') {
            return response()->json([
                'message' => '未着手のタスクのみ開始できます',
            ], 409);
        }

        $task->update(['status' => 'doing']);
        $task->load('createdBy');

        return new TaskResource($task);
    }

    /**
     * タスクを完了（doing → done）
     */
    public function complete(Request $request, $id): TaskResource|JsonResponse
    {

        // try catchのコメントアウトを解除してデフォルトのLaravelのエラーとどう違うか確認しよう
        try {
            $task = Task::findOrFail($id);

            // 権限チェック
            $isMember = $task->project->users()
                ->where('users.id', $request->user()->id)
                ->exists();

            if (!$isMember) {
                return response()->json([
                    'message' => 'このプロジェクトにアクセスする権限がありません',
                ], 403);
            }

            // 状態チェック
            if ($task->status !== 'doing') {
                return response()->json([
                    'message' => '作業中のタスクのみ完了できます',
                ], 409);
            }


            //  ✅ 意図的なタイポ（updae）でエラー
            $task->update(['status' => 'done']);
            $task->load('createdBy');

            return new TaskResource($task);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'タスク完了エラー',
                'error' => $e->getMessage(),
            ], 500);
        }

        // デフォルトのLaravelのエラーがどうか確認しよう
        //  ✅ 意図的なタイポ（updae）でエラー
        $task->updat(['status' => 'done']);
        $task->load('createdBy');

        return new TaskResource($task);
    }

    /**
     * タスクを完了（doing → done）
     * 正しいtry catchの書き方を確認しよう
     */
    // public function complete(Request $request, $id): TaskResource|JsonResponse
    // {
    //     try {
    //         $task = Task::findOrFail($id);
    //         $task->update(['status' => 'done']);
    //         return new TaskResource($task);
    //     } catch (ModelNotFoundException $e) {
    //         // タスクが見つからない場合
    //         return response()->json([
    //             'message' => '指定されたタスクが見つかりません',
    //         ], 404);
    //     } catch (QueryException $e) {
    //         // DB接続エラーの場合 → 緊急通知！
    //         // こちらのメールは実際には送信されない
    //         Mail::to('admin@example.com')->send(new DatabaseErrorAlert($e));

    //         return response()->json([
    //             'message' => 'データベースエラーが発生しました',
    //         ], 500);
    //     } catch (Exception $e) {
    //         // その他すべての例外
    //         Log::error('予期しないエラー', [
    //             'error' => $e->getMessage(),
    //         ]);

    //         return response()->json([
    //             'message' => 'エラーが発生しました',
    //         ], 500);
    //     }
    // }
}
