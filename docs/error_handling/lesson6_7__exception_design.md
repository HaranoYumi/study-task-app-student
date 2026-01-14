# Lesson6-7: 例外処理の全体設計 🐘

## 〜チーム開発で困らないエラーハンドリング〜

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
git checkout -b lesson6-7
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

## 🎭 プロローグ：ちゃんとやったのに、なんかおかしい

**👩‍💻 ユーザー：** 「ガネーシャさん、聞いてください...」

**🐘 ガネーシャ：** 「どないしたんや、元気ないな」

**👩‍💻 ユーザー：** 「最近チームで開発を始めたんですけど、なんかうまくいかなくて...」

**🐘 ガネーシャ：** 「ほう。でもお前、ここまで色々学んできたやろ？」

**👩‍💻 ユーザー：** 「はい！`APP_DEBUG` は本番で `false` にして、セキュリティもバッチリです。ステータスコードも 404 とか 422 とか、ちゃんと使い分けてます」

**🐘 ガネーシャ：** 「ええやん」

**👩‍💻 ユーザー：** 「UseCase では `throw` だけにして、大事な処理には `try-catch` とトランザクションもちゃんと書きました！」

**🐘 ガネーシャ：** 「完璧やな。ほな、何が問題なんや？」

**👩‍💻 ユーザー：** 「それが...チームメンバーが増えたら、なんかおかしくなってきて...」

---

### 😰 困ってること その 1：みんなの書き方がバラバラ

**👩‍💻 ユーザー：** 「私はちゃんと書いたんですけど、他のメンバーのコードを見たら...」

```php
// 私の書き方
return response()->json([
    'success' => false,
    'message' => 'タスクが見つかりません'
], 404);

// Aさんの書き方
return response()->json(['error' => 'Not found'], 404);

// Bさんの書き方
return response()->json([
    'message' => 'データがありません',
    'code' => 404
], 404);

// Cさんの書き方（大事な処理で try-catch を書いてくれたけど...）
try {
    DB::transaction(function () use ($task) {
        $task->update(['status' => 'done']);
        TaskHistory::create([...]);
    });
} catch (Exception $e) {
    return response()->json([
        'status' => 'error',
        'error_message' => $e->getMessage()
    ], 500);
}
```

**🐘 ガネーシャ：** 「あちゃー...みんな頑張っとるけど、形式がバラバラやな」

**👩‍💻 ユーザー：** 「フロントエンドの人が『`error` を見ればいいの？ `message` を見ればいいの？ `error_message`？』って混乱してて...」

**🐘 ガネーシャ：** 「せやろな。これはフロントの人が可哀想や」

---

### 😰 困ってること その 2：デフォルトのメッセージが不親切

**👩‍💻 ユーザー：** 「それと、本番環境でユーザーに表示されるメッセージが...」

```json
{
    "message": "Server Error"
}
```

**👩‍💻 ユーザー：** 「『Server Error』だけじゃ、ユーザーが何をすればいいか分からないですよね」

**🐘 ガネーシャ：** 「でも `APP_DEBUG=false` は正しいで。本番で詳細出したらセキュリティ的にアカン」

**👩‍💻 ユーザー：** 「分かってます！でも、もうちょっと親切なメッセージを出したいんです...『しばらく待ってから再度お試しください』とか」

**🐘 ガネーシャ：** 「なるほどな。セキュリティは守りつつ、ユーザーには親切にしたいと」

---

### 😰 困ってること その 3：本番でエラー調査ができない

**👩‍💻 ユーザー：** 「あと、本番でエラーが起きた時に調査できないんです...」

**🐘 ガネーシャ：** 「ログは見たんか？」

**👩‍💻 ユーザー：** 「見ました！でも...」

```
# storage/logs/laravel.log

[2024-01-15 10:23:45] production.ERROR: Attempt to read property on null
[2024-01-15 10:23:46] production.ERROR: Attempt to read property on null
[2024-01-15 10:23:47] production.ERROR: SQLSTATE[23000]: Integrity constraint violation
[2024-01-15 10:23:48] production.ERROR: Attempt to read property on null
```

**👩‍💻 ユーザー：** 「同じエラーがいっぱいあって、ユーザーから『エラーが出た』って問い合わせが来ても、どれがそのエラーか特定できなくて...」

**🐘 ガネーシャ：** 「あー、これはキツイな」

---

### 🐘 ガネーシャの診断

