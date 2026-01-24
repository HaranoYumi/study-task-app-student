<?php
// tests/Feature/Api/TestControllerTest.php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
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

    // ============================================
    // 以下は日本語メソッド名の例
    // ============================================

    /**
     * Pingエンドポイントが正常なレスポンスを返すことを確認
     */
    #[Test]
    public function Pingエンドポイントが正常に動作する(): void
    {
        $response = $this->getJson('/api/test/ping');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'pong',
            'status' => 'ok',
        ]);
    }

    /**
     * Echoエンドポイントが送信したデータをそのまま返すことを確認
     */
    #[Test]
    public function Echoエンドポイントが送信したデータを返す(): void
    {
        $sendData = [
            'name' => 'テスト太郎',
            'age' => 25,
        ];

        $response = $this->postJson('/api/test/echo', $sendData);

        $response->assertStatus(200);
        $response->assertJson([
            'received' => $sendData,
        ]);
    }
}
