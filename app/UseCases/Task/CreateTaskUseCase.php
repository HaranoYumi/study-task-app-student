<?php

namespace App\UseCases\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Notification\LogNotificationService;
use App\Services\Project\ProjectRules;

/**
 * タスク作成UseCase
 * 
 * 【UseCaseの責務】
 * - 「誰を」「どの順番で」呼ぶか決める司令塔
 * - 自分ではビジネスルールを判断しない
 * - Rulesに判断を任せる
 */
class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
        private LogNotificationService $notificationService,
    ) {}

    /**
     * タスク作成の流れを組み立てる
     * 
     * @param array $data タスク作成データ（title, description）
     * @param Project $project プロジェクト
     * @param User $user 作成者
     * @return Task
     */
    public function execute(array $data, Project $project, User $user): Task
    {
        // ========================================
        // 1. ビジネスルール検証（システム全体ルール）
        //    → UseCaseは「呼ぶだけ」で判断しない
        // ========================================
        $this->projectRules->ensureMember($project, $user);

        // ========================================
        // 2. データ作成（Eloquent直接）
        // ========================================
        $task = Task::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'created_by' => $user->id,
        ]);

        // ========================================
        // 3. 通知送信
        // ========================================
        $this->notificationService->notify('task_created', $user, $task->toArray());

        // ========================================
        // 4. リレーションロード
        // ========================================
        $task->load('createdBy');

        return $task;
    }
}
