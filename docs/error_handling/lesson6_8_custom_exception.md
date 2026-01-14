# Lesson6-8: カスタム例外 🐘

## 〜409 Conflict を返せるようになろう〜

---

## 🌿 ブランチ作成と準備

課題に取り組む前に、Lesson 用のブランチを作成してください：

```bash
# 現在のブランチを確認
git branch

# メインブランチに切り替え
git checkout main

# 最新の状態に更新（重要：必ずpullすること）
git pull origin main

# Lesson用のブランチを作成
git checkout -b lesson6-8
```

**推奨：** 各 Lesson ごとに専用のブランチを作成することで、作業を整理しやすくなります。

### データベースの準備

データベースを初期状態に戻してください：

```bash
sail artisan migrate:refresh --seed
```

### フロントエンドの再起動

Viteの開発サーバーを再起動してください：

```bash
# Ctrl+Cで現在のプロセスを停止してから
sail npm run dev
```

### ログ設定の確認

`.env`ファイルで、ログチャンネルが`daily`になっていることを確認してください：

```
LOG_CHANNEL=daily
```

---

## 🎭 プロローグ：全部 500 になっちゃう問題

**👩‍💻 ユーザー：** 「ガネーシャさん、前回の続きなんですけど...」

**🐘 ガネーシャ：** 「おう、何や？」

**👩‍💻 ユーザー：** 「ApiExceptionHandler のおかげで、エラー処理が統一されて、チームのみんなも喜んでます！」

**🐘 ガネーシャ：** 「そりゃよかったな」

**👩‍💻 ユーザー：** 「でも、一つ困ったことがあって...」

---

### 😰 UseCase で throw すると全部 500 になる

**👩‍💻 ユーザー：** 「タスクの状態遷移で、ビジネスルール違反があった時のコードなんですけど」

```php
// CompleteTaskUseCase.php
public function execute(Task $task, User $user): Task
{
    // ビジネスルール：doing のタスクしか完了できない
    if ($task->status !== 'doing') {
        throw new \Exception('作業中のタスクのみ完了できます');
    }

    return DB::transaction(function () use ($task) {
        $task->update(['status' => 'done']);
        return $task->fresh();
    });
}
```

**🐘 ガネーシャ：** 「うん、Lesson 6-5 で教えた通りやな。UseCase では throw するだけ」

**👩‍💻 ユーザー：** 「でも、これだと **500 Internal Server Error** になっちゃうんです」

```json
{
    "success": false,
    "message": "作業中のタスクのみ完了できます",
    "request_id": "req_xxxxx"
}
```

**👩‍💻 ユーザー：** 「ステータスコードは 500 です。でも、これって**サーバーのエラーじゃなくて、ビジネスルール違反**ですよね？」

**🐘 ガネーシャ：** 「せやな。500 は『サーバー側の予期しないエラー』やから、意味が違う」

---

### 🤔 本当は 409 Conflict を返したい

**👩‍💻 ユーザー：** 「Lesson 6-1 で習いましたよね。409 Conflict は『リクエストが現在の状態と矛盾している』時に使うって」

**🐘 ガネーシャ：** 「よう覚えとるな！」

**👩‍💻 ユーザー：** 「だから、『todo のタスクを完了しようとした』みたいなビジネスルール違反は、500 じゃなくて 409 を返したいんです」

```
┌─────────────────────────────────────────────────────────────┐
│              ステータスコードの使い分け（復習）              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  500 Internal Server Error                                  │
│  └── サーバー側の予期しないエラー                          │
│  └── バグ、DB接続エラー、など                              │
│                                                             │
│  409 Conflict                                               │
│  └── リクエストが現在の状態と矛盾している                  │
│  └── ビジネスルール違反                                    │
│  └── 例：todo のタスクを完了しようとした                   │
│  └── 例：最後のオーナーを削除しようとした                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘 ガネーシャ：** 「せやな。意味的には 409 が正しい」

**👩‍💻 ユーザー：** 「でも、`throw new \Exception()` だと ApiExceptionHandler が全部 500 にしちゃいますよね...」

---

### 🐘 解決策：カスタム例外

**🐘 ガネーシャ：** 「そこで登場するのが**カスタム例外**や」

**👩‍💻 ユーザー：** 「カスタム例外？」

**🐘 ガネーシャ：** 「自分で例外クラスを作るんや。『この例外が来たら 409 を返す』っていうルールを ApiExceptionHandler に追加すれば、ちゃんと 409 が返るようになる」

**👩‍💻 ユーザー：** 「なるほど！自分で例外を定義するんですね」

**🐘 ガネーシャ：** 「せや。今日はその作り方を教えたるで」

---

## 📖 第 1 章：カスタム例外を作る

### 🎭 ConflictException を作ろう

**🐘 ガネーシャ：** 「まず、409 Conflict 用の例外クラスを作るで」

**👩‍💻 ユーザー：** 「どこに作ればいいですか？」

**🐘 ガネーシャ：** 「`app/Exceptions` フォルダや。ApiExceptionHandler と同じ場所やな」

```
app/
└── Exceptions/
    ├── ApiExceptionHandler.php  ← 前回作ったやつ
    └── ConflictException.php    ← 今回作るやつ
