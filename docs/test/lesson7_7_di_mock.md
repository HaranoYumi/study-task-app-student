# Lesson 7-7: DI（依存性注入）を理解しよう 🐘
## 〜テスト可能なコードの書き方〜

---

## 🌿 ブランチ切り替えと準備

課題に取り組む前に、リモートの全てのブランチを取得してから、Lesson 用のブランチに切り替えてください：

```bash
# リモートの全てのブランチ情報を取得
git fetch origin

# Lesson用のブランチに切り替え
git checkout lesson7-7

# リモートの最新状態に更新
git pull origin lesson7-7
```

**推奨：** 各 Lesson ごとに専用のブランチで作業することで、作業を整理しやすくなります。

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

## 🎭 プロローグ：テストできないコードがある？

**👩‍💻ユーザー：** 「ガネーシャさん、Lesson 7 でテストの書き方を学びました！」

**🐘ガネーシャ：** 「おお、よう頑張ったな。テストはバッチリか？」

**👩‍💻ユーザー：** 「はい！でも、新しい機能を作ろうとしたら、ちょっと困ったことが...」

**🐘ガネーシャ：** 「ほう、何や？」

**👩‍💻ユーザー：** 「タスク完了時に**通知を送る機能**を作りたいんですけど、テストってどうすればいいんですか？」

**🐘ガネーシャ：** 「ほう、通知機能か。ええな！でも、何が困っとるんや？」

**👩‍💻ユーザー：** 「えっと、メールを送る機能をテストしたいんですけど...テストを実行するたびに本物のメールが送られちゃいますよね？」

**🐘ガネーシャ：** 「**おっ、ええ疑問や！** そこに気づいたのは偉いで。実際、テストのたびに100通メール送信とか、迷惑極まりないわな」

---

### 🤔 今回の課題

```
┌─────────────────────────────────────────────────────────────┐
│                  今回の課題                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【やりたいこと】                                           │
│  タスク完了時に、プロジェクトメンバーに通知を送りたい       │
│                                                             │
│  【テストの問題】                                           │
│  😱 テストを実行するたびに本物のメールが送られる           │
│  😱 テストが遅くなる（メール送信に時間がかかる）           │
│  😱 外部サービス（メールサーバー）が落ちてるとテスト失敗   │
│                                                             │
│  【理想】                                                   │
│  ✅ テスト時は「通知が呼ばれたか」だけ確認したい           │
│  ✅ 本物のメール送信はしたくない                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「そうなんです...本番では本物を使いたいけど、テストの時だけ何とかしたいんです」

**🐘ガネーシャ：** 「せやな。実はな、そういう時のために**本物を偽物に差し替える**テクニックがあるんや」

**👩‍💻ユーザー：** 「偽物に差し替える...？」

**🐘ガネーシャ：** 「せや。**DI（依存性注入）**っちゅう仕組みを使うと、テスト時だけ本物を偽物に差し替えられるんや。今日はその仕組みを教えたるで！」

---

### 🎯 今日の目標

```
┌─────────────────────────────────────────────────────────────┐
│                  🎯 今日のゴール                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  DI（依存性注入）とは何か理解する                       │
│                                                             │
│  2️⃣  NotificationService を作成する                         │
│      → タスク完了時に通知を送る機能                        │
│                                                             │
│  3️⃣  テストで「偽物（Mock）」に差し替える方法を学ぶ         │
│      → 本物のメール送信なしでテストできる！                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📖 第1章：DI（依存性注入）って何？

### 🎭 まずは DI の基本ルールを覚えよう

**🐘ガネーシャ：** 「まず、DI が何か説明するで」

```
┌─────────────────────────────────────────────────────────────┐
│              DI（Dependency Injection）とは？                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「コンストラクタやメソッドの引数に型を書くと、              │
│    Laravel が自動でその型のインスタンスを作って渡してくれる」│
│                                                             │
│  自分で new しなくても、Laravel が用意してくれる！          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「ちょっと待ってください。**コンストラクタ**って何ですか？」

**🐘ガネーシャ：** 「おっ、そこから説明が必要やったか。ええ質問や！」

---

### 🤔 コンストラクタ（__construct）とは？

**🐘ガネーシャ：** 「コンストラクタっちゅうのは、**クラスのインスタンスが作られる時に最初に実行されるメソッド**のことや」

```php
<?php

class NotificationService
{
    // ✅ これがコンストラクタ
    // クラスが new される時に自動で呼ばれる
    public function __construct()
    {
        // 初期化処理をここに書く
    }
    
    public function notify(...): void
    {
        // ...
    }
}
```

**👩‍💻ユーザー：** 「`__construct` っていう名前のメソッドなんですね」

**🐘ガネーシャ：** 「せや。PHP では `__construct` っていう特別な名前のメソッドがコンストラクタになるんや」

---

#### 📝 コンストラクタの役割

```
┌─────────────────────────────────────────────────────────────┐
│              コンストラクタ（__construct）の役割             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【いつ実行される？】                                       │
│  new SomeClass() した瞬間に自動で実行される                │
│                                                             │
│  【何に使う？】                                             │
│  ・初期化処理                                               │
│  ・必要なオブジェクトを受け取る（← DI で重要！）           │
│                                                             │
│  【例】                                                     │
│  $service = new NotificationService();                      │
│  // ↑ この瞬間に __construct() が実行される                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

#### 📝 コンストラクタで引数を受け取る

**🐘ガネーシャ：** 「コンストラクタには引数を渡せるんや。これが DI の鍵になるで」

```php
<?php

class CompleteTaskUseCase
{
    // コンストラクタで NotificationService を受け取る
    public function __construct(
        private NotificationService $notificationService
    ) {}
    
    public function execute(Task $task, User $user): Task
    {
        // 受け取った $this->notificationService を使う
        $this->notificationService->notify(...);
        return $task;
    }
}
```

```php
// 普通に使う場合（自分で new する場合）
$service = new NotificationService();
$useCase = new CompleteTaskUseCase($service);  // ← コンストラクタに渡す
$useCase->execute($task, $user);
```

**👩‍💻ユーザー：** 「`new CompleteTaskUseCase($service)` で、コンストラクタに渡してるんですね！」

**🐘ガネーシャ：** 「せや！」

**👩‍💻ユーザー：** 「でも、わざわざコンストラクタに書く必要あるんですか？メソッドの引数に書いても DI できるんじゃ...」

**🐘ガネーシャ：** 「**ええ質問や！** 実は、どっちでも DI はできるんやけど、**場合によって違う**んや。順番に説明するで」

---

#### 🤔 まず Controller の場合を考えてみよう

**🐘ガネーシャ：** 「まず、Controller で UseCase を受け取る場合を見てみ」

```php
<?php

// ============================================
// パターン1: コンストラクタで受け取る
// ============================================
class TaskController extends Controller
{
    public function __construct(
        private CompleteTaskUseCase $completeTaskUseCase
    ) {}

    public function complete(Task $task): JsonResponse
    {
        $task = $this->completeTaskUseCase->execute($task, Auth::user());
        return response()->json(['data' => $task]);
    }
}

// ============================================
// パターン2: メソッド引数で受け取る
// ============================================
class TaskController extends Controller
{
    public function complete(Task $task, CompleteTaskUseCase $useCase): JsonResponse
    {
        $task = $useCase->execute($task, Auth::user());
        return response()->json(['data' => $task]);
    }
}
```

**👩‍💻ユーザー：** 「どっちでも動きそうですね」

**🐘ガネーシャ：** 「せや。実は Controller の場合は**どっちでも特に困らん**んや」

**👩‍💻ユーザー：** 「え！？どっちでもいいんですか？」

**🐘ガネーシャ：** 「なんでかっちゅうと、**Controller のメソッドを呼び出すのは Laravel**やからや」

**👩‍💻ユーザー：** 「どういうことですか？」

**🐘ガネーシャ：** 「お前、こんなコード書いたことあるか？」

```php
<?php

// ❌ こんなコード書いたことある？
$taskController = new TaskController($completeTaskUseCase);
$taskController->complete($task);
```

**👩‍💻ユーザー：** 「いえ、書いたことないです！」

**🐘ガネーシャ：** 「せやろ？**Controller は私たちが new しない**んや。HTTP リクエストが来たら、**Laravel が勝手に作って呼んでくれる**んやで」

```
┌─────────────────────────────────────────────────────────────┐
│              Controller の場合                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Controller のメソッドを呼び出すのは誰？                    │
│  → Laravel（フレームワーク）                               │
│                                                             │
│  API リクエスト                                             │
│       ↓                                                     │
│  Laravel が routes/api.php を見る                          │
│       ↓                                                     │
│  Laravel が Controller を new する（DI で自動生成）        │
│       ↓                                                     │
│  Laravel が Controller のメソッドを呼ぶ                    │
│       ↓                                                     │
│  引数に UseCase があれば、Laravel が自動で渡してくれる     │
│                                                             │
│  → 私たちが Controller を new することはない               │
│  → 私たちが Controller のメソッドを直接呼ぶこともない      │
│  → だからメソッド引数に書いても困らない                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

```php
<?php

// ============================================
// ❌ 私たちはこう書かない（Controller を直接 new しない）
// ============================================
$taskController = new TaskController($completeTaskUseCase);
$taskController->complete($task);

