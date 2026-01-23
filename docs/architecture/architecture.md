# 📋 バックエンドアーキテクチャ設計書

**プロジェクト名**: study-task-app  
**フレームワーク**: Laravel 11.x  
**アーキテクチャ**: レイヤードアーキテクチャ（クリーンアーキテクチャ風）  
**作成日**: 2026年1月21日

---

## 📑 目次

1. [アーキテクチャ概要](#-1-アーキテクチャ概要)
   - 1.1 アーキテクチャ図

2. [各層の詳細設計](#️-2-各層の詳細設計)
   - 2.1 ルーティング層
   - 2.2 コントローラー層
   - 2.3 バリデーション層
   - 2.4 UseCase層
     - 2.4.1 設計思想
     - 2.4.2 トランザクション管理の例
     - 2.4.3 UseCaseの単位と判断基準
     - 2.4.4 ルールの配置場所と使い分け（本プロジェクトでの実践）
     - 2.4.5 共通のルールとビジネスロジックの分け方
   - 2.5 Service層
     - 2.5.1 ルールの配置場所の判断基準
     - 2.5.2 判定メソッドと保証メソッドのペア
   - 2.6 Model層
   - 2.7 Resource層
   - 2.8 Response層
   - 2.9 例外処理層
   - 2.10 ルールと共通処理の配置判断
     - 2.10.1 配置場所の決定フロー
     - 2.10.2 RulesとService/Supportの違い
     - 2.10.3 ファイル分割の判断基準
     - 2.10.4 配置場所のまとめ表
   - 2.11 命名規則
     - 2.11.1 UseCase
     - 2.11.2 Rules / Service
     - 2.11.3 メソッド
   - 2.12 ディレクトリ構成の全体像
     - 2.12.1 完全なディレクトリ構成
     - 2.12.2 本プロジェクトの特徴
   - 2.13 依存関係のルール
     - 2.13.1 依存関係の方向
     - 2.13.2 許可される依存関係
     - 2.13.3 禁止される依存関係
     - 2.13.4 依存関係の原則

3. [データベース設計](#-3-データベース設計)
   - 3.1 ER図
   - 3.2 テーブル詳細

4. [データフロー実例](#-4-データフロー実例)
   - 4.1 プロジェクト作成のフロー

5. [アーキテクチャの評価](#-5-アーキテクチャの評価)
   - 5.1 7つの評価観点
   - 5.2 良い点
   - 5.3 改善すべき点

6. [具体的な改善案](#-6-具体的な改善案)

7. [結論とおすすめ修正方針](#-7-結論とおすすめ修正方針)

8. [参考資料](#-8-参考資料)

9. [本プロジェクトの設計方針（Fat Controller → UseCase + Rules構成）](#-9-本プロジェクトの設計方針fat-controller--usecase--rules構成)
   - 9.1 採用している設計パターン
   - 9.2 ルール配置の3段階戦略
   - 9.3 本プロジェクトでの具体的な実装例
   - 9.4 本プロジェクトの設計の特徴

10. [リファクタリングのガイドライン](#-10-リファクタリングのガイドライン)
   - 10.1 Fat Controllerのリファクタリング手順
   - 10.2 リファクタリングのコツ
   - 10.3 チェックリスト

11. [よくある質問（FAQ）](#-11-よくある質問faq)

---

## 🎯 1. アーキテクチャ概要

本プロジェクトは、**責務分離**と**保守性**を重視した**レイヤードアーキテクチャ**を採用しています。

### 1.1 アーキテクチャ図

```
┌─────────────────────────────────────────────────────────┐
│                    クライアント（Vue.js）                │
└────────────────────────┬────────────────────────────────┘
                         │ HTTP Request (JSON)
┌────────────────────────┼────────────────────────────────┐
│                        │     バックエンド（Laravel）    │
│                        ▼                                 │
│  ┌──────────────────────────────────────────────────┐  │
│  │  ① ルーティング層 (routes/api.php)              │  │
│  │     - 認証・認可（Sanctum Middleware）          │  │
│  │     - リクエストの受付                          │  │
│  └────────────────┬─────────────────────────────────┘  │
│                   │                                      │
│  ┌────────────────▼─────────────────────────────────┐  │
│  │  ② コントローラー層 (Http/Controllers/Api/)     │  │
│  │     - リクエストの受け取り                       │  │
│  │     - UseCaseの呼び出し                          │  │
│  │     - レスポンスの返却                           │  │
│  └────────────────┬─────────────────────────────────┘  │
│                   │                                      │
│  ┌────────────────▼─────────────────────────────────┐  │
│  │  ③ バリデーション層 (Http/Requests/)            │  │
│  │     - 入力値の検証                               │  │
│  │     - データ整形                                 │  │
│  └──────────────────────────────────────────────────┘  │
│                   │                                      │
│  ┌────────────────▼─────────────────────────────────┐  │
│  │  ④ UseCase層 (UseCases/)                        │  │
│  │     - ビジネスロジックの組み立て                 │  │
│  │     - トランザクション管理                       │  │
│  │     - 処理フローの制御                           │  │
│  └────┬─────────────────────────────────┬───────────┘  │
│       │                                 │              │
│  ┌────▼──────────────────┐   ┌──────────▼──────────┐  │
│  │ ⑤ Service層           │   │ ⑥ Model層           │  │
│  │  (Services/)          │   │  (Models/)          │  │
│  │  - ビジネスルール判定 │   │  - データアクセス   │  │
│  │  - 権限チェック       │   │  - リレーション定義 │  │
│  │  - ドメイン知識       │   │  - 状態判定         │  │
│  └───────────────────────┘   └─────────────────────┘  │
│                   │                                      │
│  ┌────────────────▼─────────────────────────────────┐  │
│  │  ⑦ Resource層 (Http/Resources/)                 │  │
│  │     - JSONレスポンスの整形                       │  │
│  │     - データ変換                                 │  │
│  └────────────────┬─────────────────────────────────┘  │
│                   │                                      │
│  ┌────────────────▼─────────────────────────────────┐  │
│  │  ⑧ Response層 (Http/Responses/)                 │  │
│  │     - 統一レスポンス形式                         │  │
│  │     - エラーハンドリング                         │  │
│  └──────────────────────────────────────────────────┘  │
│                                                          │
│  ┌──────────────────────────────────────────────────┐  │
│  │  ⑨ 例外処理層 (Exceptions/)                      │  │
│  │     - グローバル例外ハンドリング                 │  │
│  │     - カスタム例外定義                           │  │
│  │     - Sentry連携                                 │  │
│  └──────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
                         │
                         ▼
               ┌──────────────────┐
               │   データベース   │
               │    (MySQL 8.4)   │
               └──────────────────┘
```

---

## 🏗️ 2. 各層の詳細設計

### 2.1 ルーティング層 (`routes/api.php`)

**責務**: リクエストを適切なコントローラーへルーティング

```php:1:52:routes/api.php
<?php

use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 認証済みユーザー情報を取得
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/test', function () {
    throw new \Exception("テストエラー");
});

Route::get('/test/ping', [TestController::class, 'ping']);
Route::post('/test/echo', [TestController::class, 'echo']);

// Projects
Route::get('/test/projects', [ProjectController::class, 'index']);

// 認証が必要なAPI
Route::middleware(['auth:sanctum'])->group(function () {
    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/dropdown', [UserController::class, 'dropdown']);

    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);

    // Tasks
    Route::get('/projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('/projects/{project}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);

    // Members
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
    Route::delete('/projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy']);
});
```

**特徴**:
- `auth:sanctum` ミドルウェアによる認証
- RESTful API設計
- リソースネスト（`/projects/{project}/tasks`）

**Laravelとの違い**:
- 標準Laravel: Webルート中心
- 本プロジェクト: **API専用ルート**で統一、SPA用設計

---

### 2.2 コントローラー層 (`app/Http/Controllers/Api/`)

**責務**: リクエストの受付とUseCaseの呼び出し

#### 2.2.1 基底コントローラー

```php:1:16:app/Http/Controllers/Api/ApiController.php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;

class ApiController extends Controller
{
    protected function response(): ApiResponse
    {
        // 研修用に必要に応じてコメントアウト
        return new ApiResponse();
    }
}
```

**役割**:
- 全APIコントローラーの基底クラス
- 統一レスポンスヘルパーの提供

#### 2.2.2 具体的なコントローラー例

```php:19:89:app/Http/Controllers/Api/ProjectController.php
class ProjectController extends ApiController
{
    public function __construct(
        private CreateProjectUseCase $createProjectUseCase,
        private GetProjectUseCase $getProjectUseCase,
        private UpdateProjectUseCase $updateProjectUseCase,
        private DeleteProjectUseCase $deleteProjectUseCase,
        private GetProjectsUseCase $getProjectsUseCase,
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
        $project = $this->createProjectUseCase->execute(
            $request->validated(),
            $request->user()
        );

        return $this->response()->createdWithResource(
            new ProjectResource($project),
            'プロジェクトを作成しました'
        );
    }

    /**
     * プロジェクト詳細を返す
     */
    public function show(Request $request, Project $project): ProjectResource
    {
        $project = $this->getProjectUseCase->execute($project, $request->user());
        return new ProjectResource($project);
    }

    /**
     * プロジェクト更新
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->updateProjectUseCase->execute(
            $project,
            $request->validated(),
            $request->user()
        );

        return $this->response()->successWithResource(
            new ProjectResource($project),
            'プロジェクトを更新しました'
        );
    }

    /**
     * プロジェクト削除
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->deleteProjectUseCase->execute($project, $request->user());

        return $this->response()->success(null, 'プロジェクトを削除しました');
    }
}
```

**設計の特徴**:
1. **コンストラクタインジェクション**: UseCaseをDI
2. **薄いコントローラー**: ビジネスロジックを持たない
3. **Resourceパターン**: データ変換を委譲
4. **統一レスポンス**: ApiResponseクラスを使用

**Laravelとの比較**:

| 標準Laravel | 本プロジェクト |
|:---|:---|
| Controllerにビジネスロジック記載 | **UseCaseに委譲** |
| 直接Modelを操作 | **UseCase経由でModel操作** |
| 個別のレスポンス形式 | **統一されたApiResponse** |

---

### 2.3 バリデーション層 (`app/Http/Requests/`)

**責務**: 入力値の検証

```php:1:32:app/Http/Requests/Project/StoreProjectRequest.php
<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * 認可チェックはUseCaseで行うため、ここでは認証のみ
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_archived' => 'boolean',
        ];
    }
}
```

**設計ポイント**:
- 認可（Authorization）はUseCaseで実施
- バリデーションのみに責務を限定

**Laravelとの比較**:
- 標準Laravel: `authorize()`で認可も実施
- 本プロジェクト: **UseCase層で認可を実施**（ビジネスロジックと一緒に管理）

---

### 2.4 UseCase層 (`app/UseCases/`)

**責務**: ビジネスロジックの組み立て（司令塔）

#### 2.4.1 設計思想

```php:1:59:app/UseCases/Task/CreateTaskUseCase.php
<?php

namespace App\UseCases\Task;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Project\ProjectRules;

/**
 * タスク作成UseCase
 * 
 * 【UseCaseの責務】
 * - 「誰を」「どの順番で」呼ぶか決める司令塔
 * - 自分ではビジネスルールを判断しない
 * - Rulesに判断を任せる
 */
class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    /**
     * タスク作成の流れを組み立てる
     * 
     * @param array $data タスク作成データ（title, description）
     * @param Project $project プロジェクト
     * @param User $user 作成者
     * @return Task
     */
    public function execute(array $data, Project $project, User $user): Task
    {
        // ========================================
        // 1. ビジネスルール検証（システム全体ルール）
        //    → UseCaseは「呼ぶだけ」で判断しない
        // ========================================
        $this->projectRules->ensureMember($project, $user);

        // ========================================
        // 2. データ作成（Eloquent直接）
        // ========================================
        $task = Task::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'created_by' => $user->id,
        ]);

        // ========================================
        // 3. リレーションロード
        // ========================================
        $task->load('createdBy');

        return $task;
    }
}
```

**UseCase層の原則**:

1. **処理フローの明確化**: コメントで3ステップに分離
2. **ビジネスルールの委譲**: ServiceのRulesクラスで判定
3. **トランザクション管理**: 必要に応じてDB::transaction使用
4. **リレーションの事前ロード**: N+1問題の防止

#### 2.4.3 UseCaseの単位と判断基準

**1つのUseCaseファイル = 1つのユーザー操作**

```
例：
- プロジェクトを作成する    → CreateProjectUseCase.php
- プロジェクト一覧を見る    → GetProjectsUseCase.php
- プロジェクトを更新する    → UpdateProjectUseCase.php
- プロジェクトを削除する    → DeleteProjectUseCase.php
- タスクを開始する          → StartTaskUseCase.php
- タスクを完了する          → CompleteTaskUseCase.php
```

**UseCaseが持つべき責務**:

```
┌──────────────────────────────────────────────────────┐
│ UseCaseの責務                                        │
├──────────────────────────────────────────────────────┤
│ ✅ 「誰を」「どの順番で」呼ぶか決める（司令塔）      │
│ ✅ トランザクションの制御                            │
│ ✅ リレーションのロード指示                          │
│ ✅ データの組み立て                                  │
├──────────────────────────────────────────────────────┤
│ ❌ ビジネスルールの判定（Service/Rulesに委譲）       │
│ ❌ 複雑な計算処理（Service/Supportに委譲）           │
│ ❌ HTTPレスポンスの整形（Controllerで実施）          │
└──────────────────────────────────────────────────────┘
```

**privateメソッドの使い方**:

```php
class AddMemberUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    public function execute(Project $project, array $data, User $currentUser): User
    {
        // システム全体ルール（他ドメインでも使う）
        $this->projectRules->ensureNotMember($project, $userId);

        // UseCase固有ルール（このUseCaseでしか使わない）
        $this->ensureNotSelf($userId, $currentUser->id);

        // メンバー追加処理...
    }

    /**
     * 自分自身を追加しようとしていないか検証
     * 
     * 【なぜprivateメソッドに置くか】
     * - AddMemberUseCaseでしか使わない
     * - 他のUseCaseで使う予定がない
     * - ロジックが単純
     */
    private function ensureNotSelf(int $targetUserId, int $currentUserId): void
    {
        if ($targetUserId === $currentUserId) {
            throw new ConflictException('自分自身を追加することはできません');
        }
    }
}
```

**本プロジェクトのUseCase一覧**:

| ドメイン | UseCase | 責務 |
|:---|:---|:---|
| **Project** | CreateProjectUseCase | プロジェクト作成 + オーナー登録 |
| | GetProjectsUseCase | 所属プロジェクト一覧取得 |
| | GetProjectUseCase | プロジェクト詳細取得 |
| | UpdateProjectUseCase | プロジェクト更新（権限チェック含む） |
| | DeleteProjectUseCase | プロジェクト削除（権限チェック含む） |
| **Task** | CreateTaskUseCase | タスク作成（メンバーチェック含む） |
| | GetTasksUseCase | タスク一覧取得 |
| | GetTaskUseCase | タスク詳細取得 |
| | UpdateTaskUseCase | タスク更新 |
| | DeleteTaskUseCase | タスク削除 |
| | StartTaskUseCase | タスク開始（状態遷移） |
| | CompleteTaskUseCase | タスク完了（状態遷移） |
| **Membership** | AddMemberUseCase | メンバー追加（重複チェック含む） |
| | GetMembersUseCase | メンバー一覧取得 |
| | RemoveMemberUseCase | メンバー削除 |

#### 2.4.4 ルールの配置場所と使い分け（本プロジェクトでの実践）

本プロジェクトでは、**Fat Controller → UseCase + Rules構成**のリファクタリング設計を採用しています。ルールの配置場所は「誰が使うか」で決定します。

**配置場所の3段階**:

```
┌─────────────────────────────────────────────────────────────┐
│ レベル1: privateメソッド（UseCase内）                        │
│   配置場所: UseCase内のprivateメソッド                       │
│   使用範囲: 1箇所（そのUseCaseのみ）                         │
│   目的: UseCase固有のロジック                                │
├─────────────────────────────────────────────────────────────┤
│ レベル2: ドメイン内Rules（未実装）                           │
│   配置場所: UseCases/{Domain}/Rules/                        │
│   使用範囲: 同じドメイン内の複数UseCase                      │
│   目的: ドメイン内で共有するルール                           │
├─────────────────────────────────────────────────────────────┤
│ レベル3: システム全体Rules（実装済み）                       │
│   配置場所: Services/{Domain}/                              │
│   使用範囲: 複数ドメイン（システム全体）                     │
│   目的: システム全体で共有するルール                         │
└─────────────────────────────────────────────────────────────┘
```

**本プロジェクトでの具体例**:

##### 🔹 レベル1: privateメソッド（UseCase固有）

```php
// app/UseCases/Membership/AddMemberUseCase.php

class AddMemberUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // レベル3: システム全体
    ) {}

    public function execute(Project $project, array $data, User $currentUser): User
    {
        $userId = $data['user_id'];
        $role = $data['role'] ?? 'project_member';

        // ========================================
        // レベル3: システム全体ルール（Services/Project/ProjectRules.php）
        // ========================================
        // 理由: Task作成時、Member取得時など複数ドメインで使う
        $this->projectRules->ensureNotMember($project, $userId);

        // ========================================
        // レベル1: UseCase固有ルール（privateメソッド）
        // ========================================
        // 理由: AddMemberUseCaseでしか使わない
        $this->ensureNotSelf($userId, $currentUser->id);

        // メンバー追加処理...
        $project->users()->attach($userId, ['role' => $role]);

        return $project->users()
            ->withPivot('id', 'role')
            ->find($userId);
    }

    /**
     * 【レベル1: privateメソッド】
     * 
     * 配置理由:
     * - AddMemberUseCaseでしか使わない
     * - RemoveMemberUseCaseでは使わない（削除は自分でもOK）
     * - ロジックが単純（1つのif文のみ）
     */
    private function ensureNotSelf(int $targetUserId, int $currentUserId): void
    {
        if ($targetUserId === $currentUserId) {
            throw new ConflictException('自分自身を追加することはできません');
        }
    }
}
```

##### 🔹 レベル3: システム全体Rules（複数ドメインで使用）

```php
// app/Services/Project/ProjectRules.php

class ProjectRules
{
    /**
     * 【レベル3: システム全体Rules】
     * 
     * 配置理由:
     * - TaskドメインのCreateTaskUseCase, UpdateTaskUseCase, DeleteTaskUseCaseで使う
     * - MembershipドメインのGetMembersUseCaseで使う
     * - ProjectドメインのUpdateProjectUseCase, DeleteProjectUseCaseで使う
     * 
     * 使用箇所（3つのドメイン）:
     * ✅ UseCases/Task/CreateTaskUseCase.php
     * ✅ UseCases/Task/UpdateTaskUseCase.php
     * ✅ UseCases/Task/DeleteTaskUseCase.php
     * ✅ UseCases/Membership/GetMembersUseCase.php
     * ✅ UseCases/Membership/AddMemberUseCase.php
     * ✅ UseCases/Project/UpdateProjectUseCase.php
     * ✅ UseCases/Project/DeleteProjectUseCase.php
     */
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }

    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * 【レベル3: システム全体Rules】
     * 
     * 配置理由:
     * - AddMemberUseCaseで使う（重複チェック）
     * - ensureMember()の内部でも使う
     */
    public function ensureNotMember(Project $project, int $userId): void
    {
        if ($this->hasUser($project, $userId)) {
            throw new ConflictException('既にメンバーです');
        }
    }

    public function hasUser(Project $project, int $userId): bool
    {
        return $project->users()
            ->where('users.id', $userId)
            ->exists();
    }
}
```

##### 🔹 レベル2: ドメイン内Rules（将来の拡張用・未実装）

```
本プロジェクトでは未実装ですが、以下のような場合に使用します：

例: ふるさと納税システムの場合

app/UseCases/Product/Rules/ApplicationRules.php
┌──────────────────────────────────────────────────────┐
│ 配置理由: Productドメイン内の複数UseCaseで使用       │
├──────────────────────────────────────────────────────┤
│ ✅ ApplyProductUseCase → 申し込み時の重複チェック   │
│ ✅ ShowProductUseCase → 「申し込み済み」表示判定     │
│ ✅ CancelApplicationUseCase → キャンセル期限チェック │
│                                                      │
│ ❌ AddToCartUseCase → 使わない（別ドメイン）        │
│ ❌ PlaceOrderUseCase → 使わない（別ドメイン）       │
└──────────────────────────────────────────────────────┘

// 将来このような実装が必要になる例
class ApplicationRules
{
    public function hasAlreadyApplied(Product $product, int $applicantId): bool
    {
        return Application::where('product_id', $product->id)
            ->where('applicant_id', $applicantId)
            ->whereNull('cancelled_at')
            ->exists();
    }
}
```

**判断フローチャート**:

```
あるルールを実装する必要がある
            │
            ▼
┌───────────────────────────┐
│ 何箇所で使う？            │
└────┬──────────────────┬───┘
     │                  │
     ▼                  ▼
  1箇所            2箇所以上
     │                  │
     ▼                  ▼
┌──────────┐    ┌──────────────────┐
│ private  │    │ 同じドメイン内？  │
│ メソッド │    └────┬────────┬─────┘
└──────────┘         │        │
                     ▼        ▼
                   YES       NO
                     │        │
                     ▼        ▼
            ┌──────────────┐  ┌──────────────┐
            │ UseCases/    │  │ Services/    │
            │ {Domain}/    │  │ {Domain}/    │
            │ Rules/       │  │ {Domain}     │
            │              │  │ Rules.php    │
            └──────────────┘  └──────────────┘
               （未実装）        （実装済み）
```

**本プロジェクトの実装状況**:

| 配置場所 | 実装状況 | 使用例 |
|:---|:---|:---|
| **UseCase内private** | ✅ 実装済み | `AddMemberUseCase::ensureNotSelf()` |
| **UseCases/{Domain}/Rules/** | ❌ 未実装 | （将来的に必要になったら実装） |
| **Services/{Domain}/Rules** | ✅ 実装済み | `ProjectRules::ensureMember()` |

#### 2.4.5 共通のルールとビジネスロジックの分け方

**ルール（判定系）とビジネスロジック（処理系）の違い**:

```
┌─────────────────────────────────────────────────────────┐
│ ルール（判定系） - Rules                                │
├─────────────────────────────────────────────────────────┤
│ 特徴:                                                   │
│ - 判定・チェック・検証のみ                              │
│ - データの変更をしない（参照のみ）                      │
│ - bool を返すか、例外を投げる                           │
│                                                         │
│ メソッド名:                                             │
│ - is〇〇(), has〇〇(), can〇〇() → bool                  │
│ - ensure〇〇(), validate〇〇() → void（例外）            │
│                                                         │
│ 配置場所:                                               │
│ - 複数ドメイン → Services/{Domain}/{Domain}Rules.php   │
│ - ドメイン内 → UseCases/{Domain}/Rules/                │
│ - 単一UseCase → UseCase内のprivateメソッド             │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ ビジネスロジック（処理系） - UseCase                    │
├─────────────────────────────────────────────────────────┤
│ 特徴:                                                   │
│ - データの作成・更新・削除                              │
│ - 処理の流れを組み立てる                                │
│ - ルールを呼び出すが、自分では判定しない                │
│                                                         │
│ 責務:                                                   │
│ - トランザクション管理                                  │
│ - Rulesの呼び出し順序制御                               │
│ - Model操作の組み立て                                   │
│                                                         │
│ 配置場所:                                               │
│ - UseCases/{Domain}/{Action}UseCase.php                │
└─────────────────────────────────────────────────────────┘
```

**本プロジェクトでの実装例**:

```php
// ========================================
// ❌ 悪い例: ルールとビジネスロジックが混在
// ========================================
class CreateTaskUseCase
{
    public function execute(array $data, Project $project, User $user): Task
    {
        // ルール判定をUseCase内で直接実装（NG）
        if (!$project->users()->where('users.id', $user->id)->exists()) {
            throw new AuthorizationException('権限がありません');
        }

        $task = Task::create([...]);
        return $task;
    }
}

// ========================================
// ✅ 良い例: ルールは分離、UseCaseは処理の流れのみ
// ========================================
class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // ルールは外部化
    ) {}

    public function execute(array $data, Project $project, User $user): Task
    {
        // ========================================
        // 1. ルール検証（Rulesに委譲）
        // ========================================
        $this->projectRules->ensureMember($project, $user);

        // ========================================
        // 2. ビジネスロジック（UseCaseの責務）
        // ========================================
        $task = Task::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'created_by' => $user->id,
        ]);

        // ========================================
        // 3. リレーションロード
        // ========================================
        $task->load('createdBy');

        return $task;
    }
}
```

**分け方の判断基準**:

| 質問 | ルール（Rules） | ビジネスロジック（UseCase） |
|:---|:---:|:---:|
| データを変更する？ | ❌ NO | ✅ YES |
| 判定・チェックのみ？ | ✅ YES | ❌ NO |
| bool or 例外を返す？ | ✅ YES | ❌ NO（Modelなどを返す） |
| 複数箇所で使う可能性？ | ✅ YES | ❌ NO |
| トランザクションが必要？ | ❌ NO | ✅ YES |

**まとめ表**:

| 項目 | privateメソッド | UseCases/{Domain}/Rules/ | Services/{Domain}/Rules |
|:---|:---|:---|:---|
| **使用範囲** | 1つのUseCaseのみ | 同じドメイン内の複数UseCase | 複数ドメイン（システム全体） |
| **本プロジェクトでの実装** | ✅ 実装済み | ❌ 未実装 | ✅ 実装済み |
| **データ変更** | ❌ 禁止 | ❌ 禁止 | ❌ 禁止 |
| **戻り値** | bool or void(例外) | bool or void(例外) | bool or void(例外) |
| **実装例** | `ensureNotSelf()` | （未実装） | `ensureMember()` |
| **切り出しタイミング** | 最初から | 2箇所以上で使うようになったら | 3箇所以上（複数ドメイン）で使うようになったら |

#### 2.4.2 トランザクション管理の例

```php:21:45:app/UseCases/Project/CreateProjectUseCase.php
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
```

**Laravelとの比較**:

| 標準Laravel | 本プロジェクト |
|:---|:---|
| Controllerに処理を記載 | **UseCaseに分離** |
| Model直接操作 | **UseCase経由で操作** |
| ビジネスロジック散在 | **UseCaseに集約** |

---

### 2.5 Service層 (`app/Services/`)

**責務**: ビジネスルールの判定（ドメイン知識）

```php:17:147:app/Services/Project/ProjectRules.php
class ProjectRules
{
    /**
     * ユーザーがプロジェクトメンバーか判定
     * 
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * ユーザーがプロジェクトオーナーか判定
     * 
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function isOwner(Project $project, User $user): bool
    {
        $role = $this->getRole($project, $user);
        return $role === 'project_owner';
    }

    /**
     * ユーザーがプロジェクトオーナーまたは管理者か判定
     * 
     * @param Project $project
     * @param User $user
     * @return bool
     */
    public function isOwnerOrAdmin(Project $project, User $user): bool
    {
        $role = $this->getRole($project, $user);
        return in_array($role, ['project_owner', 'project_admin']);
    }

    /**
     * ユーザーが既にメンバーか判定
     * 
     * @param Project $project
     * @param int $userId
     * @return bool
     */
    public function hasUser(Project $project, int $userId): bool
    {
        return $project->users()
            ->where('users.id', $userId)
            ->exists();
    }

    /**
     * プロジェクトメンバーであることを保証（メンバーでなければ例外）
     * 
     * @param Project $project
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }

    /**
     * プロジェクトオーナーであることを保証（オーナーでなければ例外）
     * 
     * @param Project $project
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function ensureOwner(Project $project, User $user): void
    {
        if (!$this->isOwner($project, $user)) {
            throw new AuthorizationException('プロジェクトを削除する権限がありません（オーナーのみ）');
        }
    }

    /**
     * プロジェクトオーナーまたは管理者であることを保証（どちらでもなければ例外）
     * 
     * @param Project $project
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public function ensureOwnerOrAdmin(Project $project, User $user): void
    {
        if (!$this->isOwnerOrAdmin($project, $user)) {
            throw new AuthorizationException('プロジェクトを更新する権限がありません（オーナー・管理者のみ）');
        }
    }

    /**
     * ユーザーが既にメンバーでないことを保証（メンバーなら例外）
     * 
     * @param Project $project
     * @param int $userId
     * @return void
     * @throws ConflictException
     */
    public function ensureNotMember(Project $project, int $userId): void
    {
        if ($this->hasUser($project, $userId)) {
            throw new ConflictException('既にメンバーです');
        }
    }

    /**
     * プロジェクト内でのユーザーのロールを取得
     * 
     * @param Project $project
     * @param User $user
     * @return string|null ロール（メンバーでない場合はnull）
     */
    private function getRole(Project $project, User $user): ?string
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->first()
            ?->pivot->role;
    }
}
```

**Service層の設計原則**:

1. **判定メソッド**: `is〇〇()`, `has〇〇()` - bool を返す
2. **保証メソッド**: `ensure〇〇()` - 条件を満たさない場合は例外
3. **複数UseCaseで共有**: システム全体で使うルールを一元管理

**Laravelとの比較**:
- 標準Laravel: **Policyクラス**で認可を実装
- 本プロジェクト: **Serviceクラス**でビジネスルール全般を管理

#### 2.5.1 ルールの配置場所の判断基準

**「誰が使うか」で配置場所が決まる**

```
┌─────────────────────────────────────────────────────────────┐
│ Q: このルールどこに置く？                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ 1箇所だけで使う（単一UseCaseのみ）                          │
│   → UseCase内のprivateメソッド                              │
│   例: 自分自身を追加できないチェック（AddMemberのみ）       │
│                                                             │
│ 同じドメイン内の複数UseCaseで使う                           │
│   → UseCases/{Domain}/Rules/                               │
│   例: 申し込み済みチェック（ApplyとShowで使う）             │
│                                                             │
│ 複数ドメインで使う（システム全体）                           │
│   → Services/{Domain}/                                     │
│   例: 在庫チェック（Product, Cart, Orderで使う）            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**本プロジェクトでの具体例**:

```
ProjectRules（Services/Project/ProjectRules.php）
┌──────────────────────────────────────────────────────┐
│ 配置理由: 複数ドメインで使用                          │
├──────────────────────────────────────────────────────┤
│ ✅ Task作成時 → メンバーチェック                     │
│    UseCases/Task/CreateTaskUseCase.php               │
│                                                      │
│ ✅ Task更新時 → メンバーチェック                     │
│    UseCases/Task/UpdateTaskUseCase.php               │
│                                                      │
│ ✅ Task削除時 → メンバーチェック                     │
│    UseCases/Task/DeleteTaskUseCase.php               │
│                                                      │
│ ✅ メンバー取得時 → メンバーチェック                 │
│    UseCases/Membership/GetMembersUseCase.php         │
│                                                      │
│ ✅ プロジェクト更新時 → オーナー/管理者チェック      │
│    UseCases/Project/UpdateProjectUseCase.php         │
│                                                      │
│ 👉 複数ドメイン（Task, Membership, Project）で使う！ │
└──────────────────────────────────────────────────────┘

AddMemberUseCase内のprivateメソッド
┌──────────────────────────────────────────────────────┐
│ 配置理由: 単一UseCaseでのみ使用                       │
├──────────────────────────────────────────────────────┤
│ ✅ メンバー追加時 → 自分自身の追加不可チェック       │
│    UseCases/Membership/AddMemberUseCase.php          │
│                                                      │
│ ❌ メンバー削除時 → 使わない                         │
│    理由: 削除は自分でも可能な設計                    │
│                                                      │
│ ❌ 他のUseCase → 使わない                            │
│                                                      │
│ 👉 AddMemberUseCaseだけ！だからprivateメソッド       │
└──────────────────────────────────────────────────────┘
```

#### 2.5.2 判定メソッドと保証メソッドのペア

**基本パターン**: 判定メソッド（bool）+ 保証メソッド（例外）をセットで実装

```php
class ProjectRules
{
    // ========================================
    // 判定メソッド（bool を返す）
    // ========================================
    
    /**
     * ユーザーがプロジェクトメンバーか判定
     * 用途: 条件分岐、表示制御など
     */
    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    // ========================================
    // 保証メソッド（例外を投げる）
    // ========================================
    
    /**
     * プロジェクトメンバーであることを保証
     * 用途: ビジネスロジックの事前条件チェック
     */
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }
}
```

**使い分け**:

| メソッド | 戻り値 | 使用場面 | 例 |
|:---|:---|:---|:---|
| `is〇〇()` | bool | 条件分岐 | `if ($rules->isMember(...)) { ... }` |
| `has〇〇()` | bool | 存在チェック | `if ($rules->hasUser(...)) { ... }` |
| `can〇〇()` | bool | 可否判定 | `if ($rules->canDelete(...)) { ... }` |
| `ensure〇〇()` | void | 必須条件 | `$rules->ensureMember(...);` |

**命名規則**:

```
is{状態}()       → bool    例: isMember(), isOwner(), isPublished()
has{状態}()      → bool    例: hasUser(), hasStock(), hasPermission()
can{動作}()      → bool    例: canUpdate(), canDelete(), canApply()
meets{条件}()    → bool    例: meetsAgeRequirement()
ensure{条件}()   → void    例: ensureMember(), ensureOwner()（例外投げる）
```

---

### 2.6 Model層 (`app/Models/`)

**責務**: データ構造とリレーションの定義

#### 2.6.1 Project Model

```php:10:41:app/Models/Project.php
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    /**
     * プロジェクトのユーザー（中間テーブル経由）
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * プロジェクトのタスク
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
```

#### 2.6.2 Task Model（状態判定メソッドを持つ）

```php:9:60:app/Models/Task.php
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'status',
        'created_by',
    ];

    /**
     * タスクが属するプロジェクト
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * タスクを作成したユーザー
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * タスクが未着手（todo）か判定
     */
    public function isTodo(): bool
    {
        return $this->status === 'todo';
    }

    /**
     * タスクが作業中（doing）か判定
     */
    public function isDoing(): bool
    {
        return $this->status === 'doing';
    }

    /**
     * タスクが完了（done）か判定
     */
    public function isDone(): bool
    {
        return $this->status === 'done';
    }
}
```

**Model層の設計原則**:
1. **リレーション定義**: Eloquentリレーションの活用
2. **状態判定メソッド**: 自身の状態に関する判定はModelに持つ
3. **ビジネスロジック禁止**: 他のModelに影響する処理は持たない

---

### 2.7 Resource層 (`app/Http/Resources/`)

**責務**: JSONレスポンスの整形

```php:8:27:app/Http/Resources/ProjectResource.php
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_archived' => $this->is_archived,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

**Resource層の利点**:
1. **データ変換の一元化**: JSONの形式を統一
2. **N+1問題の防止**: `whenLoaded()` で条件付きロード
3. **変更の局所化**: API仕様変更時の影響範囲を限定

---

### 2.8 Response層 (`app/Http/Responses/`)

**責務**: 統一されたレスポンス形式の提供

```php:283:320:app/Http/Responses/ApiResponse.php
    public function successWithResource($resource, string $message = '成功', int $status = 200): JsonResponse
    {
        return $resource
            ->additional([
                'success' => true,
                'message' => $message,
            ])
            ->response()
            ->setStatusCode($status);
    }

    /**
     * Resourceを使った作成成功レスポンス
     *
     * @param \Illuminate\Http\Resources\Json\JsonResource $resource
     * @param string $message
     * @return JsonResponse
     *
     * @example 使用例
     * return $this->response()->createdWithResource(new ProjectResource($project), 'プロジェクトを作成しました');
     *
     * @example レスポンス例
     * {
     *     "data": {
     *         "id": 1,
     *         "name": "新プロジェクト",
     *         "is_archived": false,
     *         "created_at": "2026-01-10T12:00:00.000000Z",
     *         "updated_at": "2026-01-10T12:00:00.000000Z"
     *     },
     *     "success": true,
     *     "message": "プロジェクトを作成しました"
     * }
     */
    public function createdWithResource($resource, string $message = '作成しました'): JsonResponse
    {
        return $this->successWithResource($resource, $message, 201);
    }
```

**統一レスポンス形式**:

```json
{
  "success": true,
  "message": "プロジェクトを作成しました",
  "data": {
    "id": 1,
    "name": "新プロジェクト"
  }
}
```

**エラー時**:

```json
{
  "success": false,
  "message": "バリデーションエラー",
  "errors": {
    "name": ["名前は必須です"]
  },
  "request_id": "req_67890abcdef12345"
}
```

---

### 2.9 例外処理層 (`app/Exceptions/`)

**責務**: グローバル例外ハンドリング

```php:42:52:app/Exceptions/ApiExceptionHandler.php
        // 例外タイプに応じて処理を振り分け
        return match (true) {
            $exception instanceof NotFoundHttpException => $this->handleNotFound($requestId),
            $exception instanceof ModelNotFoundException => $this->handleNotFound($requestId, $exception->getMessage()),
            $exception instanceof ValidationException => $this->handleValidation($exception, $requestId),
            $exception instanceof AuthenticationException => $this->handleAuthentication($requestId),
            $exception instanceof AuthorizationException => $this->handleForbidden($exception, $requestId),
            $exception instanceof ConflictException => $this->handleConflict($exception, $requestId),
            default => $this->handleServerError($exception, $request, $requestId),
        };
```

**例外処理の特徴**:
1. **match式による分岐**: PHP 8.0の新機能を活用
2. **Request ID**: エラー追跡のための一意ID
3. **Sentry連携**: 本番エラーの自動収集
4. **統一エラーフォーマット**: ApiResponseと連携

**カスタム例外の例**:

```php:1:30:app/Exceptions/ConflictException.php
<?php

namespace App\Exceptions;

use Exception;

/**
 * 競合エラー（409 Conflict）
 * 
 * リクエストが現在の状態と競合する場合に使用します。
 * 例：既に存在するデータの重複登録、不正な状態遷移など
 * 
 * @example
 * throw new ConflictException('このユーザーは既にプロジェクトのメンバーです');
 */
class ConflictException extends Exception
{
    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     * @param int $code エラーコード
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(string $message = '競合が発生しました', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
```

---

### 2.10 ルールと共通処理の配置判断

#### 2.10.1 配置場所の決定フロー

```
┌─────────────────────────────────────────────────────────────┐
│ このコードを配置する場所は？                                 │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
            ┌────────────────────────┐
            │  判定・チェック系？     │
            │  (is, has, ensure...)  │
            └───┬─────────────────┬──┘
                │ YES             │ NO
                │                 │
                ▼                 ▼
     ┌──────────────────┐   ┌──────────────────┐
     │  Rules に配置    │   │ Service/Support  │
     │                  │   │    に配置        │
     └───┬──────────────┘   └──────────────────┘
         │
         ▼
    ┌──────────────────────────┐
    │ 何箇所で使う？            │
    └───┬──────────────────┬───┘
        │                  │
        ▼                  ▼
   1箇所のみ         2箇所以上
        │                  │
        ▼                  ▼
 ┌─────────────┐    ┌──────────────────────┐
 │ private     │    │ 同じドメイン？        │
 │ メソッド    │    └───┬──────────────┬───┘
 └─────────────┘        │              │
                        ▼              ▼
                    同じ          違う
                        │              │
                        ▼              ▼
              ┌──────────────┐  ┌──────────────┐
              │ UseCases/    │  │ Services/    │
              │ {Domain}/    │  │ {Domain}/    │
              │ Rules/       │  │ {Domain}     │
              │              │  │ Rules.php    │
              └──────────────┘  └──────────────┘
```

#### 2.10.2 RulesとService/Supportの違い

| ファイル種別 | 中身 | メソッド例 | 配置場所 |
|:---|:---|:---|:---|
| **Rules** | 判定・チェック系 | `is〇〇()`, `has〇〇()`, `ensure〇〇()`, `can〇〇()` | `Services/` or `UseCases/{Domain}/Rules/` |
| **Service** | その他の共通処理（システム全体） | `build〇〇()`, `get〇〇()`, `generate〇〇()`, `calculate〇〇()`, `format〇〇()` | `Services/{Domain}/` |
| **Support** | その他の共通処理（ドメイン内） | `build〇〇()`, `get〇〇()`, `generate〇〇()`, `calculate〇〇()`, `format〇〇()` | `UseCases/{Domain}/Support/` |

**具体例**:

```php
// ✅ Rules - 判定系
class ProjectRules
{
    public function isMember(Project $project, User $user): bool { ... }
    public function hasUser(Project $project, int $userId): bool { ... }
    public function ensureOwner(Project $project, User $user): void { ... }
}

// ✅ Service - その他の共通処理（システム全体）
class ProjectService
{
    public function buildDisplayName(Project $project): string { ... }
    public function getImageUrl(Project $project): string { ... }
    public function calculateProgress(Project $project): float { ... }
}

// ✅ Support - その他の共通処理（ドメイン内）
class ProjectSupport
{
    public function generateProjectNumber(): string { ... }
    public function formatProjectName(string $name): string { ... }
}
```

#### 2.10.3 ファイル分割の判断基準

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  メソッドが5個以下                                           │
│    → 1ファイルにまとめてOK                                  │
│       Services/{Domain}/{Domain}Service.php                 │
│       （RulesもServiceも一緒に）                             │
│                                                             │
│  メソッドが6個以上                                           │
│    → ファイルを分ける                                       │
│       Services/{Domain}/{Domain}Rules.php                   │
│       Services/{Domain}/{Domain}Service.php                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**小規模な場合（まとめる）**:

```
Services/
└── Project/
    └── ProjectService.php   ← ルールも共通処理も全部入れる
```

**大きくなってきた場合（分ける）**:

```
Services/
└── Project/
    ├── ProjectRules.php     ← ルールだけ
    └── ProjectService.php   ← 共通処理だけ
```

**本プロジェクトの場合**:

```
Services/
└── Project/
    └── ProjectRules.php     ← 判定メソッドが多いため分離済み
```

#### 2.10.4 配置場所のまとめ表

| スコープ | ルール（判定系） | その他の共通処理 |
|:---|:---|:---|
| **複数ドメイン** | `Services/{Domain}/{Domain}Rules.php` | `Services/{Domain}/{Domain}Service.php` |
| **ドメイン内** | `UseCases/{Domain}/Rules/` | `UseCases/{Domain}/Support/` |
| **単一UseCase** | `UseCase内のprivateメソッド` | `UseCase内のprivateメソッド` |

---

### 2.11 命名規則

#### 2.11.1 UseCase

```
{動詞}{対象}UseCase.php

例：
CreateProjectUseCase.php      （作成する）
GetProjectsUseCase.php         （一覧取得する）
GetProjectUseCase.php          （詳細取得する）
UpdateProjectUseCase.php       （更新する）
DeleteProjectUseCase.php       （削除する）
AddMemberUseCase.php           （追加する）
RemoveMemberUseCase.php        （削除する）
StartTaskUseCase.php           （開始する）
CompleteTaskUseCase.php        （完了する）
```

**動詞の使い分け**:

| 動詞 | 用途 | 例 |
|:---|:---|:---|
| `Create` | 新規作成 | CreateProjectUseCase |
| `Get` | 取得（単数・複数） | GetProjectUseCase, GetProjectsUseCase |
| `Update` | 更新 | UpdateProjectUseCase |
| `Delete` | 削除 | DeleteProjectUseCase |
| `Add` | 追加（リレーション） | AddMemberUseCase |
| `Remove` | 削除（リレーション） | RemoveMemberUseCase |
| `Start` | 開始（状態遷移） | StartTaskUseCase |
| `Complete` | 完了（状態遷移） | CompleteTaskUseCase |
| `Cancel` | キャンセル | CancelApplicationUseCase |
| `Search` | 検索 | SearchProductsUseCase |

#### 2.11.2 Rules / Service

```
{Domain}Rules.php      ← ルール集約
{Domain}Service.php    ← 共通処理集約
{対象}Checker.php      ← 単機能の判定

例：
ProjectRules.php        （プロジェクト関連ルール集約）
TaskRules.php           （タスク関連ルール集約）
ProjectService.php      （プロジェクト関連共通処理）
StockChecker.php        （在庫チェック単体）
```

#### 2.11.3 メソッド

**判定・チェック系（bool）**:

```php
is{状態}()       → bool    例: isInStock(), isPublished(), isMember()
has{状態}()      → bool    例: hasAlreadyApplied(), hasUser(), hasStock()
can{動作}()      → bool    例: canApply(), canUpdate(), canDelete()
meets{条件}()    → bool    例: meetsAgeRequirement()
```

**保証系（void、例外）**:

```php
ensure{条件}()   → void    例: ensurePurchasable(), ensureMember()
```

**取得系**:

```php
get{情報}()      → mixed   例: getRole(), getImageUrl(), getUnavailableReason()
```

**その他の処理**:

```php
build{対象}()    → mixed   例: buildDisplayName(), buildResponse()
generate{対象}() → mixed   例: generateApplicationNumber()
calculate{対象}() → mixed  例: calculateProgress(), calculateTotal()
format{対象}()   → mixed   例: formatProjectName(), formatDate()
```

---

### 2.12 ディレクトリ構成の全体像

#### 2.12.1 完全なディレクトリ構成

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── ApiController.php         ← 基底コントローラー
│   │       ├── ProjectController.php
│   │       ├── TaskController.php
│   │       ├── ProjectMemberController.php
│   │       └── UserController.php
│   │
│   ├── Requests/                          ← バリデーション
│   │   ├── Project/
│   │   │   ├── StoreProjectRequest.php
│   │   │   └── UpdateProjectRequest.php
│   │   ├── Task/
│   │   │   ├── StoreTaskRequest.php
│   │   │   └── UpdateTaskRequest.php
│   │   └── Membership/
│   │       └── AddMemberRequest.php
│   │
│   ├── Resources/                         ← JSONレスポンス整形
│   │   ├── ProjectResource.php
│   │   ├── TaskResource.php
│   │   ├── UserResource.php
│   │   └── ProjectMemberResource.php
│   │
│   └── Responses/                         ← 統一レスポンス形式
│       └── ApiResponse.php
│
├── UseCases/                              ← ビジネスロジック組み立て
│   ├── Project/
│   │   ├── CreateProjectUseCase.php
│   │   ├── GetProjectsUseCase.php
│   │   ├── GetProjectUseCase.php
│   │   ├── UpdateProjectUseCase.php
│   │   ├── DeleteProjectUseCase.php
│   │   ├── Rules/                         ← ドメイン内共有ルール
│   │   │   └── ProjectRules.php          （未実装）
│   │   └── Support/                       ← ドメイン内共通処理
│   │       └── ProjectSupport.php        （未実装）
│   │
│   ├── Task/
│   │   ├── CreateTaskUseCase.php
│   │   ├── GetTasksUseCase.php
│   │   ├── GetTaskUseCase.php
│   │   ├── UpdateTaskUseCase.php
│   │   ├── DeleteTaskUseCase.php
│   │   ├── StartTaskUseCase.php
│   │   ├── CompleteTaskUseCase.php
│   │   ├── Rules/                         ← ドメイン内共有ルール
│   │   │   └── TaskRules.php             （未実装）
│   │   └── Support/                       ← ドメイン内共通処理
│   │       └── TaskSupport.php           （未実装）
│   │
│   └── Membership/
│       ├── AddMemberUseCase.php
│       ├── GetMembersUseCase.php
│       ├── RemoveMemberUseCase.php
│       ├── Rules/                         ← ドメイン内共有ルール
│       │   └── MembershipRules.php       （未実装）
│       └── Support/                       ← ドメイン内共通処理
│           └── MembershipSupport.php     （未実装）
│
├── Services/                              ← システム全体で使うルール・処理
│   └── Project/
│       ├── ProjectRules.php              ← システム全体ルール（実装済み）
│       └── ProjectService.php            ← システム全体共通処理（未実装）
│
├── Models/                                ← Eloquent Model
│   ├── User.php
│   ├── Project.php
│   ├── Task.php
│   └── Membership.php
│
└── Exceptions/                            ← カスタム例外
    ├── ApiExceptionHandler.php
    └── ConflictException.php
```

#### 2.12.2 本プロジェクトの特徴

1. **Services/Project/ProjectRules.php が中心**
   - 複数ドメイン（Task, Membership, Project）で使用
   - プロジェクトメンバーシップのルールを一元管理

2. **UseCases/{Domain}/Rules/ は未実装**
   - 現状はドメイン内で共有するルールが少ない
   - 必要になったら追加する方針

3. **privateメソッドの活用**
   - 単一UseCaseでしか使わないルールはprivateメソッドで実装
   - 例: `AddMemberUseCase::ensureNotSelf()`

---

### 2.13 依存関係のルール

#### 2.13.1 依存関係の方向

```
┌────────────────────────────────────────────────────────┐
│                    依存関係の方向                       │
│                                                        │
│  Controller                                            │
│      ↓ 依存OK                                         │
│  UseCase ←──────────────────┐                         │
│      ↓ 依存OK               │ 依存OK                  │
│  Services/{Any}/            │                         │
│  UseCases/{自分}/Rules/     │                         │
│      ↓ 依存OK               │                         │
│  Model ─────────────────────┘                         │
│                                                        │
└────────────────────────────────────────────────────────┘
```

#### 2.13.2 許可される依存関係

```php
【✅ 許可】

// Controller → UseCase
class ProjectController extends ApiController
{
    public function __construct(
        private CreateProjectUseCase $createProjectUseCase,
    ) {}
}

// UseCase → Services/{Any}/ （システム全体ルール）
class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // Services/Project/
    ) {}
}

// UseCase → UseCases/{自分}/Rules/ （自ドメインルール）
class ApplyProductUseCase
{
    public function __construct(
        private ProductRules $productRules,      // Services/Product/
        private ApplicationRules $applicationRules, // UseCases/Product/Rules/
    ) {}
}

// UseCase → Model
class CreateProjectUseCase
{
    public function execute(array $data, User $user): Project
    {
        $project = Project::create([...]);  // OK
    }
}

// Rules → Model（参照のみ）
class ProjectRules
{
    public function isMember(Project $project, User $user): bool
    {
        return $project->users()->where('users.id', $user->id)->exists();  // OK
    }
}
```

#### 2.13.3 禁止される依存関係

```php
【❌ 禁止】

// Controller → Model（直接操作）
class ProjectController extends ApiController
{
    public function store(Request $request)
    {
        $project = Project::create([...]);  // NG: UseCaseを経由すべき
    }
}

// UseCase → UseCases/{他}/Rules/（他ドメインのルール）
class CreateTaskUseCase
{
    public function __construct(
        private MembershipRules $membershipRules,  // NG: 他ドメイン
    ) {}
}
// 正しい: ProjectRules（Services/）を使う

// Services/ → UseCases/（逆依存）
class ProjectRules
{
    public function __construct(
        private CreateProjectUseCase $useCase,  // NG: 循環参照
    ) {}
}

// Rules → Model（更新）
class ProjectRules
{
    public function ensureMember(Project $project, User $user): void
    {
        $project->update(['...']);  // NG: 状態変更は禁止
    }
}
```

#### 2.13.4 依存関係の原則

| 原則 | 説明 | 理由 |
|:---|:---|:---|
| **単方向の依存** | 上位層 → 下位層のみ | 循環参照を防ぐ |
| **Controllerは薄く** | ビジネスロジックを持たない | テスト容易性 |
| **UseCaseが司令塔** | 処理の流れを制御 | 処理の可視化 |
| **Rulesは参照のみ** | データの更新禁止 | 副作用の局所化 |
| **Modelは単純に** | ビジネスロジックを持たない | 責務の明確化 |

---

## 📊 3. データベース設計

### 3.1 ER図

```
┌─────────────────┐
│     users       │
├─────────────────┤
│ id (PK)         │
│ name            │
│ email           │
│ password        │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N (memberships)
         │
┌────────▼────────────────────┐
│      memberships            │
├─────────────────────────────┤
│ id (PK)                     │
│ project_id (FK)             │
│ user_id (FK)                │
│ role (enum)                 │
│   - project_owner           │
│   - project_admin           │
│   - project_member          │
│ created_at                  │
│ updated_at                  │
│ UNIQUE(project_id, user_id) │
└────────┬────────────────────┘
         │
         │ N:1 (projects)
         │
┌────────▼────────┐
│    projects     │
├─────────────────┤
│ id (PK)         │
│ name            │
│ is_archived     │
│ created_at      │
│ updated_at      │
└────────┬────────┘
         │
         │ 1:N (tasks)
         │
┌────────▼────────┐
│     tasks       │
├─────────────────┤
│ id (PK)         │
│ project_id (FK) │
│ title           │
│ description     │
│ status (enum)   │
│   - todo        │
│   - doing       │
│   - done        │
│ created_by (FK) │
│ created_at      │
│ updated_at      │
└─────────────────┘
```

### 3.2 テーブル詳細

#### memberships（中間テーブル）

- `UNIQUE(project_id, user_id)`: 同じユーザーの重複登録を防止
- `onDelete('cascade')`: プロジェクト削除時、メンバーシップも削除
- `role`: プロジェクト内の権限管理

---

## 🔄 4. データフロー実例

### 4.1 プロジェクト作成のフロー

```
【リクエスト】
POST /api/projects
{
  "name": "新プロジェクト",
  "is_archived": false
}

┌────────────────────────────────────────────────────────┐
│ 1. ルーティング (routes/api.php)                      │
│    - auth:sanctum ミドルウェアで認証チェック           │
└─────────────────────┬──────────────────────────────────┘
                      │
┌─────────────────────▼──────────────────────────────────┐
│ 2. ProjectController@store                             │
│    - StoreProjectRequest でバリデーション              │
│    - CreateProjectUseCase->execute() を呼び出し        │
└─────────────────────┬──────────────────────────────────┘
                      │
┌─────────────────────▼──────────────────────────────────┐
│ 3. CreateProjectUseCase                                │
│    - DB::transaction 開始                              │
│    - Project::create() でプロジェクト作成              │
│    - $project->users()->attach() でオーナー登録        │
│    - $project->load('users') でリレーションロード      │
└─────────────────────┬──────────────────────────────────┘
                      │
┌─────────────────────▼──────────────────────────────────┐
│ 4. ProjectResource                                     │
│    - JSONに変換                                        │
└─────────────────────┬──────────────────────────────────┘
                      │
┌─────────────────────▼──────────────────────────────────┐
│ 5. ApiResponse                                         │
│    - 統一形式でレスポンス生成                          │
│    - 201 Created ステータス                            │
└────────────────────────────────────────────────────────┘

【レスポンス】
{
  "success": true,
  "message": "プロジェクトを作成しました",
  "data": {
    "id": 1,
    "name": "新プロジェクト",
    "is_archived": false,
    "users": [
      {
        "id": 1,
        "name": "山田太郎",
        "pivot": {
          "role": "project_owner"
        }
      }
    ],
    "created_at": "2026-01-21T12:00:00.000000Z",
    "updated_at": "2026-01-21T12:00:00.000000Z"
  }
}
```

---

## ⭐ 5. アーキテクチャの評価

### 5.1 7つの評価観点

| 観点 | 評価 | コメント |
|------|------|-----------|
| **可読性** | ⭐⭐⭐⭐⭐ | 各層の責務が明確で、コメントも充実。処理の流れが追いやすい |
| **構造の明確さ（責務の分離）** | ⭐⭐⭐⭐⭐ | Controller/UseCase/Service/Modelの4層で完全に責務分離 |
| **保守性** | ⭐⭐⭐⭐⭐ | 変更の影響範囲が限定される。例外処理も一元化 |
| **拡張性** | ⭐⭐⭐⭐ | 新機能追加は容易だが、層が増えるため小規模には過剰 |
| **パフォーマンス** | ⭐⭐⭐⭐ | Eager Loadingで最適化済み。層が多い分、若干のオーバーヘッド |
| **安全性 / バグの可能性** | ⭐⭐⭐⭐⭐ | 型安全、例外処理、Sentry連携で高い安全性 |
| **ベストプラクティス遵守** | ⭐⭐⭐⭐⭐ | DDD/クリーンアーキテクチャの原則を正しく適用 |

**総合評価**: ★★★★★（5/5）

---

### 5.2 👍 良い点

1. **責務の完全分離**
   - Controller: リクエスト受付のみ
   - UseCase: ビジネスロジックの組み立て
   - Service: ビジネスルールの判定
   - Model: データ構造の定義

2. **統一されたレスポンス形式**
   - ApiResponseクラスで全APIレスポンスを統一
   - フロントエンドとの連携が容易

3. **徹底したエラーハンドリング**
   - グローバル例外ハンドラー
   - カスタム例外（ConflictException）
   - Request ID によるエラー追跡
   - Sentry連携

4. **テスト容易性**
   - 各層が独立しているためモックが容易
   - DI（Dependency Injection）の活用

5. **ドキュメントとしてのコード**
   - UseCaseにコメントで処理ステップを明記
   - 各メソッドにPHPDocが充実

---

### 5.3 ⚠️ 改善すべき点

1. **層が多すぎる可能性**
   - 小規模な機能には過剰設計
   - **提案**: シンプルな機能はController → Model 直結も検討

2. **UseCase内のtry-catchが冗長**
   - トランザクション内で個別にtry-catchは不要
   - **提案**: DB::transactionのみで十分

3. **Service層とModel層の境界が曖昧**
   - ProjectRulesはProjectModelに持たせる選択肢も
   - **提案**: ドメインモデルパターンの採用検討

4. **認可ロジックがServiceに分散**
   - Laravel標準のPolicyを使わない理由が不明
   - **提案**: Laravel Policyとの併用を検討

---

## 🛠 6. 具体的な改善案

### 改善案1: トランザクション処理の簡素化

**現状**:

```php
public function execute(array $data, User $user): Project
{
    return DB::transaction(function () use ($data, $user) {
        try {
            // 処理
        } catch (\Exception $e) {
            DB::rollBack();  // 不要（transactionが自動でロールバック）
            throw new \Exception($e->getMessage());
        }
    });
}
```

**改善後**:

```php
public function execute(array $data, User $user): Project
{
    return DB::transaction(function () use ($data, $user) {
        // プロジェクト作成
        $project = Project::create([
            'name' => $data['name'],
            'is_archived' => $data['is_archived'] ?? false,
        ]);

        // 作成者を自動的にオーナーとして追加
        $project->users()->attach($user->id, [
            'role' => 'project_owner',
        ]);

        return $project->load(['users']);
    });
}
```

### 改善案2: Laravel Policyとの統合

**現状**: ServiceのRulesクラスで認可

**改善後**: Laravel標準のPolicy + Rulesの併用

```php
// app/Policies/ProjectPolicy.php
class ProjectPolicy
{
    public function update(User $user, Project $project): bool
    {
        return $this->projectRules->isOwnerOrAdmin($project, $user);
    }
}

// Controller内
public function update(UpdateProjectRequest $request, Project $project): JsonResponse
{
    $this->authorize('update', $project);  // Policy経由で認可
    
    $project = $this->updateProjectUseCase->execute(...);
    return $this->response()->successWithResource(...);
}
```

**メリット**:
- Laravel標準機能の活用
- `$this->authorize()` でコードが簡潔に
- ミドルウェアでの一括認可も可能

---

## 🎯 7. 結論とおすすめ修正方針

### 🐘 総合評価

本プロジェクトのバックエンドアーキテクチャは、**クリーンアーキテクチャの原則を正しく適用した高品質な設計**です。

**特に優れている点**:
- 責務分離の徹底
- エラーハンドリングの完成度
- 統一されたレスポンス形式
- 拡張性と保守性の高さ

**推奨する修正方針**:

1. **短期（優先度：高）**
   - トランザクション内の冗長なtry-catchを削除
   - Laravel Policyとの統合を検討

2. **中期（優先度：中）**
   - シンプルな機能はController → Model直結を許容
   - Service層の役割をドキュメント化

3. **長期（優先度：低）**
   - ドメインモデルパターンの導入検討
   - CQRS（コマンド・クエリ分離）の部分的導入

**最終提言**:  
現状のアーキテクチャは**中〜大規模プロジェクトに最適**です。小規模な機能追加の際は、柔軟にシンプルな実装も許容することで、開発速度と品質のバランスが取れます。

---

## 📚 8. 参考資料

### 本プロジェクトで採用されているパターン

| パターン | 適用箇所 | 効果 |
|:---|:---|:---|
| **レイヤードアーキテクチャ** | 全体構造 | 責務分離、保守性向上 |
| **Use Case パターン** | UseCases/ | ビジネスロジックの集約 |
| **Repository パターン** | （未実装） | データアクセスの抽象化 |
| **Resource パターン** | Http/Resources/ | レスポンス変換の一元化 |
| **Custom Exception** | Exceptions/ | エラー処理の明確化 |
| **Dependency Injection** | 全体 | テスト容易性、疎結合 |

---

## 📐 9. 本プロジェクトの設計方針（Fat Controller → UseCase + Rules構成）

本プロジェクトは、**Fat Controllerリファクタリング設計**を採用し、ルールの配置場所を「誰が使うか」で判断する設計方針を取り入れています。

### 9.1 採用している設計パターン

```
┌─────────────────────────────────────────────────────────────┐
│ Fat Controller → UseCase + Rules 構成                        │
├─────────────────────────────────────────────────────────────┤
│ 設計原則:                                                   │
│ - Controllerは薄く（HTTP層のみ）                            │
│ - ビジネスロジックはUseCaseに集約                           │
│ - ルールは使用範囲に応じて配置                              │
│ - privateメソッドから始めて段階的に抽出                    │
└─────────────────────────────────────────────────────────────┘
```

### 9.2 ルール配置の3段階戦略

本プロジェクトでは、以下の3段階でルールを配置します：

| レベル | 配置場所 | 使用範囲 | 本プロジェクトでの実装 |
|:---|:---|:---|:---|
| **レベル1** | UseCase内private | 1つのUseCaseのみ | ✅ `AddMemberUseCase::ensureNotSelf()` |
| **レベル2** | UseCases/{Domain}/Rules/ | 同じドメイン内の複数UseCase | ❌ 未実装（将来的に必要になったら） |
| **レベル3** | Services/{Domain}/ | 複数ドメイン（システム全体） | ✅ `ProjectRules::ensureMember()` |

**段階的な抽出戦略**:

```
Step 1: 最初はprivateメソッドで実装
         ↓
Step 2: 同じドメイン内で2箇所以上使うようになる
         ↓ UseCases/{Domain}/Rules/ に移動（レベル2）
         ↓
Step 3: 複数ドメインで使うようになる
         ↓ Services/{Domain}/ に移動（レベル3）
```

### 9.3 本プロジェクトでの具体的な実装例

#### 実装例1: レベル1（privateメソッド）

```php
// app/UseCases/Membership/AddMemberUseCase.php

class AddMemberUseCase
{
    public function execute(Project $project, array $data, User $currentUser): User
    {
        $userId = $data['user_id'];

        // レベル1: AddMemberUseCaseでしか使わない
        $this->ensureNotSelf($userId, $currentUser->id);

        $project->users()->attach($userId, ['role' => $role]);
        return $project->users()->find($userId);
    }

    /**
     * レベル1: privateメソッド
     * 理由: AddMemberUseCaseでしか使わない
     */
    private function ensureNotSelf(int $targetUserId, int $currentUserId): void
    {
        if ($targetUserId === $currentUserId) {
            throw new ConflictException('自分自身を追加することはできません');
        }
    }
}
```

#### 実装例2: レベル3（システム全体Rules）

```php
// app/Services/Project/ProjectRules.php

class ProjectRules
{
    /**
     * レベル3: システム全体Rules
     * 
     * 使用箇所（7箇所、3つのドメイン）:
     * - Task: CreateTaskUseCase, UpdateTaskUseCase, DeleteTaskUseCase
     * - Membership: GetMembersUseCase, AddMemberUseCase
     * - Project: UpdateProjectUseCase, DeleteProjectUseCase
     */
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }

    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
```

**使用例（複数ドメイン）**:

```php
// TaskドメインのUseCase
class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // Services/Project/
    ) {}

    public function execute(array $data, Project $project, User $user): Task
    {
        $this->projectRules->ensureMember($project, $user);
        // ...
    }
}

// MembershipドメインのUseCase
class AddMemberUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // Services/Project/
    ) {}

    public function execute(Project $project, array $data, User $currentUser): User
    {
        $this->projectRules->ensureNotMember($project, $userId);
        // ...
    }
}

// ProjectドメインのUseCase
class UpdateProjectUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // Services/Project/
    ) {}

    public function execute(Project $project, array $data, User $user): Project
    {
        $this->projectRules->ensureOwnerOrAdmin($project, $user);
        // ...
    }
}
```

### 9.4 本プロジェクトの設計の特徴

#### ✅ 実装済みの設計パターン

1. **薄いController**: リクエスト受付とレスポンス返却のみ
2. **UseCaseの独立**: 1つのユーザー操作に1つのUseCase
3. **Rulesの外部化**: 複数箇所で使うルールはServices/に配置
4. **privateメソッドの活用**: 1箇所でしか使わないルールは内部に保持
5. **DI（依存性注入）**: コンストラクタでRulesを注入

#### 📋 設計方針の比較表

| 項目 | 標準Laravel | 本プロジェクト |
|:---|:---|:---|
| **Controller** | ビジネスロジックも含む | リクエスト/レスポンスのみ |
| **ビジネスロジック** | Controller内 | UseCaseに分離 |
| **ルール判定** | Controller内 | Services/Rulesに分離 |
| **認可** | Policy | ProjectRulesで実装 |
| **トランザクション** | Controller | UseCase |
| **段階的な抽出** | ❌ なし | ✅ private → Rules → Services |

#### 🎯 この設計の利点

1. **テスト容易性**: 各層が独立しているためモックが容易
2. **再利用性**: Rulesは複数のUseCaseで再利用可能
3. **保守性**: ルールの変更が影響範囲を限定
4. **可読性**: 処理の流れ（UseCase）と判定（Rules）が明確
5. **段階的なリファクタリング**: privateメソッドから始められる

---

## 🔄 10. リファクタリングのガイドライン

### 10.1 Fat Controllerのリファクタリング手順

#### Step 1: Fat Controller の分析

```php
// ❌ Before: Fat Controller（100行以上）
class ProjectController extends Controller
{
    public function store(Request $request)
    {
        // バリデーション（20行）
        $validated = $request->validate([...]);
        
        // 権限チェック（10行）
        if (!Auth::user()->can('create-project')) { ... }
        
        // ビジネスロジック（30行）
        $project = Project::create([...]);
        $project->users()->attach(Auth::id(), ['role' => 'project_owner']);
        
        // レスポンス（10行）
        return response()->json([...]);
    }
}
```

**分析**: 処理を以下に分類する
- HTTPに関すること → Controller に残す
- バリデーション → FormRequest に移動
- ビジネスロジック → UseCase に移動
- 権限チェック → Service/Rules に移動

#### Step 2: FormRequestの作成

```php
// ✅ After: バリデーションを分離
// app/Http/Requests/Project/StoreProjectRequest.php

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // 認可はUseCaseで行う
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_archived' => 'boolean',
        ];
    }
}
```

#### Step 3: UseCaseの作成

```php
// ✅ After: ビジネスロジックを分離
// app/UseCases/Project/CreateProjectUseCase.php

class CreateProjectUseCase
{
    public function execute(array $data, User $user): Project
    {
        return DB::transaction(function () use ($data, $user) {
            // プロジェクト作成
            $project = Project::create([
                'name' => $data['name'],
                'is_archived' => $data['is_archived'] ?? false,
            ]);

            // 作成者を自動的にオーナーとして追加
            $project->users()->attach($user->id, [
                'role' => 'project_owner',
            ]);

            return $project->load(['users']);
        });
    }
}
```

#### Step 4: Controllerを薄くする

```php
// ✅ After: 薄い Controller（10行程度）
class ProjectController extends ApiController
{
    public function __construct(
        private CreateProjectUseCase $createProjectUseCase,
    ) {}

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->createProjectUseCase->execute(
            $request->validated(),
            $request->user()
        );

        return $this->response()->createdWithResource(
            new ProjectResource($project),
            'プロジェクトを作成しました'
        );
    }
}
```

#### Step 5: 共通ルールの抽出（必要に応じて）

**複数UseCaseで同じチェックが出てきたら抽出する**

```php
// 最初はprivateメソッドで書く
class CreateTaskUseCase
{
    public function execute(...)
    {
        $this->checkMembership($project, $user);  // private
        // ...
    }

    private function checkMembership(Project $project, User $user): void
    {
        if (!$project->users()->where('users.id', $user->id)->exists()) {
            throw new AuthorizationException('...');
        }
    }
}

// 👇 他のUseCaseでも使うようになったらRulesに移動
// app/Services/Project/ProjectRules.php
class ProjectRules
{
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }

    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }
}
```

### 10.2 リファクタリングのコツ

#### 段階的にやる

```
Week 1: 1つのControllerメソッドをUseCaseに切り出す
Week 2: 残りのメソッドも切り出す
Week 3: 共通ルールをRulesに抽出
Week 4: 他ドメインでも使うルールをServicesに移動
```

#### 最初は private で書く（本プロジェクトの実践例）

```php
// ========================================
// Step 1: 最初はprivateメソッドで実装
// ========================================
class AddMemberUseCase
{
    public function execute(Project $project, array $data, User $currentUser): User
    {
        $userId = $data['user_id'];
        
        // 👇 最初はprivateメソッドで書く
        $this->checkNotSelf($userId, $currentUser->id);
        $this->checkAlreadyMember($project, $userId);
        
        $project->users()->attach($userId, ['role' => $role]);
        return $project->users()->find($userId);
    }

    // 👇 最初はここに書く
    private function checkNotSelf(int $targetUserId, int $currentUserId): void 
    {
        if ($targetUserId === $currentUserId) {
            throw new ConflictException('自分自身を追加することはできません');
        }
    }

    private function checkAlreadyMember(Project $project, int $userId): void
    {
        if ($project->users()->where('users.id', $userId)->exists()) {
            throw new ConflictException('既にメンバーです');
        }
    }
}

// ========================================
// Step 2: 他のUseCaseでも使うことが判明
// ========================================
// Task作成時にも「既にメンバーか」チェックが必要になった！
class CreateTaskUseCase
{
    public function execute(...)
    {
        // メンバーチェックが必要 → AddMemberUseCaseと重複！
        if (!$project->users()->where('users.id', $user->id)->exists()) {
            throw new AuthorizationException('権限がありません');
        }
    }
}

// ========================================
// Step 3: Servicesに移動（本プロジェクトの実装）
// ========================================
// Services/Project/ProjectRules.php
class ProjectRules
{
    // 複数ドメインで使うのでServicesに配置
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }

    public function ensureNotMember(Project $project, int $userId): void
    {
        if ($this->hasUser($project, $userId)) {
            throw new ConflictException('既にメンバーです');
        }
    }

    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function hasUser(Project $project, int $userId): bool
    {
        return $project->users()
            ->where('users.id', $userId)
            ->exists();
    }
}

// ========================================
// Step 4: UseCaseで使用（本プロジェクトの実装）
// ========================================
class AddMemberUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // DI
    ) {}

    public function execute(Project $project, array $data, User $currentUser): User
    {
        $userId = $data['user_id'];
        
        // Services/Project/ProjectRules から使用
        $this->projectRules->ensureNotMember($project, $userId);
        
        // privateメソッドは残す（AddMemberUseCaseでしか使わない）
        $this->ensureNotSelf($userId, $currentUser->id);
        
        $project->users()->attach($userId, ['role' => $role]);
        return $project->users()->find($userId);
    }

    private function ensureNotSelf(int $targetUserId, int $currentUserId): void 
    {
        if ($targetUserId === $currentUserId) {
            throw new ConflictException('自分自身を追加することはできません');
        }
    }
}

class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // DI
    ) {}

    public function execute(array $data, Project $project, User $user): Task
    {
        // Services/Project/ProjectRules から使用
        $this->projectRules->ensureMember($project, $user);
        
        $task = Task::create([...]);
        return $task;
    }
}
```

**本プロジェクトでの実装の変遷**:

| フェーズ | 実装状態 | 配置場所 |
|:---|:---|:---|
| **フェーズ1** | 最初の実装 | `AddMemberUseCase::checkAlreadyMember()` (private) |
| **フェーズ2** | Task作成でも必要と判明 | `CreateTaskUseCase` でも同じロジック |
| **フェーズ3** | **Servicesに集約（現在）** | `Services/Project/ProjectRules::ensureNotMember()` |
| | privateメソッドは残す | `AddMemberUseCase::ensureNotSelf()` (private) |

**重要なポイント**:

```
✅ DO:
- 最初はprivateメソッドで書く
- 2箇所以上で使うようになったらRulesに抽出
- 複数ドメインで使う場合はServicesに配置

❌ DON'T:
- 最初からRulesクラスを作らない
- 1箇所でしか使わないのにRulesに配置しない
- 「将来使うかも」で早期最適化しない
```

### 10.3 チェックリスト

#### UseCase 切り出し時

- [ ] 1ファイル1ユースケースになっているか
- [ ] `execute` メソッドを使っているか
- [ ] ファイル名は `〇〇UseCase.php` になっているか
- [ ] 戻り値の型を明示したか
- [ ] コメントで処理ステップを明記したか

#### Rules 作成時

- [ ] 配置場所は適切か（ドメイン内 or 全体）
- [ ] 状態を変更していないか（参照のみか）
- [ ] メソッド名は命名規則に沿っているか
- [ ] bool版とensure版（例外）の両方を用意したか
- [ ] 本当に2箇所以上で使うか確認したか

#### Controller スリム化時

- [ ] ビジネスロジックが残っていないか
- [ ] UseCaseをDIで受け取っているか
- [ ] FormRequestでバリデーションしているか
- [ ] ApiResponseで統一形式のレスポンスを返しているか

---

## 🎓 11. よくある質問（FAQ）

### Q1: UseCaseとServiceの違いは？

**A**: 役割が違います。

| 層 | 役割 | 例 |
|:---|:---|:---|
| **UseCase** | 処理の流れを組み立てる（司令塔） | CreateProjectUseCase |
| **Service/Rules** | ビジネスルールの判定 | ProjectRules::isMember() |

```php
// UseCase: 「誰を」「どの順番で」呼ぶか決める
class CreateTaskUseCase
{
    public function execute(...)
    {
        // 1. ルールチェック（Serviceに委譲）
        $this->projectRules->ensureMember($project, $user);
        
        // 2. データ作成
        $task = Task::create([...]);
        
        // 3. リレーションロード
        $task->load('createdBy');
        
        return $task;
    }
}

// Service: ルールの判定だけを行う
class ProjectRules
{
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('...');
        }
    }
}
```

### Q2: いつRulesを別ファイルに切り出すべき？

**A**: 2箇所以上で使うようになったら切り出します。

```
1箇所だけで使う
  → UseCase内のprivateメソッドでOK

2箇所で使う（同じドメイン内）
  → UseCases/{Domain}/Rules/ に移動

3箇所以上で使う（複数ドメイン）
  → Services/{Domain}/ に移動
```

**本プロジェクトでの実例**:

```php
// ========================================
// ❌ 1箇所だけ → privateメソッドのまま
// ========================================
class AddMemberUseCase
{
    // このチェックはAddMemberUseCaseでしか使わない
    // RemoveMemberUseCaseでは使わない（自分を削除するのはOK）
    private function ensureNotSelf(int $targetUserId, int $currentUserId): void
    {
        if ($targetUserId === $currentUserId) {
            throw new ConflictException('自分自身を追加することはできません');
        }
    }
}

// ========================================
// ✅ 複数ドメイン（7箇所）→ Services/に配置
// ========================================
// Services/Project/ProjectRules.php
class ProjectRules
{
    // このチェックは以下の7箇所で使う：
    // - Task作成・更新・削除（3箇所）
    // - Member取得・追加（2箇所）
    // - Project更新・削除（2箇所）
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('このプロジェクトにアクセスする権限がありません');
        }
    }
}
```

**使用箇所の追跡**:

| ルール | 使用箇所 | 配置場所 |
|:---|:---|:---|
| `ensureNotSelf()` | 1箇所（AddMemberのみ） | privateメソッド ✅ |
| `ensureMember()` | 7箇所（Task×3, Member×2, Project×2） | Services/ ✅ |
| `ensureNotMember()` | 1箇所（AddMemberのみ） | Services/（将来の拡張を見越して） ✅ |

### Q3: ModelにビジネスロジックはOK？

**A**: 自身の状態判定のみOKです。

```php
// ✅ OK: 自身の状態判定
class Task extends Model
{
    public function isTodo(): bool
    {
        return $this->status === 'todo';
    }
    
    public function isDoing(): bool
    {
        return $this->status === 'doing';
    }
}

// ❌ NG: 他のModelに影響する処理
class Task extends Model
{
    public function start(): void
    {
        $this->status = 'doing';
        $this->save();  // NG: UseCaseで行うべき
    }
    
    public function canStart(User $user): bool
    {
        return $this->project->users()
            ->where('users.id', $user->id)
            ->exists();  // NG: Rulesで行うべき
    }
}
```

### Q4: Laravel Policyは使わないの？

**A**: Serviceと併用できます。

```php
// Policy: Laravel標準の認可機能
class ProjectPolicy
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    public function update(User $user, Project $project): bool
    {
        return $this->projectRules->isOwnerOrAdmin($project, $user);
    }
}

// Controller: Policyで認可チェック
class ProjectController extends ApiController
{
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);  // Policy
        
        $project = $this->updateProjectUseCase->execute(...);
        return $this->response()->successWithResource(...);
    }
}
```

### Q5: 共通のルールとビジネスロジックの違いは？

**A**: ルールは「判定のみ」、ビジネスロジックは「処理の流れ」です。

**判断基準**:

| 質問 | ルール（Rules） | ビジネスロジック（UseCase） |
|:---|:---:|:---:|
| データを変更する？ | ❌ NO | ✅ YES |
| 判定・チェックのみ？ | ✅ YES | ❌ NO |
| bool or 例外を返す？ | ✅ YES | ❌ NO（Modelなどを返す） |
| 複数箇所で使う？ | ✅ YES | ❌ NO（1つのユーザー操作に1つ） |
| トランザクションが必要？ | ❌ NO | ✅ YES |

**本プロジェクトでの実例**:

```php
// ========================================
// ✅ ルール（判定のみ）- Services/Project/ProjectRules.php
// ========================================
class ProjectRules
{
    // データを変更しない（参照のみ）
    public function isMember(Project $project, User $user): bool
    {
        return $project->users()
            ->where('users.id', $user->id)
            ->exists();  // SELECT文のみ
    }

    // 例外を投げる（データ変更なし）
    public function ensureMember(Project $project, User $user): void
    {
        if (!$this->isMember($project, $user)) {
            throw new AuthorizationException('...');  // 例外のみ
        }
    }
}

// ========================================
// ✅ ビジネスロジック（処理の流れ）- UseCases/Task/CreateTaskUseCase.php
// ========================================
class CreateTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,  // ルールを注入
    ) {}

    public function execute(array $data, Project $project, User $user): Task
    {
        // 1. ルールを呼び出す（判定を委譲）
        $this->projectRules->ensureMember($project, $user);

        // 2. データを作成（ビジネスロジック）
        $task = Task::create([
            'project_id' => $project->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'todo',
            'created_by' => $user->id,
        ]);

        // 3. リレーションロード
        $task->load('createdBy');

        return $task;  // Modelを返す
    }
}

// ========================================
// ❌ 悪い例: ルールとビジネスロジックが混在
// ========================================
class CreateTaskUseCase
{
    public function execute(array $data, Project $project, User $user): Task
    {
        // NG: UseCase内でルールを直接実装
        if (!$project->users()->where('users.id', $user->id)->exists()) {
            throw new AuthorizationException('権限がありません');
        }

        $task = Task::create([...]);
        return $task;
    }
}
```

**なぜ分けるのか？**:

1. **再利用性**: ルールは複数のUseCaseで使える
2. **テスト容易性**: ルールとビジネスロジックを個別にテストできる
3. **保守性**: ルールの変更が他のUseCaseに影響しない
4. **可読性**: 処理の流れ（UseCase）と判定（Rules）が明確に分離

### Q6: トランザクションはどこで管理？

**A**: UseCaseで管理します。

**本プロジェクトでの実例**:

```php
// ========================================
// ✅ UseCase: トランザクション管理
// ========================================
// UseCases/Project/CreateProjectUseCase.php
class CreateProjectUseCase
{
    public function execute(array $data, User $user): Project
    {
        // UseCaseでトランザクション管理
        return DB::transaction(function () use ($data, $user) {
            // 1. プロジェクト作成
            $project = Project::create([
                'name' => $data['name'],
                'is_archived' => $data['is_archived'] ?? false,
            ]);

            // 2. オーナー登録
            $project->users()->attach($user->id, [
                'role' => 'project_owner',
            ]);

            return $project->load(['users']);
        });
    }
}

// ========================================
// ✅ Controller: トランザクションは意識しない
// ========================================
class ProjectController extends ApiController
{
    public function store(StoreProjectRequest $request): JsonResponse
    {
        // トランザクションの存在を知らない
        $project = $this->createProjectUseCase->execute(
            $request->validated(),
            $request->user()
        );
        
        return $this->response()->createdWithResource(
            new ProjectResource($project),
            'プロジェクトを作成しました'
        );
    }
}
```

**理由**:

1. **単一責務の原則**: Controllerはリクエスト/レスポンスのみ
2. **ビジネスロジックの隠蔽**: Controllerはトランザクションを知る必要がない
3. **テスト容易性**: UseCaseのテストでトランザクションを検証できる

---

以上が、本プロジェクトのバックエンドアーキテクチャ設計書です。ご質問や追加の分析が必要な箇所があれば、お気軽にお申し付けください！ 🚀