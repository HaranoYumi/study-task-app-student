<?php

namespace App\UseCases\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * プロジェクト作成UseCase
 */
class CreateProjectUseCase
{
    /**
     * プロジェクト作成の流れを組み立てる
     * 
     * @param array $data プロジェクト作成データ（name, is_archived）
     * @param User $user 作成者
     * @return Project
     */
    public function execute(array $data, User $user): Project
    {
        return DB::transaction(function () use ($data, $user) {
            try {
                // プロジェクト作成
                $project = Project::create([
                    'name' => $data['name'],
                    'is_archived' => $data['is_archived'] ?? false,
                ]);

                // 作成者を自動的にオーナーとして追加
                $project->users()->attach($user->id, [
                    'role' => 'project_owner',
                ]);

                // リレーションをロード
                $project->load(['users']);

                return $project;
            } catch (\Exception $e) {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }
        });
    }
}
