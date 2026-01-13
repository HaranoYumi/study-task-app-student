<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('email', 'owner@example.com')->first();
        $admin = User::where('email', 'admin@example.com')->first();
        $member = User::where('email', 'member@example.com')->first();

        $project1 = Project::where('name', 'ECサイトリニューアルプロジェクト')->first();

        // ========================================
        // ECサイトプロジェクトのタスク
        // ========================================

        // 【タスクID: 1】完了済みタスク（409テスト用）
        // - status: done
        // - 用途: 完了済みタスクは編集・削除できないことを確認
        // - テスト: 
        //   - PUT /api/tasks/1 で409が返る（編集不可）
        //   - DELETE /api/tasks/1 で409が返る（削除不可）
        Task::create([
            'project_id' => $project1->id,
            'title' => '開発環境のセットアップ',
            'description' => '必要なツールと依存関係をインストールする',
            'status' => 'done',
            'created_by' => $owner->id,
        ]);

        // 【タスクID: 2】完了済みタスク（409テスト用）
        // - status: done
        // - 用途: 完了済みタスクに対する不正な操作でエラーになることを確認
        // - テスト: 
        //   - POST /api/tasks/2/start で409が返る（完了済みは開始不可）
        //   - DELETE /api/tasks/2 で409が返る（削除不可）
        Task::create([
            'project_id' => $project1->id,
            'title' => 'データベース設計',
            'description' => 'ER図とマイグレーションファイルを作成する',
            'status' => 'done',
            'created_by' => $admin->id,
        ]);

        // 【タスクID: 3】作業中タスク（409テスト用）
        // - status: doing
        // - 用途: 作業中タスクを開始しようとすると409
        // - テスト: POST /api/tasks/3/start で409が返ることを確認
        Task::create([
            'project_id' => $project1->id,
            'title' => '認証機能の実装',
            'description' => 'ログインと会員登録機能を追加する',
            'status' => 'doing',
            'created_by' => $admin->id,
        ]);

        // 【タスクID: 4】未着手タスク（409テスト用）
        // - status: todo
        // - 用途: 未着手タスクをいきなり完了しようとすると409
        // - テスト: POST /api/tasks/4/complete で409が返ることを確認
        Task::create([
            'project_id' => $project1->id,
            'title' => 'APIドキュメントの作成',
            'description' => '全てのAPIエンドポイントをドキュメント化する',
            'status' => 'todo',
            'created_by' => $member->id,
        ]);

        // 【タスクID: 5】作業中タスク（正常系テスト用）
        // - status: doing
        // - 用途: 正常に完了できることを確認
        // - テスト: POST /api/tasks/5/complete で200が返ることを確認
        Task::create([
            'project_id' => $project1->id,
            'title' => 'UIコンポーネントの開発',
            'description' => '再利用可能なVueコンポーネントを構築する',
            'status' => 'doing',
            'created_by' => $member->id,
        ]);

        // 【タスクID: 6】未着手タスク（正常系テスト用）
        // - status: todo
        // - 用途: 正常に開始できることを確認
        // - テスト: POST /api/tasks/6/start で200が返ることを確認
        Task::create([
            'project_id' => $project1->id,
            'title' => '商品一覧ページの実装',
            'description' => 'フィルター機能とページネーションを含む',
            'status' => 'todo',
            'created_by' => $owner->id,
        ]);

        // 【タスクID: 7】未着手タスク（通常編集用）
        // - status: todo
        // - 用途: 通常の編集・削除テスト
        Task::create([
            'project_id' => $project1->id,
            'title' => 'カート機能の実装',
            'description' => '商品の追加・削除・数量変更機能',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);
    }
}