**👩‍💻 ユーザー：** 「私、ちゃんと勉強したのに...何がダメだったんでしょう...」

**🐘 ガネーシャ：** 「お前は何も間違ってへんで。個人の知識としては完璧や」

**👩‍💻 ユーザー：** 「え？」

**🐘 ガネーシャ：** 「問題は『チームで統一するルール』がないことや。お前がどんなに正しく書いても、チーム全体で揃ってへんかったら意味がない」

**👩‍💻 ユーザー：** 「確かに...『こう書いてね』っていうルールがなかった...」

**🐘 ガネーシャ：** 「せや。実はこの問題、チーム開発では必ずぶち当たる壁なんや。ワシの教え子のベゾスくんも Amazon 立ち上げた時に同じこと言うとったわ」

**👩‍💻 ユーザー：** 「ベゾスさんが...？」

**🐘 ガネーシャ：** 「『エラーメッセージがバラバラで、カスタマーサポートが大変や』ってな」

**👩‍💻 ユーザー：** 「...本当ですか？」

**🐘 ガネーシャ：** 「...まぁ細かいことはええやんけ！今日は『チーム全体で統一する仕組み』を教えたるで！」

---

## 📖 第 1 章：問題を整理する

### 🎭 個人の知識 vs チームのルール

**🐘 ガネーシャ：** 「まず、お前が学んできたことを振り返ってみよか」

**👩‍💻 ユーザー：** 「はい」

**🐘 ガネーシャ：** 「`APP_DEBUG` は？」

**👩‍💻 ユーザー：** 「本番では `false` にして、詳細なエラーを隠します！」

**🐘 ガネーシャ：** 「ステータスコードは？」

**👩‍💻 ユーザー：** 「404 は『見つからない』、422 は『バリデーションエラー』、500 は『サーバーエラー』！」

**🐘 ガネーシャ：** 「UseCase でのエラー処理は？」

**👩‍💻 ユーザー：** 「`throw` で例外を投げて、`try-catch` は書かない！Laravel に任せる！」

**🐘 ガネーシャ：** 「トランザクションは？」

**👩‍💻 ユーザー：** 「複数テーブルを更新する時は `DB::transaction()` で囲む！」

**🐘 ガネーシャ：** 「完璧や。お前個人としては何も間違ってへん」

**👩‍💻 ユーザー：** 「じゃあなんで...」

**🐘 ガネーシャ：** 「問題は、**チームで同じルールを共有してへん**ことや」

---

### 🔑 Laravel のデフォルトは便利やけど...

**🐘 ガネーシャ：** 「Laravel のデフォルト処理を思い出してみ」

**👩‍💻 ユーザー：** 「例外を自動でキャッチして、適切なステータスコードを返してくれる...便利ですよね」

**🐘 ガネーシャ：** 「せや。でも『便利』と『チーム開発で十分』は違うんや」

```
┌─────────────────────────────────────────────────────────────┐
│              Laravel デフォルトの限界                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【便利なところ】                                            │
│  ✅ 例外を自動でキャッチしてくれる                          │
│  ✅ 適切なステータスコードを返してくれる                    │
│  ✅ APP_DEBUG=true なら詳細を表示してくれる                 │
│                                                             │
│  【チーム開発で足りないところ】                              │
│  ❌ メッセージが英語で不親切                                │
│  ❌ レスポンス形式が決まってない（各自が自由に書く）        │
│  ❌ エラーを追跡する仕組みがない                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「個人開発なら十分だけど、チームだと足りない...」

**🐘 ガネーシャ：** 「せや。だから『**チーム全員が同じ形式で書く仕組み**』を作るんや」

---

### 🎯 今日のゴール

**🐘 ガネーシャ：** 「今日教えるのは、この 3 つや」

**👩‍💻 ユーザー：** 「何ですか？」

**🐘 ガネーシャ：** 「1 つ目、**ApiResponse**。レスポンス形式を統一するクラスや。これを使えば、誰が書いても同じ形式になる」

**👩‍💻 ユーザー：** 「なるほど」

**🐘 ガネーシャ：** 「2 つ目、**ApiExceptionHandler**。例外を HTTP レスポンスに変換するクラスや。例外の種類に応じて、適切なメッセージを返してくれる」

**👩‍💻 ユーザー：** 「例外を一括で管理する...」

**🐘 ガネーシャ：** 「3 つ目、**request_id**。各リクエストに一意の ID を付けて、ログと照合できるようにする」

**👩‍💻 ユーザー：** 「それがあれば、どのリクエストでエラーが起きたか特定できる！」

**🐘 ガネーシャ：** 「せや！順番に見ていくで」

---

## 🚀 第 2 章：ApiResponse でレスポンス形式を統一

### 🎭 問題の再確認

**🐘 ガネーシャ：** 「まず、レスポンスがバラバラな問題から解決するで」

```php
// ❌ バラバラな例（再掲）

