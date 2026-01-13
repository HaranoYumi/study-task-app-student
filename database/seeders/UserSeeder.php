<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // オーナーユーザー（ID: 1）
        // - ECサイトプロジェクトのオーナー
        // - メンバー追加/削除が可能
        User::factory()->create([
            'name' => '山田太郎',
            'email' => 'owner@example.com',
        ]);

        // 管理者ユーザー（ID: 2）
        // - ECサイトプロジェクトの管理者
        // - メンバー追加/削除が可能
        User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'admin@example.com',
        ]);

        // 一般メンバーユーザー（ID: 3）
        // - ECサイトプロジェクトの一般メンバー
        // - メンバー追加/削除は不可（403テスト用）
        User::factory()->create([
            'name' => '鈴木一郎',
            'email' => 'member@example.com',
        ]);

        // 非メンバーユーザー（ID: 4）
        // - どのプロジェクトにも所属していない
        // - プロジェクトへのアクセスは不可（403テスト用）
        User::factory()->create([
            'name' => '田中美咲',
            'email' => 'outsider@example.com',
        ]);
    }
}


