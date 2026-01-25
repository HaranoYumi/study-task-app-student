<?php
// tests/Feature/Api/UserTest.php（正しい例）

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;  // ← これでDBが毎回リセット！

    /**
     * 認証済みユーザーの情報を取得できる
     */
    public function test_認証済みユーザーの情報を取得できる(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // Factory でユーザーを作成（1行！）
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        // actingAs でログイン状態にしてリクエスト
        $response = $this->actingAs($user)
            ->getJson('/api/user');

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(200);
        $response->assertJson([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
        ]);
    }
}