// ============================================
// ✅ 実際はこう（HTTP リクエストが来たら Laravel が自動でやってくれる）
// ============================================
// POST /api/tasks/5/complete
//     ↓
// Laravel「routes/api.php を見るで...」
// Laravel「TaskController の complete() を呼べばええんやな」
// Laravel「TaskController を new するで（DI で UseCase も自動注入）」
// Laravel「complete($task) を呼ぶで（$task も DI で自動取得）」
```

**👩‍💻ユーザー：** 「なるほど！Controller は Laravel が呼んでくれるから、引数が増えても私たちは困らないんですね」

**🐘ガネーシャ：** 「せや。ただ、コンストラクタに書く方が**依存関係が一目で分かる**し、**複数メソッドで同じ UseCase を使う時に便利**やから、コンストラクタに書くことが多いな」

---

#### 😱 でも UseCase 内のサービスは話が違う

**🐘ガネーシャ：** 「ところがな、**UseCase の中でサービスを受け取る場合は明確に困る**んや」

**👩‍💻ユーザー：** 「何が違うんですか？」

**🐘ガネーシャ：** 「**UseCase のメソッドを呼び出すのは私たちのコード**やからや。さっきの Controller と比べてみ」

```
┌─────────────────────────────────────────────────────────────┐
│              Controller vs UseCase の違い                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Controller】                                             │
│  ・呼び出すのは → Laravel                                  │
│  ・私たちは $taskController->complete() と書かない         │
│  ・だからメソッド引数が増えても私たちは困らない            │
│                                                             │
│  【UseCase】                                                │
│  ・呼び出すのは → 私たちのコード（Controller 内）          │
│  ・私たちが $this->useCase->execute() と書いてる！         │
│  ・だからメソッド引数が増えると私たちが困る                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あ！確かに Controller の中で `$this->completeTaskUseCase->execute()` って自分で書いてますね！」

**🐘ガネーシャ：** 「せや！**UseCase のメソッドは私たちが直接呼び出す**んや。ほなもしメソッド引数で NotificationService を受け取るようにしたらどうなるか見てみ」

```php
// CompleteTaskUseCase
<?php

// ============================================
// UseCase: メソッドの引数でNotificationServiceを受け取る（❌ 問題あり）
// ============================================
class CompleteTaskUseCase
{
    public function execute(
        Task $task, 
        User $user, 
        NotificationService $notificationService  // ← ここで受け取る
    ): Task {
        $notificationService->notify(...);
        return $task;
    }
}
```

```php
// TaskController.php
<?php

// Controller から呼び出す時...
// ⚠️ 私たちが直接 ->execute() と書いてる！
public function complete(
    Task $task, 
    NotificationService $notificationService  // ← Controller が知る必要ある
): JsonResponse {
    $task = $this->completeTaskUseCase->execute($task, $user, $notificationService);
    //      ↑ 私たちが書いてる                           ↑ 毎回渡す必要がある
    return response()->json(['data' => $task]);
}
```

**👩‍💻ユーザー：** 「あ！Controller が NotificationService のことを知らないといけなくなる！」

**🐘ガネーシャ：** 「**そこや！** でもな、問題はそれだけやないんや。**もっと困ること**があるで」

**👩‍💻ユーザー：** 「もっと困ること？」

---

#### 😱 メソッド引数にすると起きる問題

**🐘ガネーシャ：** 「UseCase に新しいサービスが必要になった時を考えてみ」

```php
// CompleteTaskUseCase
<?php

// ============================================
// 【変更前】NotificationService だけ使ってた
// ============================================
class CompleteTaskUseCase
{
    public function execute(
        Task $task, 
        User $user, 
        NotificationService $notificationService
    ): Task {
        // ...
    }
}

// ============================================
// 【変更後】LogService も必要になった！
// ============================================
class CompleteTaskUseCase
{
    public function execute(
        Task $task, 
        User $user, 
        NotificationService $notificationService,
        LogService $logService  // ← 追加！
    ): Task {
        // ...
    }
}
```

**👩‍💻ユーザー：** 「引数が増えましたね」

**🐘ガネーシャ：** 「問題はここからや。**呼び出してる側を全部修正せなアカン**んや」

```php
<?php

// ============================================
// 呼び出し箇所が1つだけならまだマシやけど...
// ============================================

// TaskController.php
$this->completeTaskUseCase->execute($task, $user, $notificationService, $logService);
//                                                                      ↑ 追加

// BatchController.php（バッチ処理でも使ってた）
$this->completeTaskUseCase->execute($task, $user, $notificationService, $logService);
//                                                                      ↑ 追加

// TaskApiController.php（別のAPIでも使ってた）
$this->completeTaskUseCase->execute($task, $user, $notificationService, $logService);
//                                                                      ↑ 追加

// TestCode.php（テストコードも全部！）
$useCase->execute($task, $user, $mockNotification, $mockLog);
//                                                 ↑ 追加

// 😱 呼び出してる箇所、全部修正が必要！！
```

**👩‍💻ユーザー：** 「うわ...UseCase を使ってる場所、全部直さないといけないんですか！？」

**🐘ガネーシャ：** 「せや。これが**保守性の問題**や。UseCase の内部実装を変えただけやのに、呼び出し側を全部修正せなアカン」

---

#### ✅ コンストラクタなら影響なし

**🐘ガネーシャ：** 「コンストラクタで受け取る場合を見てみ」

```php
// CompleteTaskUseCase
<?php

// ============================================
// 【変更前】
// ============================================
class CompleteTaskUseCase
{
    public function __construct(
        private NotificationService $notificationService
    ) {}
    
    public function execute(Task $task, User $user): Task
    {
        // ...
    }
}

// ============================================
// 【変更後】LogService も必要になった！
// ============================================
class CompleteTaskUseCase
{
    public function __construct(
        private NotificationService $notificationService,
        private LogService $logService  // ← 追加！
    ) {}
    
    public function execute(Task $task, User $user): Task  // ← 変わらない！
    {
        // ...
    }
}
```

```php
<?php

// ============================================
// 呼び出し側は...何も変更しなくていい！
// ============================================

// TaskController.php
$this->completeTaskUseCase->execute($task, $user);  // ← そのまま！

// BatchController.php
$this->completeTaskUseCase->execute($task, $user);  // ← そのまま！

// TaskApiController.php
$this->completeTaskUseCase->execute($task, $user);  // ← そのまま！

// ✅ Laravel が自動で LogService を注入してくれる
// ✅ 呼び出し側は UseCase の内部変更を知らなくていい
```

**👩‍💻ユーザー：** 「すごい！呼び出し側は何も変更しなくていいんですね！」

**🐘ガネーシャ：** 「せや！コンストラクタで受け取ると、**UseCase の内部変更が呼び出し側に影響しない**んや。これが保守性っちゅうもんや」

---

#### 📊 まとめ

```
┌─────────────────────────────────────────────────────────────┐
│              コンストラクタ vs メソッド引数                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Controller で UseCase を受け取る場合】                   │
│  → コンストラクタでもメソッド引数でも、どちらでも困らない  │
│  → Laravel が呼び出すから、引数が増えても影響なし          │
│  → ただし、コンストラクタの方が依存関係が分かりやすい      │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【UseCase でサービスを受け取る場合】                       │
│  → コンストラクタで受け取るべき（✅ 推奨）                 │
│  → メソッド引数だと、呼び出し側（私たちのコード）が困る    │
│  → 引数が増えると、呼び出し箇所を全部修正（❌ 保守性低い）│
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【結論】                                                   │
│  コンストラクタ: サービスなど「常に同じもの」を受け取る    │
│  メソッド引数: Task や User など「毎回変わるもの」を受け取る│
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！NotificationService は常に同じだからコンストラクタ、Task や User は毎回変わるからメソッド引数なんですね！」

**🐘ガネーシャ：** 「完璧や！Laravel を使うと、**コンストラクタの new を自分で書かなくていい**んや。Laravel が自動でやってくれる」

**👩‍💻ユーザー：** 「それが DI なんですね！」

**🐘ガネーシャ：** 「その通りや！実はな、お前は普段から DI を使っとるんやで」

**👩‍💻ユーザー：** 「え！？私が！？」

---

### 🤔 身近な例：Request と Task

**🐘ガネーシャ：** 「普段使っている `Request` や `Task` も DI の仕組みで渡されとるんやで」

```php
<?php

class TaskController extends Controller
{
    // ✅ Request は Laravel が自動で渡してくれる（DI）
    public function store(Request $request): JsonResponse
    {
        $title = $request->input('title');
        // ...
    }

    // ✅ Task も Laravel が自動で渡してくれる（DI + Route Model Binding）
    // URL: /api/tasks/5 → id=5 の Task を自動取得
    public function show(Task $task): JsonResponse
    {
        return response()->json(['data' => $task]);
    }
}
```

**👩‍💻ユーザー：** 「あ！確かに `new Request()` なんて書いたことないです！」

**🐘ガネーシャ：** 「せやろ？引数に型を書くだけで、Laravel が自動で渡してくれるんや」

```
┌─────────────────────────────────────────────────────────────┐
│              なぜ new しなくていいのか？                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  // ❌ こう書かなくていい                                   │
│  public function store(): JsonResponse                      │
│  {                                                          │
│      $request = new Request();  // 自分で作る必要なし！     │
│  }                                                          │
│                                                             │
│  // ✅ 引数に型を書くだけで Laravel が渡してくれる          │
│  public function store(Request $request): JsonResponse      │
│  {                                                          │
│      // $request は Laravel が用意してくれたもの            │
│  }                                                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「便利ですね！でも、なんで Laravel がやってくれるんですか？」

**🐘ガネーシャ：** 「それを理解するには、**DI コンテナ**っちゅう仕組みを知る必要があるで」

---

### 📊 DI コンテナ（サービスコンテナ）とは？

**🐘ガネーシャ：** 「Laravel には**DI コンテナ**っちゅうものがあるんや。別名『サービスコンテナ』とも呼ばれるで」

