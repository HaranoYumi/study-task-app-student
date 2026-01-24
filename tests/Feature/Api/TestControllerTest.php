<?php
// tests/Feature/Api/TestControllerTest.php

namespace Tests\Feature\Api;

use Tests\TestCase;

class TestControllerTest extends TestCase
{
    /**
     * Pingエンドポイントが正常なレスポンスを返すことを確認
     */
    public function test_Pingエンドポイントが正常に動作する(): void
    {
        // 1. Arrange（準備）
        // → 認証不要なので、特になし

        // 2. Act（実行）
        $response = $this->getJson('/api/test/ping');

        // 3. Assert（検証）
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'pong',
            'status' => 'ok',
        ]);
    }

    /**
     * Echoエンドポイントが送信したデータをそのまま返すことを確認
     */
    public function test_Echoエンドポイントが送信したデータを返す(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        $sendData = [
            'name' => 'テスト太郎',
            'age' => 25,
        ];

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->postJson('/api/test/echo', $sendData);

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(200);
        $response->assertJson([
            'received' => $sendData,
        ]);
    }
}
