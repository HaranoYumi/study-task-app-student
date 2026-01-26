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
}
