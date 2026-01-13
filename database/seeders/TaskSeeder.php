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

        // ECサイトプロジェクトのタスク（5件）

        // オーナーが完了したタスク
        Task::create([
            'project_id' => $project1->id,
            'title' => '開発環境のセットアップ',
            'description' => '必要なツールと依存関係をインストールする',
            'status' => 'done',
            'created_by' => $owner->id,
        ]);

        // 管理者が完了したタスク
        Task::create([
            'project_id' => $project1->id,
            'title' => 'データベース設計',
            'description' => 'ER図とマイグレーションファイルを作成する',
            'status' => 'done',
            'created_by' => $admin->id,
        ]);

        // 管理者が作業中のタスク
        Task::create([
            'project_id' => $project1->id,
            'title' => '認証機能の実装',
            'description' => 'ログインと会員登録機能を追加する',
            'status' => 'doing',
            'created_by' => $admin->id,
        ]);

        // メンバーの未完了タスク（削除禁止チェック用）
        Task::create([
            'project_id' => $project1->id,
            'title' => 'APIドキュメントの作成',
            'description' => '全てのAPIエンドポイントをドキュメント化する',
            'status' => 'todo',
            'created_by' => $member->id,
        ]);

        Task::create([
            'project_id' => $project1->id,
            'title' => 'UIコンポーネントの開発',
            'description' => '再利用可能なVueコンポーネントを構築する',
            'status' => 'doing',
            'created_by' => $member->id,
        ]);

        // 追加タスク（バラエティ豊かに）
        Task::create([
            'project_id' => $project1->id,
            'title' => '商品一覧ページの実装',
            'description' => 'フィルター機能とページネーションを含む',
            'status' => 'todo',
            'created_by' => $owner->id,
        ]);

        Task::create([
            'project_id' => $project1->id,
            'title' => 'カート機能の実装',
            'description' => '商品の追加・削除・数量変更機能',
            'status' => 'todo',
            'created_by' => $admin->id,
        ]);
    }
}

