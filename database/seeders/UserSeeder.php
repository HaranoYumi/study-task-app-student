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
        // オーナーユーザー
        User::factory()->create([
            'name' => '山田太郎',
            'email' => 'owner@example.com',
        ]);

        // 管理者ユーザー
        User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'admin@example.com',
        ]);

        // メンバーユーザー
        User::factory()->create([
            'name' => '鈴木一郎',
            'email' => 'member@example.com',
        ]);
    }
}


