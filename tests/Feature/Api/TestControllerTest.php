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
    public function test_Pingエンドポイントが正常に動作する(): void
    {
        // API にリクエストを送る
        $response = $this->getJson('/api/test/ping');

        // ステータスコードが 200 か確認
        $response->assertStatus(200);

        // JSON の中身が正しいか確認
        $response->assertJson([
            'message' => 'pong',
            'status' => 'ok',
        ]);
    }
}
