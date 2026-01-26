<?php
// database/factories/MembershipFactory.php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MembershipFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),        // User を自動生成
            'project_id' => Project::factory(),  // Project を自動生成
            'role' => 'project_member',          // デフォルトは一般メンバー
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
