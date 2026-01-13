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
        // トランザクションなしattachでエラーが起きてもプロジェクトは作成されてエラーが起きる
        // プロジェクト作成
        $project = Project::create([
            'name' => $data['name'],
            'is_archived' => $data['is_archived'] ?? false,
        ]);

        // 作成者を自動的にオーナーとして追加
        // ✅意図的なタイポ（attah）でエラーが起きてもプロジェクトは作成されてエラーが起きる
        $project->users()->attah($user->id, [
            'role' => 'project_owner',
        ]);

        // リレーションをロード
        $project->load(['users']);

        return $project;



        // トランザクションありattachでエラーが起きてもプロジェクトは作成されてエラーが起きる
        // return DB::transaction(function () use ($data, $user) {
        //     // この中の処理は全部「1つの塊」として扱われる

        //     $project = Project::create([
        //         'name' => $data['name'],
        //         'is_archived' => $data['is_archived'] ?? false,
        //     ]);

        //     // ✅意図的なタイポ（attah）でエラーが起きてもプロジェクトは作成されてエラーが起きても
        //     // トランザクションによってロールバックされるてロールバックされて巻き戻されてプロジェクトは作成されない
        //     $project->users()->attach($user->id, [
        //         'role' => 'project_owner',
        //     ]);

        //     $project->load(['users']);

        //     return $project;
        // });
    }
}
