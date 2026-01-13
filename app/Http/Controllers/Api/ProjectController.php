<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use App\UseCases\Project\GetProjectsUseCase;
use App\UseCases\Project\CreateProjectUseCase;
use App\UseCases\Project\GetProjectUseCase;
use App\UseCases\Project\UpdateProjectUseCase;
use App\UseCases\Project\DeleteProjectUseCase;


class ProjectController extends ApiController
{
    public function __construct(
        private GetProjectsUseCase $getProjectsUseCase,
        private CreateProjectUseCase $createProjectUseCase,
        private GetProjectUseCase $getProjectUseCase,
        private UpdateProjectUseCase $updateProjectUseCase,
        private DeleteProjectUseCase $deleteProjectUseCase,

    ) {}

    /**
     * 自分が所属しているプロジェクト一覧を返す
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->getProjectsUseCase->execute($request->user());

        return ProjectResource::collection($projects);
    }

    /**
     * プロジェクト新規作成
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();

        // プロジェクト作成
        $project = $this->createProjectUseCase->execute(
            user: $request->user(),
            name: $data['name'],
            isArchived : (bool) ($data['is_archived'] ?? false),
        );

        return (new ProjectResource($project))
            ->additional(['message' => 'プロジェクトを作成しました'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * プロジェクト詳細を返す
     */
    public function show(Request $request, Project $project): ProjectResource|JsonResponse
    {
        // プロジェクト詳細を取得
        $project = $this->getProjectUseCase->execute(
            user: $request->user(),
            project : $project,
        );

        return new ProjectResource($project);
    }

    /**
     * プロジェクト更新
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource|JsonResponse
    {
        $data = $request->validated();

        // 自分がオーナーまたは管理者かチェック
        $project = $this->updateProjectUseCase->execute(
            user: $request->user(),
            project: $project,
            data: $data,
        );

        return (new ProjectResource($project))
            ->additional(['message' => 'プロジェクトを更新しました']);
    }

    /**
     * プロジェクト削除
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        // プロジェクト削除
        $project = $this->deleteProjectUseCase->execute(
            user: $request->user(),
            project: $project,
        );

        return response()->json([
            'message' => 'プロジェクトを削除しました',
        ]);
    }
}
