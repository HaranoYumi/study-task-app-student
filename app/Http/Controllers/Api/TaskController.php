<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\UseCases\Task\CreateTaskUseCase;
use App\UseCases\Task\GetTasksUseCase;
use App\UseCases\Task\GetTaskUseCase;
use App\UseCases\Task\UpdateTaskUseCase;
use App\UseCases\Task\DeleteTaskUseCase;
use App\UseCases\Task\StartTaskUseCase;
use App\UseCases\Task\CompleteTaskUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskController extends ApiController
{
    public function __construct(
        private GetTasksUseCase $getTasksUseCase,
        private CreateTaskUseCase $createTaskUseCase,
        private GetTaskUseCase $getTaskUseCase,
        private UpdateTaskUseCase $updateTaskUseCase,
        private DeleteTaskUseCase $deleteTaskUseCase,
        private StartTaskUseCase $startTaskUseCase,
        private CompleteTaskUseCase $completeTaskUseCase,
    ) {}

    /**
     * プロジェクトのタスク一覧を取得
     *
     * @param Request $request
     * @param Project $project
     * @return AnonymousResourceCollection
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection
    {
        $tasks = $this->getTasksUseCase->execute($project, $request->user());

        return TaskResource::collection($tasks);
    }

    /**
     * タスク作成
     */
    // ✅ Request は Laravel が自動で渡してくれる（DI）
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = $this->createTaskUseCase->execute(
            $request->validated(),
            $project,
            $request->user()
        );

        return $this->response()->createdWithResource(
            new TaskResource($task),
            'タスクを作成しました'
        );
    }

    /**
     * タスク詳細を取得
     */
    public function show(Request $request, Task $task): TaskResource
    {
        $task = $this->getTaskUseCase->execute($task, $request->user());
        return new TaskResource($task);
    }

    /**
     * タスク更新
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $task = $this->updateTaskUseCase->execute(
            $task,
            $request->validated(),
            $request->user()
        );

        return $this->response()->successWithResource(
            new TaskResource($task),
            'タスクを更新しました'
        );
    }

    /**
     * タスク削除
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->deleteTaskUseCase->execute($task, $request->user());

        return $this->response()->success(null, 'タスクを削除しました');
    }

    /**
     * タスクを開始（todo → doing）
     */
    public function start(Request $request, Task $task): JsonResponse
    {
        $task = $this->startTaskUseCase->execute($task, $request->user());
        return $this->response()->successWithResource(
            new TaskResource($task),
            'タスクを開始しました'
        );
    }

    /**
     * タスクを完了（doing → done）
     */
    // public function complete(Task $task, CompleteTaskUseCase $useCase): JsonResponse　→メソッド引数で受け取る
    public function complete(Request $request, Task $task): JsonResponse
    // public function complete(Request $request, Task $task , NotificationService $notificationService): JsonResponse →コントローラーがServiceを知らないといけなくなる

    {
    $this->completeTaskUseCase->execute($task, $user, $notificationService, $logService); →毎回渡す必要がある
        $task = $this->completeTaskUseCase->execute($task, $request->user());
        return $this->response()->successWithResource(
            new TaskResource($task),
            'タスクを完了しました'
        );
    }
}
// // BatchController.php（バッチ処理でも使ってた）
// $this->completeTaskUseCase->execute($task, $user, $notificationService, $logService);
// //                                                                      ↑ 追加

// // TaskApiController.php（別のAPIでも使ってた）
// $this->completeTaskUseCase->execute($task, $user, $notificationService, $logService);
// //                                                                      ↑ 追加

// // TestCode.php（テストコードも全部！）
// $useCase->execute($task, $user, $mockNotification, $mockLog);
// //      
// // 保守性の問題や。UseCase の内部実装を変えただけやのに、呼び出し側を全部修正せなアカン