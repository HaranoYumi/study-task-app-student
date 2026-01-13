<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\Project\ProjectRules;
use App\Exceptions\ConflictException;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TaskResource;

/**
 * タスク完了UseCase（doing → done）
 *
 * 役割：
 * - 「完了」という業務シナリオ（検証 → 状態変更 → 必要なロード）を組み立てる
 * - 複数ドメインにまたがる共通ルールは Rules に委譲する
 * - このUseCase固有の条件は UseCase 内に閉じる（必要に応じて private に隔離）
 */
// CompleteTaskUseCase.php
class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    public function execute(Task $task, User $user)
    {
        try {
            // 権限チェック（Serviceに委譲）
            $result = $this->projectRules->ensureMember($task->project, $user);
            if ($result !== true) {
                return $result;  // response を返す
            }

            // 状態チェック（privateメソッド）
            $result = $this->ensureCanComplete($task);
            if ($result !== true) {
                return $result;  // response を返す
            }

            $task->update(['status' => 'done']);
            $task->load('createdBy');

            return new TaskResource($task);
        } catch (Exception $e) {
            Log::error('タスク完了エラー', ['error' => $e->getMessage()]);
            return response()->json(
                ['message' => 'エラーが発生しました'],
                $e->getCode() ?: 500
            );
        }
    }

    private function ensureCanComplete(Task $task)
    {
        if (!$task->isDoing()) {
            return response()->json([
                'message' => '作業中のタスクのみ完了できます',
            ], 409);
        }
        return true;
    }
}