// Aさん
return response()->json(['error' => 'Not found'], 404);

// Bさん
return response()->json(['message' => 'データがありません', 'code' => 404], 404);

// Cさん
return response()->json(['success' => false, 'msg' => '見つかりません'], 404);
```

**👩‍💻 ユーザー：** 「フロントエンドは `error` を見ればいいのか、`message` を見ればいいのか、`msg` を見ればいいのか分からない...」

---

### 🔑 解決策：統一されたレスポンス形式

**🐘 ガネーシャ：** 「まず、『こういう形式で返す』っていうルールを決めるんや」

```
┌─────────────────────────────────────────────────────────────┐
│              統一されたレスポンス形式                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【成功時】                                                  │
│  {                                                          │
│    "success": true,                                         │
│    "message": "成功しました",                               │
│    "data": { ... }                                          │
│  }                                                          │
│                                                             │
│  【エラー時】                                                │
│  {                                                          │
│    "success": false,                                        │
│    "message": "エラーメッセージ",                           │
│    "request_id": "req_xxxxx"  ← エラー追跡用                │
│  }                                                          │
│                                                             │
│  【バリデーションエラー時】                                  │
│  {                                                          │
│    "success": false,                                        │
│    "message": "バリデーションエラー",                       │
│    "errors": {                                              │
│      "email": ["メールアドレスは必須です"]                  │
│    },                                                       │
│    "request_id": "req_xxxxx"                                │
│  }                                                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「`success` で成功/失敗が分かって、`message` でメッセージが取れる。シンプルですね！」

**🐘 ガネーシャ：** 「せや。フロントエンドは常に同じ形式を期待できるから、処理が書きやすくなるんや」

---

### 📝 ApiResponse クラスを作る

**🐘 ガネーシャ：** 「この形式を強制するために、専用のクラスを作るで」

```php
<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * 共通のレスポンス生成処理
     */
    private function respond(
        bool $success,
        string $message,
        $data,
        int $status,
        $errors = null,
        ?string $requestId = null
    ): JsonResponse {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // エラー時は request_id を追加（追跡用）
        if ($requestId !== null) {
            $response['request_id'] = $requestId;
        }

        return response()->json($response, $status);
    }

    /**
     * 成功レスポンス（200 OK）
     */
    public function success($data = null, string $message = '成功'): JsonResponse
    {
        return $this->respond(true, $message, $data, 200);
    }

    /**
     * 作成成功レスポンス（201 Created）
     */
    public function created($data = null, string $message = '作成しました'): JsonResponse
    {
        return $this->respond(true, $message, $data, 201);
    }

    /**
     * 認証エラー（401 Unauthorized）
     */
    public function unauthorized(
        string $message = '認証が必要です',
        ?string $requestId = null
    ): JsonResponse {
        return $this->respond(false, $message, null, 401, null, $requestId);
    }

    /**
     * 権限エラー（403 Forbidden）
     */
    public function forbidden(
        string $message = '権限がありません',
        ?string $requestId = null
    ): JsonResponse {
        return $this->respond(false, $message, null, 403, null, $requestId);
    }

    /**
     * 未検出エラー（404 Not Found）
     */
    public function notFound(
        string $message = '指定されたデータが見つかりません',
        ?string $requestId = null
    ): JsonResponse {
        return $this->respond(false, $message, null, 404, null, $requestId);
    }

    /**
     * バリデーションエラー（422 Unprocessable Entity）
     */
    public function validationError(
        string $message = 'バリデーションエラー',
        $errors = null,
        ?string $requestId = null
    ): JsonResponse {
        return $this->respond(false, $message, null, 422, $errors, $requestId);
    }

    /**
     * サーバーエラー（500 Internal Server Error）
     */
    public function serverError(
        string $message = 'サーバーエラー',
        ?string $requestId = null
    ): JsonResponse {
        return $this->respond(false, $message, null, 500, null, $requestId);
    }
}
```

**👩‍💻 ユーザー：** 「なるほど！メソッドを呼ぶだけで、統一された形式になるんですね」

