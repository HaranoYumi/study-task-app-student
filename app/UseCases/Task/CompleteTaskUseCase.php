<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\Project\ProjectRules;
use App\Exceptions\ConflictException;

/**
 * タスク完了UseCase（doing → done）
 *
 * 役割：
 * - 「完了」という業務シナリオ（検証 → 状態変更 → 必要なロード）を組み立てる
 * - 複数ドメインにまたがる共通ルールは Rules に委譲する
 * - このUseCase固有の条件は UseCase 内に閉じる（必要に応じて private に隔離）
 */
class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
        private NotificationService $notificationService,
    ) {}

    /**
     * タスク完了の流れを組み立てる
     *
     * @param Task $task タスク
     * @param User $user ユーザー
     * @return Task
     */
    public function execute(Task $task, User $user): Task
    {
        // ========================================
        // 1. 検証（ビジネスルール / 制約）
        // ========================================

        // 横断ルール：このプロジェクトを操作できるメンバーか？
        // （Project×Membership など、複数UseCaseで再利用される前提ルール）
        $this->projectRules->ensureMember($task->project, $user);

        // UseCase固有ルール：このタスクは「完了」に遷移できる状態か？
        //（現時点では条件が少なくても、状態遷移は条件が増えやすいので隔離しておく）
        $this->ensureCanComplete($task);

        // ========================================
        // 2. 状態変更（UseCaseの責務）
        // ========================================
        $task->status = 'done';
        $task->save();

        // ========================================
        // 3. 通知送信
        // ========================================
        $this->notificationService->notify('task_completed', $user, $task->toArray());

        // ========================================
        // 4. 表示に必要なデータをロード（I/O都合）
        // ========================================
        $task->load('createdBy');

        return $task;
    }

    /**
     * タスクが「完了」に遷移可能か検証する（UseCase固有の制約）
     *
     * 置き場所の意図：
     * - これは「タスク完了」というシナリオに閉じた条件（現時点では他UseCaseで使わない想定）
     * - execute() の流れ（検証→更新）を読みやすく保つため、検証ロジックを private に隔離する
     * - private だからテスト不要、ではなく「UseCaseテストで完了条件をまとめて検証する」方針
     *   （将来この条件が複数UseCaseに広がったら Rules へ昇格を検討する）
     *
     * @param Task $task タスク
     * @return void
     * @throws ConflictException
     */
    private function ensureCanComplete(Task $task): void
    {
        if (!$task->isDoing()) {
            throw new ConflictException('作業中のタスクのみ完了できます');
        }
    }
}
