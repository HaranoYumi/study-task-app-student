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

use App\UseCases\Task\GetProjectTasksUseCase;
use App\UseCases\Task\CreateTaskUseCase;
use App\UseCases\Task\GetTaskUseCase;
use App\UseCases\Task\UpdateTaskUseCase;
use App\UseCases\Task\DeleteTaskUseCase;
use App\UseCases\Task\StartTaskUseCase;
use App\UseCases\Task\CompleteTaskUseCase;

class TaskController extends ApiController
{
    public function __construct(
        private GetProjectTasksUseCase $getProjectTasksUseCase,
        private CreateTaskUseCase $createTaskUseCase,
        private GetTaskUseCase $getTaskUseCase,
        private UpdateTaskUseCase $updateTaskUseCase,
        private DeleteTaskUseCase $deleteTaskUseCase,
        private StartTaskUseCase $startTaskUseCase,
        private CompleteTaskUseCase $completeTaskUseCase,
    ) {}
    /**
     * プロジェクトのタスク一覧を取得
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection|JsonResponse
    {
        $tasks = $this->getProjectTasksUseCase->execute(
            $request->user(),
            $project,
        );

        return TaskResource::collection($tasks);
    }

    /**
     * タスク作成
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();
        
        $task = $this->createTaskUseCase->execute(
            title: $data['title'],
            description: $data['description'],
            project: $project,
            user: $request->user(),
        );

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
        // タスク詳細を取得
        $task = $this->getTaskUseCase->execute(
            user: $request->user(),
            task : $task,
        );

        return new TaskResource($task);
    }

    /**
     * タスク更新
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource|JsonResponse
    {
        $data = $request->validated();

        // タスク更新
        $task = $this->updateTaskUseCase->execute(
            user: $request->user(),
            task: $task,
            data: $data,
        );

        return (new TaskResource($task))
            ->additional(['message' => 'タスクを更新しました']);
    }

    /**
     * タスク削除
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        // タスク削除
        $this->deleteTaskUseCase->execute(
            user: $request->user(),
            task : $task,
        );

        return response()->json([
            'message' => 'タスクを削除しました',
        ]);
    }

    /**
     * タスクを開始（todo → doing）
     */
    public function start(Request $request, Task $task): TaskResource|JsonResponse
    {
        // タスク詳細を取得
        $task = $this->startTaskUseCase->execute(
            user: $request->user(),
            task : $task,
        );
        
        return new TaskResource($task);
    }

    /**
     * タスクを完了（doing → done）
     */
    public function complete(Request $request, Task $task): TaskResource|JsonResponse
    {
        // タスク詳細を取得
        $task = $this->completeTaskUseCase->execute(
            user: $request->user(),
            task : $task,
        );

        return new TaskResource($task);
    }
}