---

### 🎯 ApiResponse の使い方

**🐘 ガネーシャ：** 「Controller での使い方を見せたるわ」

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;

class ApiController extends Controller
{
    protected ApiResponse $response;

    public function __construct()
    {
        $this->response = new ApiResponse();
    }
}
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;

class TaskController extends ApiController
{
    /**
     * タスク一覧取得
     */
    public function index()
    {
        $tasks = Task::all();

        // ✅ 統一された形式で返す
        return $this->response->success($tasks, 'タスク一覧を取得しました');
    }

    /**
     * タスク作成
     */
    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());

        // ✅ 201 で返す
        return $this->response->created($task, 'タスクを作成しました');
    }
}
```

**👩‍💻 ユーザー：** 「`ApiController` を継承すれば、どの Controller でも同じ形式で返せる！」

**🐘 ガネーシャ：** 「せや！これでチームの誰が書いても、レスポンス形式は統一されるんや」

---

### 📊 Before / After

```php
// ❌ Before：人によってバラバラ
return response()->json(['error' => 'Not found'], 404);
return response()->json(['message' => 'データがありません'], 404);
return response()->json(['success' => false, 'msg' => '見つかりません'], 404);

// ✅ After：統一された形式
return $this->response->notFound('タスクが見つかりません');
```

```json
// ✅ After のレスポンス（常にこの形式）
{
    "success": false,
    "message": "タスクが見つかりません",
    "request_id": "req_67890abcdef12345"
}
```

---

## 🔍 第 3 章：ApiExceptionHandler で例外を一括管理

### 🎭 次の問題

**🐘 ガネーシャ：** 「ApiResponse で形式は統一できた。でも、まだ問題があるで」

**👩‍💻 ユーザー：** 「何ですか？」

**🐘 ガネーシャ：** 「Controller で try-catch を書かない場合、Laravel のデフォルト処理になってしまうやろ？」

```php
public function show(Task $task)
{
    // Route Model Binding で 404 が発生
    // → Laravel のデフォルト処理
    // → {"message": "Not Found"} が返る（統一されてない！）

    return $this->response->success($task);
}
```

**👩‍💻 ユーザー：** 「あ...ApiResponse を使っても、例外が発生したら意味がない...」

**🐘 ガネーシャ：** 「せや。だから『例外が発生した時も、統一された形式で返す』仕組みが必要なんや」

---

### 🔑 解決策：例外ハンドラーを作る

**🐘 ガネーシャ：** 「ApiExceptionHandler っていうクラスを作って、全ての例外を一括で処理するんや」

```
┌─────────────────────────────────────────────────────────────┐
│              例外処理の流れ                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Before：Laravel デフォルト】                             │
│  例外発生 → Laravel の Handler → {"message": "Not Found"}   │
│                                                             │
│  【After：ApiExceptionHandler】                             │
│  例外発生 → ApiExceptionHandler → ApiResponse               │
│         → {"success": false, "message": "...", ...}        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📝 ApiExceptionHandler クラス

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
use Throwable;

class ApiExceptionHandler
{
    private ApiResponse $response;

    public function __construct()
    {
        $this->response = new ApiResponse();
    }