```
┌─────────────────────────────────────────────────────────────┐
│              DI コンテナ（サービスコンテナ）                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「この型が必要な時は、これを渡せ」という対応表             │
│                                                             │
│  ┌─────────────────────────────────────────────────┐        │
│  │  型                    │  渡すもの              │        │
│  ├─────────────────────────────────────────────────┤        │
│  │  Request               │  現在のリクエスト情報  │        │
│  │  User（引数）          │  認証済みユーザー      │        │
│  │  Task（ルート引数）    │  URLのIDから取得       │        │
│  │  NotificationService   │  ???（設定次第）       │        │
│  └─────────────────────────────────────────────────┘        │
│                                                             │
│  Laravel はこの「対応表」を見て、何を渡すか決める           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「対応表があるんですね！」

**🐘ガネーシャ：** 「せや。この対応表を**DI コンテナ**って呼ぶんや。Laravel が『何を渡すか』を決める時に、このコンテナを見るんやで」

---

### 📝 Laravel が「何を渡すか」決める3つのパターン

**🐘ガネーシャ：** 「Laravel が何を渡すか決めるパターンは3つあるで」

---

#### 1️⃣ デフォルト（何も登録しない場合）

```php
// NotificationService は特に登録されていない
public function __construct(
    private NotificationService $service
) {}

// Laravel の動き:
// 「NotificationService が必要やな」
// 「特に登録されてないから、new NotificationService() するわ」
```

**🐘ガネーシャ：** 「何も設定してないと、Laravel は勝手に `new` して渡してくれるんや」

---

#### 2️⃣ AppServiceProvider に bind() で登録した場合

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        NotificationServiceInterface::class,  // この型が必要な時は
        NotificationService::class            // こっちを作れ
    );
}
```

```php
public function __construct(
    private NotificationServiceInterface $service
) {}

// Laravel の動き:
// 「NotificationServiceInterface が必要やな」
// 「AppServiceProvider に登録されてる！」
// 「NotificationService を作れって書いてあるな」
// 「ほな new NotificationService() するわ」
```

**🐘ガネーシャ：** 「Interface を使う場合は、『この Interface には、この実装を使え』って設定するんや。これは次回のレッスンで詳しくやるで」

---

#### 3️⃣ テストで instance() した場合

```php
// テストコード
$mock = Mockery::mock(NotificationService::class);
$this->app->instance(NotificationService::class, $mock);
```

```php
public function __construct(
    private NotificationService $service
) {}

// Laravel の動き:
// 「NotificationService が必要やな」
// 「instance() で登録されてる！」
// 「すでに作られたオブジェクト（$mock）があるな」
// 「ほなそれをそのまま渡すわ」
```

**👩‍💻ユーザー：** 「`instance()` を使うと、Laravel が渡すものを変えられるんですね！」

**🐘ガネーシャ：** 「**そこや！** テストで Mock に差し替える時はこれを使うんや！」

---

### 📊 優先順位

```
┌─────────────────────────────────────────────────────────────┐
│              Laravel が「何を渡すか」決める優先順位          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ instance() で登録されてる？ → それを使う（最優先）     │
│         ↓ なければ                                          │
│  2️⃣ bind() で登録されてる？ → 指定されたクラスを new      │
│         ↓ なければ                                          │
│  3️⃣ 何も登録されてない → その型をそのまま new             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「`instance()` が最優先やから、テストで Mock を登録すると、本物より優先されるんや」

---

### 📊 具体例で整理

| 型 | 登録状況 | Laravel が渡すもの |
|----|----------|-------------------|
| `Request` | Laravel がデフォルト登録済み | 現在のリクエスト情報 |
| `Task`（ルート引数） | Laravel がデフォルト登録済み | URL の ID から自動取得 |
| `NotificationService` | 何も登録なし | `new NotificationService()` |
| `NotificationService` | `instance($mock)` で登録 | `$mock` そのもの |

**👩‍💻ユーザー：** 「なるほど！テストで `instance()` を使えば、Mock が渡されるようになるんですね！」

---

## 📖 第2章：なぜ DI を使うと Mock が可能になるのか？

### 🎭 ❌ DI を使わない場合（差し替え不可能）

**🐘ガネーシャ：** 「ここが一番大事なポイントや。**なぜ DI を使わないとテストできないのか**を説明するで」

**👩‍💻ユーザー：** 「はい！」

**🐘ガネーシャ：** 「まず、DI を使わないダメな例を見てみ」

```php
<?php

class CompleteTaskUseCase
{
    public function execute(Task $task, User $user): Task
    {
        // ❌ クラス内で直接 new している
        $notificationService = new NotificationService();
        
        $task->status = 'done';
        $task->save();
        
        // この NotificationService は「本物」しかありえない
        $notificationService->notify('task_completed', $user, [...]);
        
        return $task;
    }
}
```

**👩‍💻ユーザー：** 「普通に `new` してますね」

**🐘ガネーシャ：** 「せや。これをテストしようとするとどうなるか見てみ」

```php
<?php
// テストコード

public function test_タスク完了(): void
{
    $mock = Mockery::mock(NotificationService::class);
    $mock->shouldReceive('notify')->once();
    
    // 😢 DI コンテナに登録しても...
    $this->app->instance(NotificationService::class, $mock);
    
    // 😢 UseCase の中で new NotificationService() してるから
    // DI コンテナは使われない！
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/tasks/{$task->id}/complete");
    
    // ❌ 本物の NotificationService が動いてしまう
    // ❌ Mock は完全に無視される
}
```

**👩‍💻ユーザー：** 「あっ！UseCase の中で `new` してるから、DI コンテナに登録しても意味がないんですね！」

**🐘ガネーシャ：** 「**その通りや！** `new` は『自分で作る』っちゅう意味やから、Laravel に聞かずに勝手に作っちゃうんや。だから Mock が無視されるんや」

---

### 🎭 ✅ DI を使う場合（差し替え可能）

**🐘ガネーシャ：** 「ほな、DI を使った正しい書き方を見てみ」

```php
<?php

class CompleteTaskUseCase
{
    // ✅ コンストラクタで外から受け取る（DI）
    public function __construct(
        private NotificationService $notificationService
    ) {}
    
    public function execute(Task $task, User $user): Task
    {
        $task->status = 'done';
        $task->save();
        
        // ✅ 外から渡された $this->notificationService を使う
        // → 本物でも Mock でも、渡されたものが動く
        $this->notificationService->notify('task_completed', $user, [...]);
        
        return $task;
    }
}
```

**👩‍💻ユーザー：** 「コンストラクタで受け取ってる！`new` してないですね」

**🐘ガネーシャ：** 「せや！これならテストで Mock に差し替えられるで」

```php
<?php
// テストコード

public function test_タスク完了(): void
{
    $mock = Mockery::mock(NotificationService::class);
    $mock->shouldReceive('notify')->once();
    
    // ✅ DI コンテナに登録
    $this->app->instance(NotificationService::class, $mock);
    
    // ✅ API を呼ぶと、Laravel が UseCase を作る時に
    //    DI コンテナを見て Mock を注入してくれる
    
    $response = $this->actingAs($this->user)
        ->postJson("/api/tasks/{$task->id}/complete");
    
    // ✅ Mock が使われる！本物は動かない！
    $response->assertStatus(200);
}
```

**👩‍💻ユーザー：** 「今度は Mock が使われるんですね！」

---

### 📊 図解：差し替えの流れ

**🐘ガネーシャ：** 「図で整理するとこうなるで」

```
┌─────────────────────────────────────────────────────────────┐
│  【パターン1: new する（❌ 差し替え不可）】                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  テスト: $this->app->instance(..., $mock)                   │
│                     ↓                                       │
│              DI コンテナに登録                               │
│              ┌──────────────┐                               │
│              │ Mock が待機中 │                               │
│              └──────────────┘                               │
│                     ↓                                       │
│  UseCase: $service = new NotificationService();             │
│                     ↓                                       │
│           「自分で作るわ」→ DI コンテナ無視                │
│                     ↓                                       │
│              本物が生成される 😢                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  【パターン2: DI で受け取る（✅ 差し替え可能）】            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  テスト: $this->app->instance(..., $mock)                   │
│                     ↓                                       │
│              DI コンテナに登録                               │
│              ┌──────────────┐                               │
│              │ Mock が待機中 │                               │
│              └──────────────┘                               │
│                     ↓                                       │
│  UseCase: __construct(NotificationService $service)         │
│                     ↓                                       │
│           「Laravel さん、これ頂戴」                        │
│                     ↓                                       │
│           DI コンテナ「Mock あるで！」                      │
│                     ↓                                       │
│              Mock が注入される 🎉                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 まとめ表

| 書き方 | DI コンテナ | Mock 差し替え |
|--------|-------------|---------------|
| `new NotificationService()` | ❌ 使われない | ❌ 不可能 |
| `__construct(NotificationService $s)` | ✅ 使われる | ✅ 可能 |

---

### 🎯 DI の本質を具体的なコードで理解しよう

**🐘ガネーシャ：** 「ここまで来たら、DI の本質をもう一回整理するで。具体的なコードで見た方が分かりやすいやろ」

**👩‍💻ユーザー：** 「はい！」

---

#### ❌ ダメな例：クラス内で new する

```php
<?php

class CompleteTaskUseCase
{
    public function execute(Task $task, User $user): Task
    {
        // ❌ クラスの中で直接 new している
        $service = new NotificationService();
        
        $task->status = 'done';
        $task->save();
        
        $service->notify('task_completed', $user, $task->toArray());
        
        return $task;
    }
}
```

