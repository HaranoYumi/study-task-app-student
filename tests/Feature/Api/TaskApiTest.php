<?php
// tests/Feature/Api/TaskApiTest.php（リファクタリング後）

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    /**
     * 各テストの前に実行される
     */
    protected function setUp(): void
    {
        parent::setUp();  // ← これは必ず書く！

        // 共通のテストデータを準備
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();

        Membership::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'role' => 'project_member',
        ]);
    }

    /**
     * タスクを作成できる
     */
    public function test_タスクを作成できる(): void
    {
        // Arrange は setUp で完了してるので、すぐ Act へ！

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => '新しいタスク',
                'description' => 'タスクの説明です',
            ]);

        // Assert
        $response->assertStatus(201);
        $response->assertJson([
            'data' => [
                'title' => '新しいタスク',
                'status' => 'todo',
            ]
        ]);
    }

    /**
     * todo ステータスのタスクを開始できる
     */
    public function test_todoステータスのタスクを開始できる(): void
    {
        // Arrange（タスクだけ追加で作成）
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'status' => 'todo',
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/start");

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'doing',
            ]
        ]);
    }

    // tests/Feature/Api/TaskApiTest.php に追加

    /**
     * doing ステータスのタスクを完了できる
     *
     * 正常系：doing → done への状態遷移
     */
    public function test_doingステータスのタスクを完了できる(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'status' => 'doing',
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/complete");

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'status' => 'done',
            ]
        ]);

        // DB にも反映されていることを確認
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);
    }

    // tests/Feature/Api/TaskApiTest.php に追加

    /**
     * タイトルが空の場合は422エラーになる
     */
    public function test_タイトルが空の場合は422エラーになる(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // setUp で作成した user, project を使用

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->id}/tasks", [
                'title' => '',  // ← 空っぽ！
                'description' => 'タスクの説明',
            ]);

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);

        // タスクが作成されていないことも確認
        $this->assertDatabaseMissing('tasks', [
            'description' => 'タスクの説明',
        ]);
    }

    // tests/Feature/Api/TaskApiTest.php に追加

    /**
     * 参加していないプロジェクトのタスクにはアクセスできない（403）
     */
    public function test_参加していないプロジェクトのタスクにはアクセスできない(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // 他人のプロジェクトとタスクを作成
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create();

        Membership::factory()->create([
            'user_id' => $otherUser->id,
            'project_id' => $otherProject->id,
            'role' => 'project_owner',
        ]);

        $otherTask = Task::factory()->create([
            'project_id' => $otherProject->id,
            'created_by' => $otherUser->id,
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        // 参加していないプロジェクトのタスクにアクセス
        $response = $this->actingAs($this->user)
            ->getJson("/api/tasks/{$otherTask->id}");

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(403);
    }

    // tests/Feature/Api/TaskApiTest.php に追加

    /**
     * 存在しないタスクにアクセスすると404エラーになる
     */
    public function test_存在しないタスクにアクセスすると404エラーになる(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // setUp で作成した user を使用
        // タスクは作成しない

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks/99999');  // ← 存在しないID

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(404);
    }

    // tests/Feature/Api/TaskApiTest.php に追加

    /**
     * doing ステータスのタスクは開始できない（409）
     */
    public function test_doingステータスのタスクは開始できない(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        // すでに doing 状態のタスクを作成
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'status' => 'doing',  // ← すでに作業中！
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        // 開始しようとする（でも、もう doing だから失敗するはず）
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/start");

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(409);
        $response->assertJson([
            'message' => '未着手のタスクのみ開始できます',
        ]);

        // ステータスが変わっていないことも確認
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'doing',  // ← doing のまま
        ]);
    }

    /**
     * todo ステータスのタスクは完了できない（409）
     *
     * 異常系：doing を経由せずに完了しようとした場合409エラーになる
     */
    public function test_todoステータスのタスクは完了できない(): void
    {
        // ============================================
        // 1. Arrange（準備）
        // ============================================
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'created_by' => $this->user->id,
            'status' => 'todo',  // ← まだ着手してない
        ]);

        // ============================================
        // 2. Act（実行）
        // ============================================
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/complete");

        // ============================================
        // 3. Assert（検証）
        // ============================================
        $response->assertStatus(409);
        $response->assertJson([
            'message' => '作業中のタスクのみ完了できます',
        ]);

        // ステータスが変わっていないことも確認
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'todo',  // ← todo のまま
        ]);
    }
}