    /**
     * API例外を処理する
     */
    public function handle(Throwable $exception, Request $request): ?JsonResponse
    {
        // API以外のリクエストは処理しない
        if (!$request->is('api/*')) {
            return null;
        }

        // リクエストIDを生成（エラー追跡用）
        $requestId = $request->header('X-Request-ID') ?? uniqid('req_', true);

        // 例外タイプに応じて処理を振り分け
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

            // 500 Server Error（その他全て）
            default => $this->handleServerError($exception, $requestId),
        };
    }

    /**
     * 404エラー
     */
    private function handleNotFound(string $requestId): JsonResponse
    {
        return $this->response->notFound('指定されたデータが見つかりません', $requestId);
    }

    /**
     * 認証エラー（401）
     */
    private function handleAuthentication(string $requestId): JsonResponse
    {
        return $this->response->unauthorized('認証が必要です', $requestId);
    }

    /**
     * 権限エラー（403）
     */
    private function handleForbidden(
        AuthorizationException $exception,
        string $requestId
    ): JsonResponse {
        return $this->response->forbidden($exception->getMessage(), $requestId);
    }

    /**
     * バリデーションエラー（422）
     */
    private function handleValidation(
        ValidationException $exception,
        string $requestId
    ): JsonResponse {
        return $this->response->validationError(
            'バリデーションエラー',
            $exception->errors(),
            $requestId
        );
    }

    /**
     * サーバーエラー（500）
     */
    private function handleServerError(
        Throwable $exception,
        string $requestId
    ): JsonResponse {
        // 本番では固定メッセージ、開発中は詳細表示
        $message = config('app.debug')
            ? $exception->getMessage()
            : 'サーバーエラーが発生しました';

        return $this->response->serverError($message, $requestId);
    }
}
```

**👩‍💻 ユーザー：** 「例外の種類を見て、適切なレスポンスを返すんですね！」

**🐘 ガネーシャ：** 「せや。`match` 式で振り分けとるのがポイントや。PHP 8 の機能やで」

---

### 🎯 match 式の解説

**👩‍💻 ユーザー：** 「`match (true)` って何ですか？」

**🐘 ガネーシャ：** 「ええ質問や。これは『最初に true になった条件を実行する』っていう書き方や」

```php
// match (true) の仕組み
return match (true) {
    $exception instanceof NotFoundHttpException => $this->handleNotFound(),
    $exception instanceof ValidationException => $this->handleValidation(),
    default => $this->handleServerError(),
};

// これは以下と同じ意味
if ($exception instanceof NotFoundHttpException) {
    return $this->handleNotFound();
} elseif ($exception instanceof ValidationException) {
    return $this->handleValidation();
} else {
    return $this->handleServerError();
}
```

**👩‍💻 ユーザー：** 「if-elseif をスッキリ書けるんですね！」

**🐘 ガネーシャ：** 「せや。例外の振り分けみたいに条件が多い時に便利やで」

---

### 📝 bootstrap/app.php での登録

**🐘 ガネーシャ：** 「作った ApiExceptionHandler を Laravel に登録するで」

```php
<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\ApiExceptionHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 👇 ここで ApiExceptionHandler を登録
        $exceptions->render(function (Throwable $e, $request) {
            $handler = new ApiExceptionHandler();
            return $handler->handle($e, $request);
        });
    })
    ->create();
```

**👩‍💻 ユーザー：** 「`withExceptions` で登録するんですね」

**🐘 ガネーシャ：** 「せや。これで全ての API リクエストの例外が、ApiExceptionHandler を通るようになるんや」

---

### 📊 処理の流れ

```
┌─────────────────────────────────────────────────────────────┐
│                    全体の処理フロー                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  リクエスト                                                 │
│      ↓                                                      │
│  Controller                                                 │
│      ↓                                                      │
│  UseCase（throw で例外を投げる）                            │
│      ↓                                                      │
│  💥 例外発生！                                              │
│      ↓                                                      │
│  bootstrap/app.php の withExceptions                        │
│      ↓                                                      │
│  ApiExceptionHandler::handle()                              │
│      ↓                                                      │
│  例外の種類を判定（match 式）                               │
│      ↓                                                      │
│  ApiResponse で統一されたレスポンスを生成                   │
│      ↓                                                      │
│  クライアントにレスポンス                                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔍 第 4 章：request_id でエラーを追跡可能に

### 🎭 問題の再確認

**🐘 ガネーシャ：** 「3 つ目の問題、『本番でエラー調査ができない』を解決するで」

**👩‍💻 ユーザー：** 「同じエラーがたくさんあって、どれがどのリクエストか分からない問題ですね」

**🐘 ガネーシャ：** 「せや。これを解決するのが `request_id` や」

---

### 🔑 request_id とは