**🐘ガネーシャ：** 「この書き方やと、**UseCase の中で NotificationService が固定されてる**んや」

**👩‍💻ユーザー：** 「テストで偽物に差し替えたくても、できないですよね...」

**🐘ガネーシャ：** 「せや。テストで Mock を登録しても、UseCase が勝手に `new` するから無視されるんや」

**👩‍💻ユーザー：** 「ところで、『固定されてる』って具体的にどういうことですか？」

**🐘ガネーシャ：** 「ええ質問や！実はこの状態を**『依存している』**って言うんや」

```
┌─────────────────────────────────────────────────────────────┐
│              「依存している」とは？                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  CompleteTaskUseCase は NotificationService を使っている   │
│  → CompleteTaskUseCase は NotificationService に「依存」   │
│                                                             │
│  class CompleteTaskUseCase {                                │
│      public function execute(...) {                         │
│          $service = new NotificationService();  // ← 依存！│
│          $service->notify(...);                             │
│      }                                                      │
│  }                                                          │
│                                                             │
│  この書き方だと...                                          │
│  ・UseCase の中で「何に依存するか」が決まってしまう        │
│  ・外から変更できない（固定）                               │
│  ・テストで偽物に差し替え不可能                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！『使っている = 依存している』なんですね。で、クラス内で new すると依存先が固定されちゃう...」

**🐘ガネーシャ：** 「**その通りや！** これが問題なんや。ほな、どうすればええと思う？」

**👩‍💻ユーザー：** 「外から渡せばいいんですよね！」

---

#### ✅ 良い例：外から引数で受け取る（DI）

```php
<?php

class CompleteTaskUseCase
{
    // ✅ コンストラクタの引数で外から受け取る
    public function __construct(
        private NotificationService $notificationService
    ) {}
    
    public function execute(Task $task, User $user): Task
    {
        $task->status = 'done';
        $task->save();
        
        // ✅ 外から渡された $this->notificationService を使う
        $this->notificationService->notify('task_completed', $user, $task->toArray());
        
        return $task;
    }
}
```

**🐘ガネーシャ：** 「この書き方やと、**依存しているもの（NotificationService）を外から注入できる**んや」

**👩‍💻ユーザー：** 「あっ！**依存**を**注入**...だから **Dependency Injection（依存性注入）**なんですね！」

**🐘ガネーシャ：** 「**完璧や！** DI の名前の意味がやっと分かったやろ？」

```
┌─────────────────────────────────────────────────────────────┐
│              DI（Dependency Injection）= 依存性注入         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Dependency = 依存しているもの（使っているもの）            │
│  Injection  = 注入する（外から渡す）                        │
│                                                             │
│  つまり...                                                  │
│  「依存しているものを、外から注入する」仕組みのこと！       │
│                                                             │
│  class CompleteTaskUseCase {                                │
│      public function __construct(                           │
│          private NotificationService $service  // ← 注入！ │
│      ) {}                                                   │
│  }                                                          │
│                                                             │
│  ・UseCase は「何に依存するか」を自分で決めない            │
│  ・外から渡される → 呼び出す側が決められる                 │
│  ・本物でも偽物でも OK！                                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「DI って難しそうな名前だと思ってたけど、やってることはシンプルなんですね！」

**🐘ガネーシャ：** 「せや！**『自分で new しない、外からもらう』**だけや。これで依存先を自由に差し替えられるようになるんや」

---

#### 🎭 呼び出す側が「好きなものを渡せる」とは？

**👩‍💻ユーザー：** 「具体的にどういうことですか？」

**🐘ガネーシャ：** 「見てみ。同じ UseCase でも、**渡すものを変えるだけ**で動作が変わるんや」

```php
<?php

// ============================================
// 本番：本物の NotificationService を渡す
// ============================================
$realService = new NotificationService();
$useCase = new CompleteTaskUseCase($realService);
$useCase->execute($task, $user);
// → 本物のログ出力（本番ならメール送信）が実行される


// ============================================
// テスト：偽物（Mock）を渡す
// ============================================
$mockService = Mockery::mock(NotificationService::class);
$mockService->shouldReceive('notify')->once();

$useCase = new CompleteTaskUseCase($mockService);  // ← 偽物を渡す！
$useCase->execute($task, $user);
// → 本物は動かない！「呼ばれたか」だけ検証できる
```

**👩‍💻ユーザー：** 「あー！同じ UseCase なのに、**渡すものを変えるだけで本物にも偽物にもなる**んですね！」

**🐘ガネーシャ：** 「**それが DI の本質や！** 『自分で作らない（new しない）。外からもらう』だけで、こんなに柔軟になるんや」

---

#### 📊 図解：DI の本質

```
┌─────────────────────────────────────────────────────────────┐
│              DI の本質                                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「自分で作らない（new しない）。外からもらう」             │
│                                                             │
│  これだけで、呼び出す側が好きなものを渡せるようになる！     │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  ・new する → 自分で決める → 差し替え不可能               │
│  ・DI で受け取る → 外が決める → 差し替え可能              │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【本番】本物を渡す → 本物が動く                           │
│  【テスト】偽物を渡す → 偽物が動く（本物は動かない）       │
│                                                             │
│  UseCase のコードは一切変えずに、動作を切り替えられる！    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「ワシの教え子のエジソンくんも言うとったで。『テストできないコードは、設計が悪いんや』ってな」

**👩‍💻ユーザー：** 「エジソンくん、そんなこと言ってましたっけ...」

**🐘ガネーシャ：** 「...まぁええやん！大事なのは『new しない、外からもらう』っちゅうことや！」

---

### 🤔 Request や User も同じ仕組み

**🐘ガネーシャ：** 「ちなみにな、普段使ってる Controller も同じ仕組みやで」

```php
<?php

class TaskController extends Controller
{
    // ✅ UseCase も DI で受け取る
    public function __construct(
        private CompleteTaskUseCase $completeTaskUseCase
    ) {}

    // ✅ Request も DI で受け取る
    // ✅ Task も DI（Route Model Binding）で受け取る
    public function complete(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        
        $task = $this->completeTaskUseCase->execute($task, $user);
        
        return response()->json(['data' => new TaskResource($task)]);
    }
}
```

```
┌─────────────────────────────────────────────────────────────┐
│              Controller の引数はすべて DI で解決される       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  public function complete(Request $request, Task $task)     │
│                              ↑              ↑               │
│                              │              │               │
│                    Laravel が用意    URL の ID から取得      │
│                                                             │
│  __construct(CompleteTaskUseCase $useCase)                  │
│                                   ↑                         │
│                                   │                         │
│                         DI コンテナから取得                  │
│                         （Mock に差し替え可能！）           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あれ？UseCase もコンストラクタで受け取ってますね。ということは...」

**🐘ガネーシャ：** 「**気づいたか！** UseCase も DI で渡されとるんや。つまり、**UseCase 自体も Mock に差し替えられる**っちゅうことや」

**👩‍💻ユーザー：** 「えっ！UseCase も Mock にできるんですか！？」

**🐘ガネーシャ：** 「せや。コンストラクタで受け取ってるものは、なんでも差し替え可能や」

```
┌─────────────────────────────────────────────────────────────┐
│              DI で受け取っているもの = 差し替え可能          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Controller                                                 │
│  ├── __construct(CompleteTaskUseCase)  ← 差し替え可能      │
│  │                                                          │
│  └── CompleteTaskUseCase                                    │
│      └── __construct(NotificationService)  ← 差し替え可能  │
│                                                             │
│  どの階層でも、DI で受け取ってれば Mock に差し替えできる！  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！今回は NotificationService を Mock にするけど、やろうと思えば UseCase 自体も Mock にできるんですね」

**🐘ガネーシャ：** 「その通りや！**仕組みとしては**可能や」

**👩‍💻ユーザー：** 「じゃあ UseCase を Mock にすることもあるんですか？」

**🐘ガネーシャ：** 「基本的には**しない**な。なんでかっちゅうと、**UseCase 自体をテストしたい**からや」

```
┌─────────────────────────────────────────────────────────────┐
│              何を Mock にするか？                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【UseCase を Mock にする？】→ ❌ 基本的にしない            │
│  ・UseCase のロジック自体をテストしたい                     │
│  ・Mock にしたら、UseCase のテストにならない               │
│                                                             │
│  【外部サービスを Mock にする？】→ ✅ する                  │
│  ・NotificationService（メール送信）                       │
│  ・PaymentService（決済処理）                              │
│  ・ExternalApiService（外部 API 呼び出し）                 │
│  → テスト時に本物を動かしたくないもの                     │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【今回のテスト】                                           │
│  ・UseCase のロジックをテストしたい（本物を使う）          │
│  ・でもメール送信はしたくない（Mock に差し替え）           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！UseCase は『テストしたい対象』だから Mock にしない。NotificationService は『テスト時に動かしたくない外部処理』だから Mock にするんですね！」

**🐘ガネーシャ：** 「完璧や！」

**👩‍💻ユーザー：** 「全部 DI で渡されてたんですね！今まで意識してませんでした」

**🐘ガネーシャ：** 「せやろ？Laravel は賢いから、お前が意識せんでも裏でやってくれとったんや」

**👩‍💻ユーザー：** 「でも、ちょっと待ってください。Controller や UseCase って、私たち `new` してないですよね？誰が作ってるんですか？」

**🐘ガネーシャ：** 「**ええ質問や！** 実は、それも全部 Laravel が自動でやってくれとるんや」

---

### 🤔 Laravel が自動で new してくれる仕組み