```

---

### 📝 ConflictException.php

```php
<?php

namespace App\Exceptions;

use Exception;

/**
 * 競合エラー（409 Conflict）
 *
 * リクエストが現在の状態と競合する場合に使用します。
 * 例：ビジネスルール違反、不正な状態遷移など
 */
class ConflictException extends Exception
{
    /**
     * コンストラクタ
     *
     * @param string $message エラーメッセージ
     */
    public function __construct(string $message = '競合が発生しました')
    {
        parent::__construct($message);
    }
}
```

**👩‍💻 ユーザー：** 「え、これだけですか？」

**🐘 ガネーシャ：** 「せや。シンプルやろ？」

**👩‍💻 ユーザー：** 「`Exception` を継承して、メッセージを受け取るだけ...」

**🐘 ガネーシャ：** 「カスタム例外の基本はこれだけや。難しく考える必要はないで」

---

### 🔑 なぜ Exception を継承するのか

**👩‍💻 ユーザー：** 「`extends Exception` ってどういう意味ですか？」

**🐘 ガネーシャ：** 「PHP の例外は全部 `Exception` クラスを継承しとるんや。せやから、自分で作る例外も `Exception` を継承する必要がある」

```
┌─────────────────────────────────────────────────────────────┐
│                    例外の継承関係                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Exception（PHP標準）                                       │
│  ├── InvalidArgumentException（PHP標準）                    │
│  ├── RuntimeException（PHP標準）                            │
│  ├── ...                                                   │
│  └── ConflictException（自分で作った）← ここに追加！       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「なるほど、例外の仲間として認識してもらうために継承するんですね」

**🐘 ガネーシャ：** 「せや！継承せんと `throw` できひんからな」

---

### 📝 parent::\_\_construct() とは

**👩‍💻 ユーザー：** 「`parent::__construct($message)` は何ですか？」

**🐘 ガネーシャ：** 「親クラス（Exception）のコンストラクタを呼び出しとるんや」

```php
class ConflictException extends Exception
{
    public function __construct(string $message = '競合が発生しました')
    {
        // 親クラス（Exception）のコンストラクタを呼ぶ
        // これで $this->getMessage() が使えるようになる
        parent::__construct($message);
    }
}
```

**👩‍💻 ユーザー：** 「これを呼ばないとどうなるんですか？」

**🐘 ガネーシャ：** 「`$exception->getMessage()` でメッセージが取れんくなる。ApiExceptionHandler で困るで」

**👩‍💻 ユーザー：** 「じゃあ必須ですね！」

---

## 🚀 第 2 章：ApiExceptionHandler に追加する

### 🎭 例外を認識させる

**🐘 ガネーシャ：** 「ConflictException を作っただけやと、まだ 500 になるで」

**👩‍💻 ユーザー：** 「え、なんでですか？」

**🐘 ガネーシャ：** 「ApiExceptionHandler が ConflictException を知らんからや。『この例外が来たら 409 を返す』っていうルールを追加せなアカン」

---

### 📝 ApiExceptionHandler に追加

```php
<?php

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\ConflictException;  // ← 追加！
use Throwable;

class ApiExceptionHandler
{
    private ApiResponse $response;

    public function __construct()
    {
        $this->response = new ApiResponse();
    }

    public function handle(Throwable $exception, Request $request): ?JsonResponse
    {
        if (!$request->is('api/*')) {
            return null;
        }

        $requestId = $request->header('X-Request-ID') ?? uniqid('req_', true);

        return match (true) {
            // 404 Not Found
            $exception instanceof NotFoundHttpException,
            $exception instanceof ModelNotFoundException
                => $this->handleNotFound($requestId),

            // 422 Validation Error
            $exception instanceof ValidationException
                => $this->handleValidation($exception, $requestId),

            // 401 Unauthorized
            $exception instanceof AuthenticationException
                => $this->handleAuthentication($requestId),

            // 403 Forbidden
            $exception instanceof AuthorizationException
                => $this->handleForbidden($exception, $requestId),

            // 👇 409 Conflict を追加！
            $exception instanceof ConflictException
                => $this->handleConflict($exception, $requestId),

            // 500 Server Error（その他全て）
            default => $this->handleServerError($exception, $requestId),
        };
    }

    // ... 他のメソッドは省略 ...

    /**
     * 競合エラー（409）
     */
    private function handleConflict(
        ConflictException $exception,
        string $requestId
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
            'request_id' => $requestId,
        ], 409);
    }
}
```