```
┌─────────────────────────────────────────────────────────────┐
│                    request_id の仕組み                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【仕組み】                                                  │
│  各リクエストに一意のIDを付ける                             │
│  → エラーレスポンスに含める                                 │
│  → ログにも記録する                                        │
│  → ユーザーから問い合わせが来たら、IDで特定できる          │
│                                                             │
│  【例】                                                      │
│  リクエストID: req_67890abcdef12345                         │
│                                                             │
│  エラーレスポンス:                                          │
│  {                                                          │
│    "success": false,                                        │
│    "message": "サーバーエラーが発生しました",               │
│    "request_id": "req_67890abcdef12345"  ← これで追跡！     │
│  }                                                          │
│                                                             │
│  ログ:                                                      │
│  [2024-01-15 10:23:45] ERROR: ... [req_67890abcdef12345]    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「ユーザーに『エラー画面の request_id を教えてください』って聞けば、ログから特定できる！」

**🐘 ガネーシャ：** 「せや！これがあるとエラー調査が格段に楽になるんや」

---

### 📝 request_id の生成

**🐘 ガネーシャ：** 「ApiExceptionHandler の中で、こうやって生成しとるで」

```php
public function handle(Throwable $exception, Request $request): ?JsonResponse
{
    // リクエストIDを生成（エラー追跡用）
    // ① クライアントが X-Request-ID ヘッダーを送ってきたらそれを使う
    // ② なければ自動生成
    $requestId = $request->header('X-Request-ID') ?? uniqid('req_', true);

    // この requestId をエラーレスポンスに含める
    return match (true) {
        // ...
    };
}
```

**👩‍💻 ユーザー：** 「`uniqid` で一意の ID を作るんですね」

**🐘 ガネーシャ：** 「せや。`req_` っていう接頭辞を付けて、一目でリクエスト ID って分かるようにしとるんや」

---

### 🎯 ログにも request_id を記録

**🐘 ガネーシャ：** 「サーバーエラーの時は、ログにも request_id を含めるとええで」

```php
private function handleServerError(
    Throwable $exception,
    string $requestId
): JsonResponse {
    // ログに記録（request_id 付き）
    Log::error($exception->getMessage(), [
        'request_id' => $requestId,
        'exception' => $exception,
        'trace' => $exception->getTraceAsString(),
    ]);

    $message = config('app.debug')
        ? $exception->getMessage()
        : 'サーバーエラーが発生しました';

    return $this->response->serverError($message, $requestId);
}
```

---

### 📊 エラー調査の流れ

```
┌─────────────────────────────────────────────────────────────┐
│                  エラー調査の流れ                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ ユーザーから問い合わせ                                 │
│     「エラーが出ました。request_id は req_xxxxx です」      │
│                                                             │
│  2️⃣ ログを検索                                             │
│     $ grep "req_xxxxx" storage/logs/laravel.log            │
│                                                             │
│  3️⃣ エラーの詳細が分かる                                   │
│     [2024-01-15 10:23:45] ERROR: Attempt to read property   │
│     on null {"request_id": "req_xxxxx", "trace": "..."}    │
│                                                             │
│  4️⃣ 原因を特定して修正                                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「request_id があるだけで、調査がすごく楽になりますね！」

---

## 📊 第 5 章：全体像を見る

### 🎭 ファイル構成

**🐘 ガネーシャ：** 「ここまでの内容を整理するで」

```
app/
├── Exceptions/
│   └── ApiExceptionHandler.php    ← 例外を一括管理
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── ApiController.php  ← 共通の基底クラス
│   │       └── TaskController.php
│   │
│   └── Responses/
│       └── ApiResponse.php        ← レスポンス形式を統一
│
bootstrap/
└── app.php                        ← ApiExceptionHandler を登録
```

---

### 🔑 各クラスの役割

```
┌─────────────────────────────────────────────────────────────┐
│                    各クラスの役割                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ApiResponse                                                │
│  ──────────                                                 │
│  役割：レスポンス形式を統一                                 │
│  使う場所：Controller                                       │
│  例：$this->response->success($data)                        │
│                                                             │
│  ApiExceptionHandler                                        │
│  ────────────────────                                       │
│  役割：例外を HTTP レスポンスに変換                         │
│  使う場所：bootstrap/app.php で登録                         │
│  動作：例外発生時に自動で呼ばれる                           │
│                                                             │
│  ApiController                                              │
│  ─────────────                                              │
│  役割：API Controller の共通基底クラス                      │
│  機能：ApiResponse をインスタンス化                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 処理フローの全体像

```
┌─────────────────────────────────────────────────────────────┐
│                    処理フローの全体像                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【正常系】                                                  │
│  Request → Controller → UseCase → Controller                │
│                                       ↓                     │
│                              ApiResponse::success()         │
│                                       ↓                     │
│                              {"success": true, ...}         │
│                                                             │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  【異常系（例外発生）】                                      │
│  Request → Controller → UseCase                             │
│                            ↓                                │
│                     💥 throw new Exception()                │
│                            ↓                                │
│                   ApiExceptionHandler                       │
│                            ↓                                │
│                      例外の種類を判定                       │
│                            ↓                                │
│                    ApiResponse::xxxError()                  │
│                            ↓                                │
│                {"success": false, "request_id": ...}        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「正常系は Controller で ApiResponse を使って、異常系は ApiExceptionHandler が自動で処理してくれるんですね！」