**🐘ガネーシャ：** 「API リクエストが来た時、Laravel が裏で何をしてるか説明するで」

```
┌─────────────────────────────────────────────────────────────┐
│              Laravel が自動で new してくれる                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  API リクエストが来ると...                                  │
│                                                             │
│  1. Controller が必要 → Laravel が作る                     │
│  2. Controller のコンストラクタに UseCase がある            │
│  3. Laravel「UseCase が必要やな。DI コンテナに聞くで」     │
│  4. DI コンテナ「登録されてないから new するわ」           │
│  5. UseCase のコンストラクタに NotificationService がある   │
│  6. Laravel「NotificationService も必要やな」              │
│  7. DI コンテナ「これも登録されてないから new するわ」     │
│                                                             │
│  → 全部 Laravel が自動でやってくれる！                     │
│  → 私たちは「コンストラクタに型を書く」だけでええ          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「つまり、UseCase も NotificationService も、全部 DI で自動的に作られてるんですね！」

**🐘ガネーシャ：** 「せや！私たちが `new` を書かなくても、Laravel が裏で全部やってくれとるんや」

---

#### 📝 Laravel が裏でやってること（イメージ）

**🐘ガネーシャ：** 「もうちょい詳しく説明するで。Laravel が裏で何をしてるかイメージで見せたる」

```php
<?php
// これは Laravel が内部で自動的にやってること（擬似コード）

// API リクエストが来た！
// ↓
// routes/api.php を見る
// Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
// ↓
// TaskController を作らなアカン
// ↓
// TaskController のコンストラクタを見る
// public function __construct(CompleteTaskUseCase $completeTaskUseCase)
// ↓
// CompleteTaskUseCase が必要や！DI コンテナに聞くで
// → 登録されてない → new して作るわ
// ↓
// CompleteTaskUseCase のコンストラクタを見る
// public function __construct(NotificationService $notificationService)
// ↓
// NotificationService が必要や！DI コンテナに聞くで
// → 登録されてない → new して作るわ
// ↓

// Laravel が自動で実行するコード（イメージ）
$notificationService = new NotificationService();  // DI で自動生成
$useCase = new CompleteTaskUseCase($notificationService);  // DI で自動生成
$controller = new TaskController($useCase);  // DI で自動生成
$controller->complete($task);
```

**👩‍💻ユーザー：** 「すごい！私たちは `new` を1つも書いてないのに、全部作られてる！」

**🐘ガネーシャ：** 「せや。これが **DI コンテナ**の力や。**コンストラクタに型を書くだけ**で、Laravel が勝手に作って渡してくれる」

---

#### 📊 図解

```
┌─────────────────────────────────────────────────────────────┐
│              Laravel の自動組み立て                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  API リクエスト                                             │
│         ↓                                                   │
│  Laravel「TaskController 作るで」                          │
│         ↓                                                   │
│  Laravel「CompleteTaskUseCase が必要やな」                 │
│         ↓                                                   │
│  Laravel「NotificationService が必要やな」                 │
│         ↓                                                   │
│  Laravel「DI コンテナに聞いてみよ...」                     │
│         ↓                                                   │
│  DI コンテナ「特に登録されてないで」                       │
│         ↓                                                   │
│  Laravel「ほな new NotificationService() するわ」          │
│         ↓                                                   │
│  new CompleteTaskUseCase($notificationService)              │
│         ↓                                                   │
│  new TaskController($useCase)                               │
│         ↓                                                   │
│  $controller->complete($task) 実行！                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「私たちは型を書くだけで、Laravel が全部組み立ててくれるんですね！」

**🐘ガネーシャ：** 「その通りや！そして**テストの時だけ Mock に差し替えたい**場合は、DI コンテナに『Mock を使え』って登録するんや。そしたら Laravel は Mock を使って組み立ててくれる」

**👩‍💻ユーザー：** 「なるほど！だから `$this->app->instance()` で登録するだけでいいんですね！」

**🐘ガネーシャ：** 「完璧に理解したな！ほな、実際に作っていこか」

---

## 📖 第3章：NotificationService を作ろう

### 🎭 まず通知サービスを作る

**🐘ガネーシャ：** 「ほな、実際に作っていこか。まずは NotificationService やで」

**👩‍💻ユーザー：** 「はい！」

---

### 📁 ディレクトリ構成

```
┌─────────────────────────────────────────────────────────────┐
│              作成するファイル                                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  app/                                                       │
│  └── Services/                                              │
│      └── NotificationService.php  ← 今から作る！           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「Services フォルダがなければ作ってな」

```bash
touch app/Services/NotificationService.php
```

---

### 📝 NotificationService を作成

**ファイル**: `app/Services/NotificationService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 通知サービス
 * 
 * 実際にはログ出力のみ（メール/Slack送信の模擬）
 * テスト時は Mock に差し替えて使用する
 */
class NotificationService
{
    /**
     * 通知を送信する
     *
     * @param string $type    通知タイプ（例: 'task_completed'）
     * @param User   $actor   操作を実行したユーザー
     * @param array  $payload 通知に含めるデータ
     * @return void
     */
    public function notify(string $type, User $actor, array $payload): void
    {
        // 実際のメール送信の代わりにログ出力
        // （本番ではここで Mail::send() などを呼ぶ）
        Log::info('[Notification]', [
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'payload' => $payload,
        ]);
    }
}
```

**👩‍💻ユーザー：** 「ログ出力だけ...？メール送信しないんですか？」

**🐘ガネーシャ：** 「教材用やからログにしてるんや。でもな、もしこれが **本物のメール送信**やったらどうなると思う？」

**👩‍💻ユーザー：** 「えっと...テストを実行するたびにメールが送られる？」

**🐘ガネーシャ：** 「**その通りや！** 想像してみ。テストが10個あって、それぞれでタスク完了のテストをしたら...」

**👩‍💻ユーザー：** 「10通のメールが届く...！😱」

**🐘ガネーシャ：** 「しかもな、CI/CD で自動テストが動くたびに、コミットするたびに、何十通もメールが飛んでいくんや。Slack 通知やったら、通知の嵐でチャンネルが埋まるで」

```
┌─────────────────────────────────────────────────────────────┐
│              😱 もし本物の通知を使ったら...                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【テスト実行のたびに】                                     │
│  ・テスト10個 × 1通 = 10通のメール                         │
│  ・CI/CD が1日10回動く → 100通/日                         │
│  ・開発者5人が各自テスト → 500通/日                       │
│                                                             │
│  【Slack 通知の場合】                                       │
│  ・#general が「タスク完了しました」で埋まる               │
│  ・本物の通知が見つけられなくなる                          │
│  ・チームメンバーから苦情が来る 😢                         │
│                                                             │
│  【外部 API の場合】                                        │
│  ・API の呼び出し回数制限に引っかかる                      │
│  ・テストのたびに課金される 💸                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「それは迷惑すぎる...！」

**🐘ガネーシャ：** 「せやろ？だから**テストの時だけ偽物（Mock）に差し替える**んや。Mock なら何回呼んでもメールは1通も飛ばん。これが Mock を使う最大の理由やで」

```
┌─────────────────────────────────────────────────────────────┐
│              なぜログ出力だけ？（教材用）                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【今回の教材】                                             │
│  ・ログ出力にしてるから、実害はない                         │
│  ・でも「Mock の使い方を学ぶ」には十分！                   │
│                                                             │
│  【実際の実装（本番）】                                     │
│  本番では Log::info() の代わりに...                         │
│  ・Mail::send() でメール送信                                │
│  ・Http::post() で Slack 送信                               │
│  ・外部 API 呼び出し                                        │
│  → これらは **テストで本物を動かしたくない！**            │
│                                                             │
│  【Mock の価値】                                            │
│  ・テストで本物の通知を送らない                            │
│  ・「呼ばれたかどうか」だけ検証できる                      │
│  ・高速にテストできる（外部通信なし）                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！今回はログだから実害ないけど、本番を想定して Mock の使い方を学ぶんですね！」

**🐘ガネーシャ：** 「完璧や！さすがワシの弟子や」

---

## 📖 第4章：CompleteTaskUseCase を修正しよう

### 🎭 通知機能を追加する

**🐘ガネーシャ：** 「次に、タスク完了時に通知を送るよう UseCase を修正するで」

**👩‍💻ユーザー：** 「DI で NotificationService を受け取るんですよね！」

**🐘ガネーシャ：** 「せや！コンストラクタに追加するんや。**絶対に new しないこと**、これが大事やで」

---

### 📝 CompleteTaskUseCase を修正

**ファイル**: `app/UseCases/Task/CompleteTaskUseCase.php`

```php
<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;        // ← 追加
use App\Services\Project\ProjectRules;
use App\Exceptions\ConflictException;

/**
 * タスク完了UseCase（doing → done）
 *
 * 役割：
 * - 「完了」という業務シナリオ（検証 → 状態変更 → 必要なロード）を組み立てる
 * - 複数ドメインにまたがる共通ルールは Rules に委譲する
 * - このUseCase固有の条件は UseCase 内に閉じる（必要に応じて private に隔離）
 */
