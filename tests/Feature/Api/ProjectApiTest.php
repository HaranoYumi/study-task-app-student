<?php
// tests/Feature/Api/ProjectApiTest.php

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 参加しているプロジェクト一覧を取得できる
     */
    public function test_参加しているプロジェクト一覧を取得できる(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // ユーザーを作成
        $user = User::factory()->create();

        // プロジェクトを作成
        $project = Project::factory()->create([
            'name' => 'テストプロジェクト',
        ]);

        // ユーザーをプロジェクトに参加させる（Membership を作成）
        Membership::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'role' => 'project_owner',
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'name' => 'テストプロジェクト',
        ]);
    }

    /**
     * 参加していないプロジェクトは一覧に表示されない
     */
    public function test_参加していないプロジェクトは一覧に表示されない(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // 自分（テスト対象のユーザー）を作成
        $user = User::factory()->create();

        // 別のユーザー（他人）を作成
        $otherUser = User::factory()->create([
            'name' => '他のユーザー',
        ]);

        // プロジェクトを作成
        $project = Project::factory()->create([
            'name' => '他人のプロジェクト',
        ]);

        // 別のユーザーをプロジェクトに参加させる
        // （自分は参加させない）
        Membership::factory()->create([
            'user_id' => $otherUser->id,  // ← 他のユーザーが参加
            'project_id' => $project->id,
            'role' => 'project_owner',
        ]);

        // ⚠️ $user の Membership は作らない = 自分は参加していない状態

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');  // ← 0件のはず！

        // 他人のプロジェクトが含まれていないことを確認
        $response->assertJsonMissing([
            'name' => '他人のプロジェクト',
        ]);
    }

    // tests/Feature/Api/ProjectApiTest.php に追加

    /**
     * 未認証の場合は401エラーになる
     */
    public function test_未認証の場合は401エラーになる(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // ログインしない状態なので、特に準備は不要

        // ============================================
        // 2. Act（実行）
        // ============================================
        // actingAs を使わない = ログインしてない状態
        $response = $this->getJson('/api/projects');

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(401);
        $response->assertJson([
            'message' => '認証が必要です',
        ]);
    }

    // tests/Feature/Api/ProjectApiTest.php に追加

    /**
     * project_member はプロジェクトを削除できない（403）
     */
    public function test_memberはプロジェクトを削除できない(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // プロジェクトのオーナー（他人）
        $owner = User::factory()->create([
            'name' => 'オーナー太郎',
        ]);

        // 普通のメンバー（自分）
        $member = User::factory()->create([
            'name' => 'メンバー花子',
        ]);

        // プロジェクトを作成
        $project = Project::factory()->create([
            'name' => 'チームプロジェクト',
        ]);

        // オーナーとしてプロジェクトに参加
        Membership::factory()->create([
            'user_id' => $owner->id,
            'project_id' => $project->id,
            'role' => 'project_owner',  // ← オーナー
        ]);

        // 普通のメンバーとしてプロジェクトに参加
        Membership::factory()->create([
            'user_id' => $member->id,
            'project_id' => $project->id,
            'role' => 'project_member',  // ← 一般メンバー
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        // 一般メンバーがプロジェクトを削除しようとする
        $response = $this->actingAs($member)
            ->deleteJson("/api/projects/{$project->id}");

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(403);

        // プロジェクトが削除されていないことも確認
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'チームプロジェクト',
        ]);
    }
}