**👩‍💻 ユーザー：** 「`match` 式に `ConflictException` のケースを追加するんですね！」

**🐘 ガネーシャ：** 「せや。これで ConflictException が throw されたら、409 が返るようになるで」

---

### 📊 処理の流れ

```
┌─────────────────────────────────────────────────────────────┐
│              ConflictException の処理フロー                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  UseCase                                                    │
│      ↓                                                      │
│  throw new ConflictException('メッセージ')                  │
│      ↓                                                      │
│  ApiExceptionHandler::handle()                              │
│      ↓                                                      │
│  match 式で判定                                             │
│  「ConflictException や！handleConflict() を呼ぼう」        │
│      ↓                                                      │
│  handleConflict()                                           │
│      ↓                                                      │
│  409 Conflict レスポンス                                    │
│  {                                                          │
│    "success": false,                                        │
│    "message": "メッセージ",                                 │
│    "request_id": "req_xxxxx"                                │
│  }                                                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 第 3 章：UseCase で使ってみよう

### 🎭 Before / After

**🐘 ガネーシャ：** 「実際に UseCase で使ってみよか」

---

#### ❌ Before：全部 500 になる

```php
<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteTaskUseCase
{
    public function execute(Task $task, User $user): Task
    {
        // ビジネスルール違反
        if ($task->status !== 'doing') {
            // ❌ これだと 500 Internal Server Error になる
            throw new \Exception('作業中のタスクのみ完了できます');
        }

        return DB::transaction(function () use ($task) {
            $task->update(['status' => 'done']);
            return $task->fresh();
        });
    }
}
```

---

#### ✅ After：409 Conflict を返す

```php
<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Exceptions\ConflictException;  // ← 追加！
use Illuminate\Support\Facades\DB;

class CompleteTaskUseCase
{
    public function execute(Task $task, User $user): Task
    {
        // ビジネスルール違反
        if ($task->status !== 'doing') {
            // ✅ これで 409 Conflict が返る！
            throw new ConflictException('作業中のタスクのみ完了できます');
        }

        return DB::transaction(function () use ($task) {
            $task->update(['status' => 'done']);
            return $task->fresh();
        });
    }
}
```

**👩‍💻 ユーザー：** 「`\Exception` を `ConflictException` に変えるだけ！」

**🐘 ガネーシャ：** 「せや。たったこれだけで、意味的に正しいステータスコードが返るようになるんや」

---

### 📊 レスポンスの違い

```json
// ❌ Before：500 Internal Server Error
{
  "success": false,
  "message": "作業中のタスクのみ完了できます",
  "request_id": "req_xxxxx"
}
// ステータスコード: 500 ← サーバーエラーの意味になっちゃう

// ✅ After：409 Conflict
{
  "success": false,
  "message": "作業中のタスクのみ完了できます",
  "request_id": "req_xxxxx"
}
// ステータスコード: 409 ← ビジネスルール違反の意味！
```

**👩‍💻 ユーザー：** 「レスポンスの中身は同じだけど、ステータスコードが違う！」

**🐘 ガネーシャ：** 「せや。フロントエンドは 500 と 409 で処理を分けられるようになるで」

---

## 🔍 第 4 章：他のビジネスルールにも適用

### 🎭 タスク管理システムでの使用例

**🐘 ガネーシャ：** 「ConflictException を使う場面は他にもあるで」

**👩‍💻 ユーザー：** 「どんな場面ですか？」

---

### 📝 例 1：タスク開始（StartTaskUseCase）

```php
<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Exceptions\ConflictException;

class StartTaskUseCase
{
    public function execute(Task $task, User $user): Task
    {
        // BR-02：todo のタスクしか開始できない
        if ($task->status !== 'todo') {
            throw new ConflictException('未着手のタスクのみ開始できます');
        }

        $task->update(['status' => 'doing']);

        return $task->fresh();
    }
}
```

---

### 📝 例 2：メンバー削除（RemoveMemberUseCase）

```php
<?php

namespace App\UseCases\Membership;