class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
        private NotificationService $notificationService,  // ← 追加
    ) {}

    /**
     * タスクを完了する
     *
     * @param Task $task 完了するタスク
     * @param User $user 操作を実行するユーザー
     * @return Task 更新されたタスク
     * @throws ConflictException doing 以外のステータスの場合
     */
    public function execute(Task $task, User $user): Task
    {
        // ========================================
        // 1. 検証（ビジネスルール / 制約）
        // ========================================

        // 横断ルール：このプロジェクトを操作できるメンバーか？
        // （Project×Membership など、複数UseCaseで再利用される前提ルール）
        $this->projectRules->ensureMember($task->project, $user);

        // UseCase固有ルール：このタスクは「完了」に遷移できる状態か？
        //（現時点では条件が少なくても、状態遷移は条件が増えやすいので隔離しておく）
        $this->ensureCanComplete($task);

        // ========================================
        // 2. 状態変更（UseCaseの責務）
        // ========================================
        $task->status = 'done';
        $task->save();

        // ========================================
        // 3. 通知送信 ← 追加！
        // ========================================
        // ✅ DI で受け取った $this->notificationService を使う
        // テスト時は Mock が渡されるので、本物は動かない
        $this->notificationService->notify('task_completed', $user, $task->toArray());

        // ========================================
        // 4. 表示に必要なデータをロード（I/O都合）
        // ========================================
        $task->load('createdBy');

        return $task;
    }

    /**
     * タスクが「完了」に遷移可能か検証する（UseCase固有の制約）
     *
     * 置き場所の意図：
     * - これは「タスク完了」というシナリオに閉じた条件（現時点では他UseCaseで使わない想定）
     * - execute() の流れ（検証→更新）を読みやすく保つため、検証ロジックを private に隔離する
     * - private だからテスト不要、ではなく「UseCaseテストで完了条件をまとめて検証する」方針
     *   （将来この条件が複数UseCaseに広がったら Rules へ昇格を検討する）
     *
     * @param Task $task タスク
     * @return void
     * @throws ConflictException
     */
    private function ensureCanComplete(Task $task): void
    {
        if (!$task->isDoing()) {
            throw new ConflictException('作業中のタスクのみ完了できます');
        }
    }
}
```

---

### 🤔 変更点を確認

**🐘ガネーシャ：** 「変更点を整理するで」

```
┌─────────────────────────────────────────────────────────────┐
│              CompleteTaskUseCase の変更点                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  use 文を追加                                           │
│      use App\Services\NotificationService;                  │
│                                                             │
│  2️⃣  コンストラクタに追加                                   │
│      private NotificationService $notificationService       │
│                                                             │
│  3️⃣  execute() 内で notify() を呼び出す                     │
│      $this->notificationService->notify('task_completed', ...)│
│                                                             │
│  ✅ new NotificationService() とは書いてない！              │
│  ✅ 外から受け取ってるから、差し替え可能！                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「`new` してない！これが DI ですね！」

**🐘ガネーシャ：** 「せや！Laravel が自動で NotificationService を作って渡してくれるんや」

---

## 📖 第5章：動作確認しよう

### 🎭 まず本番の動作を確認

**🐘ガネーシャ：** 「テストを書く前に、まず普通に動くか確認しよか」

**👩‍💻ユーザー：** 「Postman で試してみます！」

---

### 📝 ログファイルを準備

**🐘ガネーシャ：** 「API を実行する前に、**ログファイルの中身を削除**しておくとええで。そしたら新しいログだけ確認できるからな」

```
📁 storage/logs/laravel-YYYY-MM-DD.log
```

**💡 準備：** ログファイルを開いて、中身を全削除しておこう（`Ctrl+A` で全選択 → 削除 → 保存）

**💡 ヒント：** ログファイルは日付ごとに分かれてるで。今日の日付のファイルを開いてな。  
例：`storage/logs/laravel-2026-01-31.log`

---

### 📝 API を実行

**🐘ガネーシャ：** 「ログを空にしたら、API を実行してみ。doing 状態のタスクで試してな」

```
POST /api/tasks/3/complete
Authorization: Bearer {token}
```

---

### 📝 ログを確認

**🐘ガネーシャ：** 「API 実行後、ログファイルを開いて確認してみ」

ファイルを開くと、こんな感じのログが出力されてるはずや：

```
[2024-01-15 10:30:45] local.INFO: [Notification] {
    "type": "task_completed",
    "actor_id": 1,
    "actor_name": "山田太郎",
    "payload": {
        "id": 5,
        "title": "サンプルタスク",
        "project_id": 1,
        "status": "done",
        ...
    }
}
```

**👩‍💻ユーザー：** 「ログに出力されてる！通知機能が動いてますね」

**🐘ガネーシャ：** 「せや。本番ではこれがメール送信になるわけや。でも、テストでメール送信（この場合はログ出力）されたら、テストが外部に依存してしまうよな？」

**👩‍💻ユーザー：** 「はい...『通知が呼ばれたか』だけ確認できればいいのに...」

**🐘ガネーシャ：** 「そこで**Mock**の出番や！」

---

## 📖 第6章：Mock の文法を学ぼう

### 🎭 Mock って何？

**🐘ガネーシャ：** 「テストを書く前に、Mock の文法を詳しく説明するで」

```
┌─────────────────────────────────────────────────────────────┐
│              Mock（モック）とは？                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  本物のクラスと「同じ型」を持つ偽物                         │
│                                                             │
│  【本物: NotificationService】                              │
│  → notify() を呼ぶとログ出力（本番ならメール送信）         │
│                                                             │
│  【Mock: Mockery::mock(NotificationService::class)】        │
│  → notify() を呼んでも何もしない（偽物だから）             │
│  → 代わりに「呼ばれたかどうか」を記録する                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📝 `$this->app->instance()` の意味

**🐘ガネーシャ：** 「まず、Mock を DI コンテナに登録する方法から説明するで」

#### 基本構文

```php
$this->app->instance(クラス名::class, 渡したいオブジェクト);
```

```
┌─────────────────────────────────────────────────────────────┐
│              $this->app->instance() とは？                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「この型が必要になったら、このオブジェクトを使え」         │
│  と DI コンテナに登録するメソッド                           │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  $this->app                                                 │
│      ↑ Laravel の DI コンテナ（サービスコンテナ）          │
│                                                             │
│  ->instance(                                                │
│      NotificationService::class,  ← この型が必要な時は     │
│      $mock                        ← これを渡せ             │
│  );                                                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### 具体例

```php
// Mock を作成
$mock = Mockery::mock(NotificationService::class);

// DI コンテナに登録
// 「NotificationService が必要な時は $mock を使え」
$this->app->instance(NotificationService::class, $mock);

// この後、Laravel が NotificationService を必要とした時、
// new NotificationService() ではなく $mock が渡される
```

---

### 📝 Mockery の基本メソッド

#### Mock の作成

```php
$mock = Mockery::mock(NotificationService::class);
```

| 部分 | 意味 |
|------|------|
| `Mockery::mock()` | 偽物（Mock）を作るメソッド |
| `NotificationService::class` | どの型の偽物を作るか |
| `$mock` | 作られた偽物オブジェクト |

---

#### shouldReceive() - メソッドが呼ばれることを期待

```php
$mock->shouldReceive('notify');
```

| 部分 | 意味 |
|------|------|
| `shouldReceive()` | 「このメソッドが呼ばれるはず」と宣言 |
| `'notify'` | 呼ばれるメソッド名 |

```
「notify() が呼ばれるはずやで」と宣言する。
テスト終了時に呼ばれてなかったらエラーになる。
```

```
┌─────────────────────────────────────────────────────────────┐
│              ⚠️ 重要：メソッド名は本物と一致させる           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  shouldReceive('notify') の 'notify' は                    │
│  本物の NotificationService のメソッド名と同じ！            │
│                                                             │
│  【本物のクラス】                                           │
│  class NotificationService {                                │
│      public function notify(...) { ... }  ← このメソッド名 │
│  }                                                          │
│                                                             │
│  【Mock の設定】                                            │
│  $mock->shouldReceive('notify')  ← 同じ名前を指定！        │
│                                                             │
│  ❌ 間違い例                                                │
│  $mock->shouldReceive('sendNotification')  ← 存在しない！  │
│  → 本物には notify() しかないのに別の名前を指定してもダメ │
│                                                             │
│  💡 本物のメソッドが何か確認してから書こう！               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

#### once() - 1回だけ呼ばれることを期待

```php
$mock->shouldReceive('notify')->once();
```

| メソッド | 意味 |
|----------|------|
| `once()` | 1回だけ呼ばれる |
| `twice()` | 2回呼ばれる |
| `times(3)` | 3回呼ばれる |
| `never()` | 1回も呼ばれない（`shouldNotReceive()` と同じ） |

```
「notify() がちょうど1回呼ばれるはずやで」と宣言する。
0回でも2回でもエラーになる。
```

---

#### shouldNotReceive() - メソッドが呼ばれないことを期待

```php
$mock->shouldNotReceive('notify');
```

```
「notify() は絶対に呼ばれないはずやで」と宣言する。
1回でも呼ばれたらエラーになる。

例: エラーケースのテストで、通知が送信されないことを確認したい時
```

---

#### with() - 引数を検証

```php
$mock->shouldReceive('notify')
    ->once()
    ->with('task_completed', $user, $payload);
```

| 部分 | 意味 |
|------|------|
| `with()` | 「この引数で呼ばれるはず」と宣言 |
| 第1引数 | `'task_completed'` と一致するか |
| 第2引数 | `$user` と一致するか |
| 第3引数 | `$payload` と一致するか |

```
「notify() が、この引数で呼ばれるはずやで」と宣言する。
引数が違ったらエラーになる。
```

---

#### Mockery::on() - 引数を柔軟に検証

```php
$mock->shouldReceive('notify')
    ->once()
    ->with(
        'task_completed',
        Mockery::on(fn($actor) => $actor->id === 1),
        Mockery::on(fn($payload) => $payload['id'] === 5)
    );