**🐘 ガネーシャ：** 「せや！どっちのパターンでも、レスポンス形式は統一されるんや」

---

## 🎯 第 6 章：実践 - 既存コードに適用

### 🎭 Before / After で見る改善

**🐘 ガネーシャ：** 「実際のコードがどう変わるか見てみよか」

---

### ❌ Before：バラバラなコード

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::all();

        // 人によって書き方が違う
        return response()->json(['data' => $tasks]);
    }

    public function store(Request $request)
    {
        // バリデーションも人によって違う
        $validated = $request->validate([
            'title' => 'required|max:255',
        ]);

        $task = Task::create($validated);

        return response()->json([
            'message' => 'Created',
            'task' => $task
        ], 201);
    }

    public function show($id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($task);
    }

    public function complete($id)
    {
        try {
            $task = Task::findOrFail($id);

            DB::transaction(function () use ($task) {
                $task->update(['status' => 'done']);
                TaskHistory::create([...]);
            });

            return response()->json(['status' => 'ok']);

        } catch (Exception $e) {
            // エラー処理もバラバラ
            return response()->json([
                'error' => true,
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
```

---

### ✅ After：統一されたコード

```php
<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\UseCases\Task\CompleteTaskUseCase;
use App\Http\Requests\StoreTaskRequest;

class TaskController extends ApiController  // ← ApiController を継承
{
    public function __construct(
        private CompleteTaskUseCase $completeTaskUseCase
    ) {}

    public function index()
    {
        $tasks = Task::all();

        // ✅ 統一された形式
        return $this->response->success($tasks, 'タスク一覧を取得しました');
    }

    public function store(StoreTaskRequest $request)  // ← FormRequest でバリデーション
    {
        $task = Task::create($request->validated());

        // ✅ 201 で返す
        return $this->response->created($task, 'タスクを作成しました');
    }

    public function show(Task $task)  // ← Route Model Binding
    {
        // 404 は ApiExceptionHandler が自動処理
        return $this->response->success($task, 'タスクを取得しました');
    }

    public function complete(Task $task)
    {
        // UseCase に処理を委譲
        // 例外は ApiExceptionHandler が自動処理
        $task = $this->completeTaskUseCase->execute($task, auth()->user());

        return $this->response->success($task, 'タスクを完了しました');
    }
}
```

---

### 📊 改善ポイント

| 項目             | Before           | After                      |
| ---------------- | ---------------- | -------------------------- |
| レスポンス形式   | バラバラ         | **統一** ✅                |
| エラー処理       | 各所で try-catch | **一括管理** ✅            |
| バリデーション   | Controller 内    | **FormRequest** ✅         |
| 404 処理         | 手動チェック     | **Route Model Binding** ✅ |
| ビジネスロジック | Controller 内    | **UseCase** ✅             |
| request_id       | なし             | **あり** ✅                |

---

### 🎯 UseCase のコード

**🐘 ガネーシャ：** 「UseCase はこんな感じや。Lesson 6-5, 6-6 で学んだ内容やな」

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
        // ビジネスルールチェック
        if ($task->status !== 'doing') {
            // throw するだけ！
            // ApiExceptionHandler が 500 エラーとして処理
            throw new \Exception('作業中のタスクのみ完了できます');
        }

        // トランザクションで複数テーブルを更新
        return DB::transaction(function () use ($task, $user) {
            $task->update(['status' => 'done']);

            TaskHistory::create([
                'task_id' => $task->id,
                'action' => 'completed',
                'user_id' => $user->id,
            ]);

            return $task->fresh();
        });
    }
}
```

**👩‍💻 ユーザー：** 「UseCase では throw するだけで、try-catch は書かないんですね！」

**🐘 ガネーシャ：** 「せや！Lesson 6-5 で学んだ通りや。例外は上に伝播させて、ApiExceptionHandler に任せるんや」

---

## 📝 第 7 章：まとめ

**🐘 ガネーシャ：** 「今日学んだことをまとめるで」

```
┌─────────────────────────────────────────────────────────────┐
│                      今日の学び                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【3つの問題】                                               │
│  😰 デフォルトのメッセージが不親切                          │
│  😰 レスポンス形式がバラバラ                                │
│  😰 本番でエラー調査ができない                              │
│                                                             │
│  【3つの解決策】                                             │
│  ✅ ApiResponse → レスポンス形式を統一                      │
│  ✅ ApiExceptionHandler → 例外を一括管理                    │
│  ✅ request_id → エラーを追跡可能に                         │
│                                                             │
│  【設計のポイント】                                          │
│  ・Controller は ApiController を継承                       │
│  ・正常系は ApiResponse で返す                              │
│  ・異常系は ApiExceptionHandler が自動処理                  │
│  ・UseCase は throw するだけ（try-catch 不要）              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 Lesson 6-7 の総復習

```
┌─────────────────────────────────────────────────────────────┐
│                  エラー処理の全体像                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Lesson 6-1: ステータスコード                               │
│  └── 200, 400, 404, 422, 500 の意味                        │
│                                                             │
│  Lesson 6-2: Laravel のデフォルト処理                       │
│  └── Route Model Binding, FormRequest, APP_DEBUG           │
│                                                             │
│  Lesson 6-3: 自分で書くエラーハンドリング                   │
│  └── 403, 409 を返す場面                                   │
│                                                             │
│  Lesson 6-4: try-catch の基本                                 │
│  └── 例外をキャッチして処理する                            │
│                                                             │
│  Lesson 6-5: UseCase での throw                               │
│  └── 例外は throw だけ、try-catch は書かない               │
│                                                             │
│  Lesson 6-6: トランザクション                                 │
│  └── 複数テーブル更新を安全に                              │
│                                                             │
│  Lesson 6-7: 例外処理の全体設計（今日）                      │
│  └── ApiResponse + ApiExceptionHandler + request_id        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「全部つながってるんですね！」

**🐘 ガネーシャ：** 「せや。一つ一つの知識が組み合わさって、チーム開発で使える設計になるんや」

---

## 🐘 ガネーシャの最後のメッセージ

**🐘 ガネーシャ：** 「今日のポイントはこれや」

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│     レスポンス形式は ApiResponse で統一！                   │
│                                                             │
│     例外処理は ApiExceptionHandler で一括管理！             │
│                                                             │
│     request_id でエラーを追跡可能に！                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘 ガネーシャ：** 「ワシの教え子のラリー・ペイジくんも言うとったわ。『システムは統一性が命や』って」

**👩‍💻 ユーザー：** 「Google 創業者の...本当ですか？」

**🐘 ガネーシャ：** 「...まぁ細かいことはええやんけ！とにかく、チーム開発ではみんなが同じルールで書くことが大事なんや。今日学んだ設計を使えば、誰が書いても統一されたエラー処理になるで」

**👩‍💻 ユーザー：** 「はい！これでチーム開発も安心です！」

**🐘 ガネーシャ：** 「よっしゃ！さすガネーシャや！🐘✨」

---

## 📚 次回予告

**🐘 ガネーシャ：** 「今日の設計で、ほとんどのエラーは対応できるようになったな」

**👩‍💻 ユーザー：** 「はい！でも一つ気になることがあって...」

**🐘 ガネーシャ：** 「何や？」

**👩‍💻 ユーザー：** 「UseCase で throw new Exception() すると、全部 500 エラーになりますよね。409 Conflict を返したい時はどうすれば...」

**🐘 ガネーシャ：** 「ええ質問や！次回は『カスタム例外』を教えたるわ。自分で例外クラスを作って、409 とかのステータスコードを指定できるようになるで」

**👩‍💻 ユーザー：** 「楽しみです！」

### 振り返り

| Lesson      | タイトル                     | 学んだこと                                         |
| ----------- | ---------------------------- | -------------------------------------------------- |
| 6-1         | ステータスコードとは         | 200, 400, 404, 500 などの意味                      |
| 6-2         | Laravel が自動でやること     | Route Model Binding, FormRequest, APP_DEBUG        |
| 6-3         | 自分で書くエラーハンドリング | 403, 409 の実装                                    |
| 6-4         | try-catch の基本             | try-catch の概念、Laravel の自動処理               |
| 6-5         | UseCase での throw           | return vs throw、UseCase では throw だけ           |
| 6-6         | トランザクション             | DB::transaction() で複数更新を安全に               |
| **6-7**     | **例外処理の全体設計**       | **ApiResponse + ApiExceptionHandler + request_id** |
| 6-8（次回） | カスタム例外                 | 409 を返せる ConflictException の作成              |

**🐘 ガネーシャ：** 「ほな、次回もよろしくな！さすガネーシャや！🐘✨」