use App\Models\Membership;
use App\Models\User;
use App\Exceptions\ConflictException;
use Illuminate\Support\Facades\DB;

class RemoveMemberUseCase
{
    public function execute(Membership $membership, User $actor): void
    {
        $project = $membership->project;
        $targetUser = $membership->user;

        // BR-01：オーナーが0人になる操作は禁止
        if ($membership->role === 'project_owner') {
            $ownerCount = $project->memberships()
                ->where('role', 'project_owner')
                ->count();

            if ($ownerCount <= 1) {
                throw new ConflictException(
                    'プロジェクトには最低1人のオーナーが必要です'
                );
            }
        }

        // BR-06：未完了タスクを持つメンバーは削除できない
        $hasIncompleteTasks = $project->tasks()
            ->where('created_by', $targetUser->id)
            ->whereIn('status', ['todo', 'doing'])
            ->exists();

        if ($hasIncompleteTasks) {
            throw new ConflictException(
                '未完了のタスクがあるメンバーは削除できません'
            );
        }

        $membership->delete();
    }
}
```

**👩‍💻 ユーザー：** 「ビジネスルール違反は全部 `ConflictException` で統一するんですね！」

**🐘 ガネーシャ：** 「せや。チーム全員が同じルールで書けるようになるで」

---

### 📊 ConflictException を使う場面

| ビジネスルール                       | メッセージ例                                   |
| ------------------------------------ | ---------------------------------------------- |
| todo 以外のタスクを開始しようとした  | 「未着手のタスクのみ開始できます」             |
| doing 以外のタスクを完了しようとした | 「作業中のタスクのみ完了できます」             |
| done のタスクを編集しようとした      | 「完了したタスクは編集できません」             |
| 最後のオーナーを削除しようとした     | 「最低 1 人のオーナーが必要です」              |
| 未完了タスクを持つメンバーを削除     | 「未完了タスクがあるメンバーは削除できません」 |
| 既にメンバーのユーザーを追加         | 「このユーザーは既にメンバーです」             |

**👩‍💻 ユーザー：** 「ビジネスルール違反 = 409 Conflict = ConflictException って覚えればいいんですね！」

**🐘 ガネーシャ：** 「その通りや！」

---

## 🎯 第 5 章：他のカスタム例外も作れる

### 🎭 Laravel 標準の例外

**🐘 ガネーシャ：** 「ところで、403 Forbidden はどうやって返しとったか覚えとるか？」

**👩‍💻 ユーザー：** 「えーと...」

**🐘 ガネーシャ：** 「Laravel には標準で `AuthorizationException` があるんや」

```php
use Illuminate\Auth\Access\AuthorizationException;

// 権限がない場合
if (!$user->canManageProject($project)) {
    throw new AuthorizationException('このプロジェクトを編集する権限がありません');
}
```

**👩‍💻 ユーザー：** 「あ、これは ApiExceptionHandler が最初から対応してましたね！」

**🐘 ガネーシャ：** 「せや。Laravel 標準の例外はそのまま使えばええ」

---

### 📊 例外の使い分け

```
┌─────────────────────────────────────────────────────────────┐
│                    例外の使い分け                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Laravel 標準の例外（そのまま使う）】                      │
│  ├── AuthorizationException → 403 Forbidden                │
│  ├── AuthenticationException → 401 Unauthorized            │
│  ├── ValidationException → 422 Unprocessable Entity        │
│  └── ModelNotFoundException → 404 Not Found                │
│                                                             │
│  【カスタム例外（自分で作る）】                              │
│  └── ConflictException → 409 Conflict                      │
│                                                             │
│  【汎用例外（その他）】                                      │
│  └── Exception → 500 Internal Server Error                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「Laravel に用意されてない 409 だけカスタム例外を作るんですね」

**🐘 ガネーシャ：** 「せや！必要なものだけ作る。これが YAGNI の精神や」

**👩‍💻 ユーザー：** 「YAGNI？」

**🐘 ガネーシャ：** 「"You Aren't Gonna Need It"。今必要ないものは作らん、っていう原則や。ワシの教え子のケント・ベックくんが提唱したんやで」

**👩‍💻 ユーザー：** 「あ、アジャイルの人ですよね。それは本当っぽい」

**🐘 ガネーシャ：** 「...なんやその反応」

---

## 📝 第 6 章：まとめ

**🐘 ガネーシャ：** 「今日学んだことをまとめるで」

