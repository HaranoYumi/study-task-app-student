<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\Notification\LogNotificationService;
use App\Services\Project\ProjectRules;
use App\Exceptions\ConflictException;

/**
 * タスク開始UseCase（todo → doing）
 */
class StartTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
        private LogNotificationService $notificationService,
    ) {}

    /**
     * タスク開始の流れを組み立てる
     * 
     * @param Task $task タスク
     * @param User $user ユーザー
     * @return Task
     */
    public function execute(Task $task, User $user): Task
    {
        // ========================================
        // 1. ビジネスルール検証
        // ========================================
        // 権限チェック（システム全体ルール - 複数UseCaseで共通）
        $this->projectRules->ensureMember($task->project, $user);

        // 状態チェック（privateメソッド - このUseCaseだけで使う）
        $this->ensureCanStart($task);

        // ========================================
        // 2. タスク状態を変更（UseCaseの責務）
        // ========================================
        $task->status = 'doing';
        $task->save();

        // ========================================
        // 3. 通知送信
        // ========================================
        $this->notificationService->notify('task_started', $user, $task->toArray());

        // ========================================
        // 4. リレーションロード
        // ========================================
        $task->load('createdBy');

        return $task;
    }

    /**
     * タスク開始可能か検証
     * 
     * 【なぜprivateメソッドに置くか】
     * - StartTaskUseCaseでしか使わない
     * - ロジックが単純（3行程度）
     * - 単体テスト不要（UseCaseのテストで十分）
     * 
     * @param Task $task タスク
     * @return void
     * @throws ConflictException
     */
    private function ensureCanStart(Task $task): void
    {
        if (!$task->isTodo()) {
            throw new ConflictException('未着手のタスクのみ開始できます');
        }
    }
}
