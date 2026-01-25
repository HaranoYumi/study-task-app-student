<?php
// database/factories/ProjectFactory.php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * モデルのデフォルト状態を定義
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),      // ランダムな名前
            'is_archived' => false,              // アーカイブされてない
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