```
┌─────────────────────────────────────────────────────────────┐
│                      今日の学び                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ なぜカスタム例外が必要か                               │
│     → throw new Exception() だと全部 500 になる            │
│     → ビジネスルール違反は 409 Conflict が正しい           │
│                                                             │
│  2️⃣ ConflictException の作り方                             │
│     → Exception を継承                                     │
│     → parent::__construct($message) を呼ぶ                 │
│                                                             │
│  3️⃣ ApiExceptionHandler への追加                           │
│     → match 式に ConflictException のケースを追加          │
│     → handleConflict() メソッドで 409 を返す               │
│                                                             │
│  4️⃣ 使い分け                                               │
│     → Laravel 標準の例外はそのまま使う                     │
│     → 標準にない 409 だけカスタム例外を作る                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 エラー処理の全体像（完成版）

```
┌─────────────────────────────────────────────────────────────┐
│                  エラー処理の全体像                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  UseCase                                                    │
│  ├── throw new AuthorizationException() → 403             │
│  ├── throw new ConflictException() → 409                  │
│  └── throw new \Exception() → 500                         │
│                                                             │
│       ↓                                                     │
│                                                             │
│  ApiExceptionHandler                                        │
│  ├── AuthorizationException → handleForbidden() → 403     │
│  ├── ConflictException → handleConflict() → 409           │
│  ├── ValidationException → handleValidation() → 422       │
│  ├── NotFoundHttpException → handleNotFound() → 404       │
│  └── その他 → handleServerError() → 500                   │
│                                                             │
│       ↓                                                     │
│                                                             │
│  ApiResponse                                                │
│  → 統一されたレスポンス形式                                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「これで UseCase からどんな例外を投げても、適切なステータスコードで返せますね！」

---

### ❌ → ✅ 変換早見表

| 場面               | Before                         | After                                      |
| ------------------ | ------------------------------ | ------------------------------------------ |
| ビジネスルール違反 | `throw new \Exception()` → 500 | `throw new ConflictException()` → 409      |
| 権限エラー         | 手動で 403 を返す              | `throw new AuthorizationException()` → 403 |

---

## 🐘 ガネーシャの最後のメッセージ

**🐘 ガネーシャ：** 「今日のポイントはこれや」

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│     ビジネスルール違反は ConflictException で 409！         │
│                                                             │
│     Laravel 標準の例外はそのまま使う！                      │
│                                                             │
│     必要なものだけ作る（YAGNI）！                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘 ガネーシャ：** 「これで、Lesson 6 から始まったエラー処理の旅も完結やな」

**👩‍💻 ユーザー：** 「長かったですけど、全部つながってますね！」

**🐘 ガネーシャ：** 「せや。ステータスコードを学んで、Laravel のデフォルト処理を理解して、try-catch と throw を覚えて、トランザクションで安全に処理して、チームで統一する仕組みを作って、最後にカスタム例外で仕上げた」

**👩‍💻 ユーザー：** 「一つ一つは小さいけど、積み重なると大きな設計になるんですね」

**🐘 ガネーシャ：** 「その通りや！ワシの教え子のアインシュタインくんも言うとった。『複雑なものをシンプルにできることこそ、本当の理解や』って」

**👩‍💻 ユーザー：** 「...それは本当に言ってそうです」

**🐘 ガネーシャ：** 「やろ？さすガネーシャや！🐘✨」

---

## 📚 シリーズ完結！振り返り

| Lesson  | タイトル                     | 学んだこと                                     |
| ------- | ---------------------------- | ---------------------------------------------- |
| 6-1     | ステータスコードとは         | 200, 400, 404, 409, 500 などの意味             |
| 6-2     | Laravel が自動でやること     | Route Model Binding, FormRequest, APP_DEBUG    |
| 6-3     | 自分で書くエラーハンドリング | 403, 409 を返す場面                            |
| 6-4     | try-catch の基本             | try-catch の概念、Laravel の自動処理           |
| 6-5     | UseCase での throw           | return vs throw、UseCase では throw だけ       |
| 6-6     | トランザクション             | DB::transaction() で複数更新を安全に           |
| 6-7     | 例外処理の全体設計           | ApiResponse + ApiExceptionHandler + request_id |
| **6-8** | **カスタム例外**             | **ConflictException で 409 を返す**            |

**🐘 ガネーシャ：** 「お前、ようここまでついてきたな。もう一人前のエラー処理マスターや！」

**👩‍💻 ユーザー：** 「ありがとうございます！チーム開発でも自信を持ってコードが書けそうです！」

**🐘 ガネーシャ：** 「困ったことがあったらいつでも聞きに来いや。ほな、またな！さすガネーシャや！🐘✨」