```

| 部分 | 意味 |
|------|------|
| `Mockery::on()` | 引数をコールバック関数で検証 |
| `fn($actor) => $actor->id === 1` | 「$actor->id が 1 なら OK」 |
| `fn($payload) => ...` | 「$payload['id'] が 5 なら OK」 |

```
┌─────────────────────────────────────────────────────────────┐
│              Mockery::on() はいつ使う？                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  引数が「完全一致」では検証しにくい時                       │
│                                                             │
│  例: User オブジェクトは毎回新しく作られるので              │
│      $user === $user では比較できない                       │
│      → Mockery::on(fn($u) => $u->id === 1) で ID だけ比較  │
│                                                             │
│  例: 配列の一部だけ検証したい時                             │
│      → Mockery::on(fn($p) => $p['id'] === 5)               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「オブジェクトは毎回新しく作られるから、完全一致では比較できないんですね！」

**🐘ガネーシャ：** 「せや！だから `Mockery::on()` を使って、ID だけで比較するんや」

---

#### withArgs() - 複数引数をまとめて検証

```php
$mock->shouldReceive('notify')
    ->once()
    ->withArgs(function ($type, $actor, $payload) use ($task) {
        return $type === 'task_completed'
            && $actor->id === 1
            && $payload['id'] === $task->id;
    });
```

```
複数の引数をまとめて1つのコールバックで検証できる。
すべての条件が true なら OK。
```

---

### 📊 よく使うパターン一覧

```php
<?php

// ============================================
// パターン1: 単純に「呼ばれたか」だけ確認
// ============================================
$mock = Mockery::mock(NotificationService::class);
$mock->shouldReceive('notify')->once();


// ============================================
// パターン2: 「呼ばれない」ことを確認
// ============================================
$mock = Mockery::mock(NotificationService::class);
$mock->shouldNotReceive('notify');


// ============================================
// パターン3: 引数も含めて厳密に確認
// ============================================
$mock = Mockery::mock(NotificationService::class);
$mock->shouldReceive('notify')
    ->once()
    ->with('task_completed', Mockery::any(), Mockery::any());;


// ============================================
// パターン4: 引数の一部を柔軟に確認
// ============================================
$mock = Mockery::mock(NotificationService::class);
$mock->shouldReceive('notify')
    ->once()
    ->with(
        'task_completed',
        Mockery::on(fn($actor) => $actor->id === $this->user->id),
        Mockery::on(fn($payload) => $payload['id'] === $task->id)
    );


// ============================================
// パターン5: 複数引数をまとめて確認
// ============================================
$mock = Mockery::mock(NotificationService::class);
$mock->shouldReceive('notify')
    ->once()
    ->withArgs(function ($type, $actor, $payload) use ($task) {
        return $type === 'task_completed'
            && $actor->id === $this->user->id
            && $payload['id'] === $task->id
            && $payload['title'] === $task->title;
    });
```

**👩‍💻ユーザー：** 「色んなパターンがあるんですね！」

**🐘ガネーシャ：** 「せや。今回は**パターン4**を使うで。引数の一部だけ柔軟に確認するパターンや」

---

## 📖 第7章：既存のテストに Mock を組み込もう

### 🤔 Mock を使ったテストは「通知のテスト」ではない

**🐘ガネーシャ：** 「ここで大事なことを確認するで」

**👩‍💻ユーザー：** 「はい！」

**🐘ガネーシャ：** 「Mock を使ったテストは『**通知のテスト**』やない。なんでかわかるか？」

**👩‍💻ユーザー：** 「えっと...Mock って偽物ですよね。偽物を使ってるから...？」

**🐘ガネーシャ：** 「**その通りや！** Mock は偽物やから、**本物の通知処理は一切動いてない**んや」

```
┌─────────────────────────────────────────────────────────────┐
│              Mock は「偽物」である                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【本物の NotificationService】                             │
│  → notify() を呼ぶとログ出力（本番ならメール送信）         │
│  → 実際に通知が送られる                                    │
│                                                             │
│  【Mock（偽物）】                                           │
│  → notify() を呼んでも何もしない（偽物だから）             │
│  → 「呼ばれたかどうか」を記録するだけ                      │
│  → 通知が届くかどうかは分からない                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あ！Mock では『通知が実際に届くか』は検証できないんですね！」

**🐘ガネーシャ：** 「せや。Mock で検証できるのは『**UseCase が通知サービスを呼び出したか**』だけや。通知サービス自体が正しく動くかは、Mock では検証できへん」

```
┌─────────────────────────────────────────────────────────────┐
│              Mock で検証できること・できないこと            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ Mock で検証できる                                       │
│  ・UseCase が notify() を呼んだか                          │
│  ・正しい引数で呼んだか                                    │
│  ・何回呼んだか                                            │
│  → 「依頼したこと」は検証できる                           │
│                                                             │
│  ❌ Mock で検証できない                                     │
│  ・メールが実際に送信されるか                              │
│  ・通知の内容が正しいか                                    │
│  ・宛先に届くか                                            │
│  → 「届くこと」は検証できない（偽物だから）               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「つまり、これは『通知のテスト』じゃなくて『UseCase のテスト』なんですね！」

**🐘ガネーシャ：** 「**完璧や！** Mock は『外部依存を切り離す』ためのもので、UseCase が『正しく依頼するか』を検証してるんや。通知が届くかどうかをテストしたかったら `Mail::fake()` みたいな別の方法を使うことになるで」

**👩‍💻ユーザー：** 「なるほど！Mock の役割がよく分かりました！」

**🐘ガネーシャ：** 「ほな、既存のテストに Mock を組み込んでいこか」

---

### 📝 既存のテストに Mock を追加

**ファイル**: `tests/Feature/Api/TaskApiTest.php`

まず、use 文に `Mockery` と `NotificationService` を追加：

```php
use App\Services\NotificationService;
use Mockery;
```

次に、タスク完了の正常系テストに Mock を組み込む：

```php
/**
 * doing ステータスのタスクを完了できる
 *
 * 正常系：doing → done への状態遷移
 * Mock を使って通知サービスを差し替え、外部依存を切り離してテスト
 */
public function test_doingステータスのタスクを完了できる(): void
{
    // ============================================
    // 1. Arrange（準備）
    // ============================================
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => 'doing',
    ]);

    // NotificationService を Mock に差し替え
    // → 本物の通知処理（ログ出力やメール送信）を実行しない
    $mockNotification = Mockery::mock(NotificationService::class);
    $mockNotification
        ->shouldReceive('notify')
        ->once()
        ->with(
            'task_completed',
            Mockery::on(fn($user) => $user->id === $this->user->id),
            Mockery::on(fn($payload) => $payload['id'] === $task->id)
        );
    $this->app->instance(NotificationService::class, $mockNotification);

    // ============================================
    // 2. Act（実行）
    // ============================================
    $response = $this->actingAs($this->user)
        ->postJson("/api/tasks/{$task->id}/complete");

    // ============================================
    // 3. Assert（検証）
    // ============================================
    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'status' => 'done',
        ]
    ]);

    // DB にも反映されていることを確認
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'done',
    ]);

    // Mockery が自動で「notify() が正しく呼ばれたか」を検証
}
```

---

### 🤔 テストの構造を整理

**🐘ガネーシャ：** 「このテストが何をしてるか整理するで」

```
┌─────────────────────────────────────────────────────────────┐
│              テストの構造                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【テスト対象】                                             │
│  CompleteTaskUseCase（タスク完了の UseCase）               │
│                                                             │
│  【検証内容】                                               │
│  1. レスポンスが 200 OK                                    │
│  2. ステータスが done に変わる                             │
│  3. 通知サービスが正しく呼ばれる（←Mock で検証）          │
│                                                             │
│  【Mock の役割】                                            │
│  ・外部依存（通知）を切り離す                              │
│  ・UseCase が通知を「依頼した」ことを検証                 │
│  ・本物の通知処理は実行しない                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「Mock は『通知のテスト』じゃなくて、『UseCase が通知を正しく依頼するか』のテストなんですね！」

**🐘ガネーシャ：** 「完璧や！」

---

### 🎭 テストを実行

**🐘ガネーシャ：** 「テストを実行する前に、**ログファイルの中身を削除**しておくんやで。Mock が正しく機能してるか確認するためや」

```
📁 storage/logs/laravel-YYYY-MM-DD.log
```

**💡 準備：** ログファイルを開いて、中身を全削除しておこう（`Ctrl+A` で全選択 → 削除 → 保存）

**🐘ガネーシャ：** 「ログを空にしたら、テストを実行してみ」

```bash
sail artisan test --filter=TaskApiTest
```

```
   PASS  Tests\Feature\Api\TaskApiTest
  ✓ タスクを作成できる                                       2.35s
  ✓ todoステータスのタスクを開始できる                       0.09s
  ✓ doingステータスのタスクを完了できる                      0.08s  ← 新規追加
  ...

  Tests:    8 passed (19 assertions)
  Duration: 3.98s
```

**👩‍💻ユーザー：** 「通った！🎉」

**🐘ガネーシャ：** 「ほな、ログファイルを開いて `[Notification]` で検索してみ」

**👩‍💻ユーザー：** 「あれ？ログが1つも出てない...」

**🐘ガネーシャ：** 「**それが正解や！** Mock が正しく機能してる証拠やで。本物の NotificationService は動いてないんや」

**👩‍💻ユーザー：** 「すごい！Mock に差し替えたから、本物の `Log::info()` は呼ばれてないんですね！」

---

## 📖 第8章：異常系テストにも Mock を組み込もう

### 🎭 通知が呼ばれないケースを検証

**🐘ガネーシャ：** 「正常系だけやのうて、異常系のテストにも Mock を組み込むで」

**👩‍💻ユーザー：** 「todo のタスクを完了しようとしたら、409 エラーになりますよね。その時は通知も送られないはず...」

**🐘ガネーシャ：** 「せや！そういう時は `shouldNotReceive()` を使うんや」

---

### 📝 異常系テストに Mock を追加

```php
/**
 * todo ステータスのタスクは完了できない（409）
 *
 * 異常系：doing を経由せずに完了しようとした場合
 * Mock を使って通知が呼ばれないことも検証
 */
