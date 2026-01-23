<?php
// tests/Feature/Api/TestControllerTest.php

namespace Tests\Feature\Api;

use Tests\TestCase;

class TestControllerTest extends TestCase
{
    /**
     * /api/test/ping が正しいレスポンスを返す
     */
    public function test_ping_returns_pong(): void
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
     * /api/test/echo が送ったデータをそのまま返す
     */
    public function test_echo_returns_received_data(): void
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
            'received' => [
                'name' => 'テスト太郎',
                'age' => 25,
            ],
        ]);
    }
}
