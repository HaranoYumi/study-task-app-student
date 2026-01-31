<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 通知サービス
 * 
 * 実際にはログ出力のみ（メール/Slack送信の模擬）
 * テスト時は Mock に差し替えて使用する
 */
class LogNotificationService
{
    /**
     * 通知を送信する
     *
     * @param string $type    通知タイプ（例: 'task_completed'）
     * @param User   $actor   操作を実行したユーザー
     * @param array  $payload 通知に含めるデータ
     * @return void
     */
    public function notify(string $type, User $actor, array $payload): void
    {
        // 実際のメール送信の代わりにログ出力
        // （本番ではここで Mail::send() などを呼ぶ）
        Log::info('[Notification]', [
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'payload' => $payload,
        ]);
    }
}