public function test_todoステータスのタスクは完了できない(): void
{
    // ============================================
    // 1. Arrange（準備）
    // ============================================
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => 'todo',  // ← まだ着手してない
    ]);

    // NotificationService を Mock に差し替え
    // → 異常系なので notify() は呼ばれないはず
    $mockNotification = Mockery::mock(NotificationService::class);
    $mockNotification->shouldNotReceive('notify');
    $this->app->instance(NotificationService::class, $mockNotification);

    // ============================================
    // 2. Act（実行）
    // ============================================
    $response = $this->actingAs($this->user)
        ->postJson("/api/tasks/{$task->id}/complete");

    // ============================================
    // 3. Assert（検証）
    // ============================================
    $response->assertStatus(409);
    $response->assertJson([
        'message' => '作業中のタスクのみ完了できます',
    ]);

    // ステータスが変わっていないことも確認
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'todo',  // ← todo のまま
    ]);

    // Mockery が自動で「notify() が呼ばれなかったか」を検証
}
```

**👩‍💻ユーザー：** 「`shouldNotReceive('notify')` で『呼ばれないはず』って宣言するんですね！」

**🐘ガネーシャ：** 「せや。もし呼ばれたらテストが失敗するで。これで『エラーの時は通知が送られない』ことを保証できるんや」

---

### 📊 shouldReceive vs shouldNotReceive

```
┌─────────────────────────────────────────────────────────────┐
│              shouldReceive vs shouldNotReceive              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【shouldReceive('notify')->once()】                        │
│  「notify() が1回呼ばれるはず」                             │
│  → 0回でも2回でもエラー                                    │
│  → 正常系のテストで使う                                    │
│                                                             │
│  【shouldNotReceive('notify')】                             │
│  「notify() は絶対に呼ばれないはず」                        │
│  → 1回でも呼ばれたらエラー                                 │
│  → 異常系のテストで使う                                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🎭 テストを実行

**🐘ガネーシャ：** 「さっきと同じように、テストを実行する前に**ログファイルの中身を削除**しておくんやで」

```
📁 storage/logs/laravel-YYYY-MM-DD.log
```

**💡 準備：** ログファイルを開いて、中身を全削除しておこう（`Ctrl+A` で全選択 → 削除 → 保存）

**🐘ガネーシャ：** 「ログを空にしたら、テストを実行してみ」

```bash
sail artisan test --filter=TaskApiTest
```

```
   PASS  Tests\Feature\Api\TaskApiTest
  ✓ タスクを作成できる                                       2.35s
  ✓ todoステータスのタスクを開始できる                       0.09s
  ✓ doingステータスのタスクは開始できない                    0.41s
  ✓ doingステータスのタスクを完了できる                      0.08s
  ✓ todoステータスのタスクは完了できない                     0.32s
  ...

  Tests:    8 passed (21 assertions)
  Duration: 3.98s
```

**👩‍💻ユーザー：** 「全部通った！🎉」

---

### 📝 ログを確認してみよう

**🐘ガネーシャ：** 「ほな、ログファイルを開いて `[Notification]` で検索してみ」

**👩‍💻ユーザー：** 「やっぱりログが1つも出てないですね！正常系も異常系も、Mock がちゃんと機能してる！」

**🐘ガネーシャ：** 「**その通りや！** 今度は2つのテストを実行したのに、ログは1つも出てへん」

```
┌─────────────────────────────────────────────────────────────┐
│              Mock が機能している証拠                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【テストで実行したこと】                                   │
│  ・正常系: doing → done（通知が呼ばれるはず）              │
│  ・異常系: todo → done（通知が呼ばれないはず）             │
│                                                             │
│  【ログに出力されたか？】                                   │
│  → 何も出力されてない！                                    │
│                                                             │
│  【なぜ？】                                                 │
│  → 本物の NotificationService は動いてないから！           │
│  → Mock（偽物）が使われたから、Log::info() は呼ばれない   │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 もし本番（実際のシステム）だったら...                   │
│  ・テストを実行するたびにメールが送られてしまう 😱          │
│  ・でも Mock を使えば、メールは1通も届かない！ ✅          │
│                                                             │
│  これが Mock の威力や！                                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「すごい！テストでは『呼ばれたか』だけ検証して、本物の処理は一切動いてないんですね！」

**🐘ガネーシャ：** 「せや。**Mock は『本物のフリをする偽物』**やから、UseCase から見たら本物を呼んでるつもりやけど、実際は何も起きてないんや。これで安心してテストを何回でも実行できるやろ？」

**👩‍💻ユーザー：** 「はい！テストのたびにメールが飛んだり、ログが汚れたりしないのは本当に便利ですね！」

---

## 📝 まとめ

### 🎯 今日学んだこと

```
┌─────────────────────────────────────────────────────────────┐
│                    📝 Lesson 7-7 まとめ                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  DI（依存性注入）とは                                   │
│      → 「自分で作らない（new しない）。外からもらう」      │
│      → コンストラクタの引数で受け取る                      │
│      → Laravel が自動で渡してくれる                        │
│                                                             │
│  2️⃣  DI コンテナ（サービスコンテナ）                        │
│      → 「この型が必要な時は、これを渡せ」という対応表      │
│      → instance() > bind() > デフォルト の優先順位         │
│                                                             │
│  3️⃣  なぜ DI を使うのか                                     │
│      → new すると差し替え不可能                            │
│      → DI で受け取ると差し替え可能（Mock に置換できる）   │
│                                                             │
│  4️⃣  Mock の位置付け                                        │
│      → 「通知のテスト」ではない！                          │
│      → 「UseCase のテスト」で外部依存を切り離すためのもの │
│      → 既存のテストに組み込んで使う                        │
│                                                             │
│  5️⃣  Mock の基本文法                                        │
│      → Mockery::mock() で偽物を作成                        │
│      → shouldReceive() で「呼ばれるはず」を定義            │
│      → shouldNotReceive() で「呼ばれないはず」を定義       │
│      → with() / Mockery::on() で引数を検証                 │
│                                                             │
│  6️⃣  DI コンテナへの登録                                    │
│      → $this->app->instance() で Mock を登録               │
│      → これで本物が偽物に差し替わる                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 DI の全体像

```
┌─────────────────────────────────────────────────────────────┐
│              DI と Mock の関係                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【本番の動き】                                             │
│                                                             │
│  Controller → UseCase → NotificationService（本物）        │
│                              ↓                              │
│                         Log::info() 実行                    │
│                   （本番なら Mail::send()）                 │
│                                                             │
│  ─────────────────────────────────────────────────          │
│  【テストの動き】                                           │
│                                                             │
│  $this->app->instance(..., $mock);  ← 差し替え指示         │
│                                                             │
│  Controller → UseCase → Mock（偽物）                       │
│                              ↓                              │
│                         何もしない                          │
│                   （呼ばれたことだけ記録）                 │
│                                                             │
│  → 本物の処理は実行されない！🎉                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📚 次回予告

**🐘ガネーシャ：** 「今回は DI の基本を学んだな。次回は...」

**👩‍💻ユーザー：** 「次は何を学ぶんですか？」

**🐘ガネーシャ：** 「**Interface を使った DI** や。今回の方法でも十分やけど、Interface を使うともっと柔軟に切り替えられるようになるで」

```
┌─────────────────────────────────────────────────────────────┐
│              今回 vs 次回                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【今回（パターン A）】                                     │
│  ・Interface なし                                           │
│  ・シンプルで分かりやすい                                   │
│  ・Mock のテストは問題なくできる                           │
│                                                             │
│  【次回（パターン B）】                                     │
│  ・Interface あり                                           │
│  ・本番とテスト以外にも、複数の実装を切り替え可能          │
│  ・例：ログ版、メール版、Slack版 を簡単に切り替え         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「今回の内容をしっかり理解してから次に進むんやで！DI は最初は難しく感じるかもしれへんけど、慣れたら『なんで今まで new してたんやろ』ってなるで」

**👩‍💻ユーザー：** 「はい！『new しない、外からもらう』が大事なんですね！」

**🐘ガネーシャ：** 「その通りや！さすガネーシャや！🐘✨」

---

### 🍨 おまけ：今回のキーワード

| キーワード | 意味 |
|------------|------|
| DI（依存性注入） | 外からもらう。new しない |
| DI コンテナ | 「この型にはこれを渡せ」という対応表 |
| Mock | 本物と同じ型を持つ偽物 |
| `Mockery::mock()` | Mock を作成 |
| `shouldReceive()` | 呼ばれるはずと宣言 |
| `shouldNotReceive()` | 呼ばれないはずと宣言 |
| `once()` / `twice()` / `times(n)` | 呼ばれる回数を指定 |
| `with()` | 引数を厳密に検証 |
| `Mockery::on()` | 引数を柔軟に検証（コールバック） |
| `withArgs()` | 複数引数をまとめて検証 |
| `$this->app->instance()` | DI コンテナに登録 |

**🐘ガネーシャ：** 「ほな、またな！」

**👩‍💻ユーザー & 🐘ガネーシャ：** 「はい、Oh, My God!!」 🙏