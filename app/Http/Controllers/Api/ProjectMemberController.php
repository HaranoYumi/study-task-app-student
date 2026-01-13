<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\AddMemberRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

use App\UseCases\Membership\GetMembershipUseCase;
use App\UseCases\Membership\AddMembershipUseCase;
use App\UseCases\Membership\DeleteMembershipUseCase;

class ProjectMemberController extends Controller
{
    public function __construct(
        private GetMembershipUseCase $getMembershipUseCase,
        private AddMembershipUseCase $addMembershipUseCase,
        private DeleteMembershipUseCase $deleteMembershipUseCase,
    ) {}
    /**
     * プロジェクトのメンバー一覧を取得
     */
    public function index(Request $request, Project $project): AnonymousResourceCollection|JsonResponse
    {
        $members = $this->getMembershipUseCase->execute(
            user: $request->user(),
            project: $project,
        );

        return ProjectMemberResource::collection($members);
    }

    /**
     * プロジェクトにメンバーを追加
     */
    public function store(AddMemberRequest $request, Project $project): JsonResponse
    {
        $validated = $request->validated();

        $user =$this->addMembershipUseCase->execute(
            user: $request->user(),
            project: $project,
            userId: $validated['user_id'],
            role: $validated['role'] ?? 'project_member',
        );

        return response()->json([
            'message' => 'メンバーを追加しました',
            'membership' => new ProjectMemberResource($user),
        ], 201);
    }

    /**
     * プロジェクトからメンバーを削除（users()リレーションを使用）
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->deleteMembershipUseCase->execute(
            user: $request->user(),
            project: $project,
        );

        return response()->json([
            'message' => 'メンバーを削除しました',
        ]);
    }
}
