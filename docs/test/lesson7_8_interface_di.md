# Lesson 7-8: Interface を使った DI 🐘
## 〜より柔軟な実装の切り替え〜

---

## 🌿 ブランチ切り替えと準備

課題に取り組む前に、リモートの全てのブランチを取得してから、Lesson 用のブランチに切り替えてください：

```bash
# リモートの全てのブランチ情報を取得
git fetch origin

# Lesson用のブランチに切り替え
git checkout lesson7-8

# リモートの最新状態に更新
git pull origin lesson7-8
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

## 📁 このブランチで事前に変更されていること

**🐘ガネーシャ：** 「このブランチでは、前回の内容をベースに以下の変更がされてるで」

```
┌─────────────────────────────────────────────────────────────┐
│              lesson7-8 ブランチの事前変更                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  フォルダ構成の変更                                     │
│      app/Services/NotificationService.php                   │
│      ↓                                                      │
│      app/Services/Notification/LogNotificationService.php   │
│                                                             │
│  2️⃣  クラス名の変更                                         │
│      NotificationService → LogNotificationService           │
│      （ログ出力版であることを明確にするため）               │
│                                                             │
│  3️⃣  タスク関連の UseCase 全てに通知機能を追加              │
│      ・CreateTaskUseCase（タスク作成）                      │
│      ・StartTaskUseCase（タスク開始）                       │
│      ・CompleteTaskUseCase（タスク完了）                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎭 プロローグ：通知機能を完璧に実装した！

**👩‍💻ユーザー：** 「ガネーシャさん！前回学んだ DI と Mock を使って、タスク関連の機能全てに通知を実装しました！」

**🐘ガネーシャ：** 「おお、頑張ったな！どんな感じになったんや？」

**👩‍💻ユーザー：** 「タスクの作成、開始、完了、全部に通知を入れました！テストも Mock を使って完璧です！」

**🐘ガネーシャ：** 「ほう、ほな実際にファイルを見せてみ。ちゃんと実装できとるか確認したるわ」

**👩‍💻ユーザー：** 「はい！」

---

### 📝 実際にファイルを確認してみよう

**🐘ガネーシャ：** 「以下のファイルを開いて、通知機能が追加されてるか確認してみ」

```
📁 確認するファイル

1. app/Services/Notification/LogNotificationService.php
   → NotificationService から名前が変わってるで

2. app/UseCases/Task/CreateTaskUseCase.php
   → コンストラクタに LogNotificationService が追加されてる
   → execute() の中で notify('task_created', ...) を呼んでる

3. app/UseCases/Task/StartTaskUseCase.php
   → コンストラクタに LogNotificationService が追加されてる
   → execute() の中で notify('task_started', ...) を呼んでる

4. app/UseCases/Task/CompleteTaskUseCase.php
   → コンストラクタに LogNotificationService が追加されてる
   → execute() の中で notify('task_completed', ...) を呼んでる
```

**👩‍💻ユーザー：** 「確認しました！こんな感じです！」

---

### 📝 現在の実装状況

```php
<?php
// app/Services/Notification/LogNotificationService.php

class LogNotificationService
{
    public function notify(string $type, User $actor, array $payload): void
    {
        Log::info('[Notification]', [
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'payload' => $payload,
        ]);
    }
}
```

```php
<?php
// app/UseCases/Task/CreateTaskUseCase.php

class CreateTaskUseCase
{
    public function __construct(
        private LogNotificationService $notificationService,  // ← DI で受け取る
    ) {}

    public function execute(...): Task
    {
        // ... タスク作成処理 ...
        
        $this->notificationService->notify('task_created', $user, $task->toArray());
        
        return $task;
    }
}
```

```php
<?php
// app/UseCases/Task/StartTaskUseCase.php

class StartTaskUseCase
{
    public function __construct(
        private LogNotificationService $notificationService,  // ← DI で受け取る
    ) {}

    public function execute(...): Task
    {
        // ... タスク開始処理 ...
        
        $this->notificationService->notify('task_started', $user, $task->toArray());
        
        return $task;
    }
}
```

```php
<?php
// app/UseCases/Task/CompleteTaskUseCase.php

class CompleteTaskUseCase
{
    public function __construct(
        private LogNotificationService $notificationService,  // ← DI で受け取る
    ) {}

    public function execute(...): Task
    {
        // ... タスク完了処理 ...
        
        $this->notificationService->notify('task_completed', $user, $task->toArray());
        
        return $task;
    }
}
```

**👩‍💻ユーザー：** 「テストも全部 Mock を使って、通知が呼ばれることを確認してます！」

```php
<?php
// テストコード（例）

public function test_タスクを作成できる(): void
{
    $mock = Mockery::mock(LogNotificationService::class);
    $mock->shouldReceive('notify')->once();
    $this->app->instance(LogNotificationService::class, $mock);
    
    // ... テスト実行 ...
}
```

**🐘ガネーシャ：** 「おお、完璧やな！DI と Mock をしっかり使いこなしとる！」

**👩‍💻ユーザー：** 「えへへ、ありがとうございます！🎉」

---

### 😱 ガネーシャからの質問

**🐘ガネーシャ：** 「ところでな、ちょっと聞きたいことがあるんやけど...」

**👩‍💻ユーザー：** 「はい？」

**🐘ガネーシャ：** 「もし急に『**本番環境ではメールで通知を送りたい**』って言われたらどうする？」

**👩‍💻ユーザー：** 「え...えっと...`LogNotificationService` の中身を書き換えて、`Log::info()` を `Mail::send()` に変えれば...」

```php
<?php
// 👩‍💻ユーザーが考えた方法（単純に書き換え）

class LogNotificationService  // ← 🤔 あれ？クラス名は「Log」なのに...
{
    public function notify(string $type, User $actor, array $payload): void
    {
        // Log::info() を Mail::send() に変える
        Mail::raw("通知: {$type}", function ($message) use ($actor) {  // ← メール送信してる！
            $message->to($actor->email);
        });
    }
}
```

**🐘ガネーシャ：** 「ちょっと待ち。そのコード、なんかおかしくないか？」

**👩‍💻ユーザー：** 「え？」

**🐘ガネーシャ：** 「クラス名は `LogNotificationService` やのに、中身はメール送信しとるやん。**クラス名と実装内容が合ってない**んや」

**👩‍💻ユーザー：** 「あ...確かに。`LogNotificationService` なのにログ出力してない...」

**🐘ガネーシャ：** 「これは**嘘のクラス名**になっとる。他の開発者が見たら『ログ出力するんやな』と思うのに、実際はメール送信する。これはバグの温床やで」

```
┌─────────────────────────────────────────────────────────────┐
│              😱 クラス名と実装が合わない問題                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【クラス名】LogNotificationService                         │
│  【期待する動作】ログ出力                                   │
│  【実際の動作】メール送信                                   │
│                                                             │
│  ❌ クラス名が嘘になってる！                               │
│  ❌ 他の開発者が混乱する                                   │
│  ❌ コードを読んだだけでは何をするか分からない             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「じゃあクラス名を `MailNotificationService` に変えれば...」

**🐘ガネーシャ：** 「ほな、そうすると**UseCase のコードも全部書き換えなアカン**やん」

```php
<?php
// 😱 UseCase も全部書き換えが必要！

// CreateTaskUseCase.php
use App\Services\Notification\LogNotificationService;  // ❌ 削除
use App\Services\Notification\MailNotificationService; // ← 追加
private MailNotificationService $notificationService;  // ← 変更

// StartTaskUseCase.php
use App\Services\Notification\LogNotificationService;  // ❌ 削除
use App\Services\Notification\MailNotificationService; // ← 追加
private MailNotificationService $notificationService;  // ← 変更

// CompleteTaskUseCase.php
use App\Services\Notification\LogNotificationService;  // ❌ 削除
use App\Services\Notification\MailNotificationService; // ← 追加
private MailNotificationService $notificationService;  // ← 変更

// 😱 3つの UseCase で合計 6箇所も変更が必要！
```

**👩‍💻ユーザー：** 「うわ...UseCase が増えれば増えるほど、変更箇所が増えていく...」

**🐘ガネーシャ：** 「せやろ？しかも、開発環境ではログのままにしたい場合はどうするんや？」

**👩‍💻ユーザー：** 「うーん...じゃあ `LogNotificationService` の中に `if` 文を書いて、環境によって分岐させる...？」

```php
<?php
// 👩‍💻ユーザーが考えた方法（❌ イマイチ）

class LogNotificationService
{
    public function notify(string $type, User $actor, array $payload): void
    {
        if (app()->environment('production')) {
            // 本番はメール送信
            Mail::raw("通知: {$type}", function ($message) use ($actor) {
                $message->to($actor->email);
            });
        } else {
            // 開発はログ出力
            Log::info('[Notification]', [...]);
        }
    }
}
```

**🐘ガネーシャ：** 「ほな、さらに『**ステージング環境では Slack に通知したい**』って言われたら？」

**👩‍💻ユーザー：** 「えっと...また `if` 文を追加して...」

```php
<?php
// 👩‍💻ユーザーが考えた方法（❌ どんどん複雑に...）

class LogNotificationService
{
    public function notify(string $type, User $actor, array $payload): void
    {
        if (app()->environment('production')) {
            // 本番はメール送信
            Mail::raw(...);
        } elseif (app()->environment('staging')) {
            // ステージングは Slack 送信
            Http::post('https://slack.com/api/...', [...]);
        } else {
            // 開発はログ出力
            Log::info(...);
        }
    }
}
```

**👩‍💻ユーザー：** 「...なんか、どんどん複雑になってきました...😰」

**🐘ガネーシャ：** 「せやろ？しかもな、そもそも `LogNotificationService` っちゅう名前やのに、メールも Slack も送るようになったら、名前と中身が合ってないやん」

**👩‍💻ユーザー：** 「確かに...クラス名が嘘になっちゃいます...」

---

### 😱 さらにお客さんからの要望が...

**🐘ガネーシャ：** 「ほんでな、もう一つ聞きたいことがあるんやけど...」

**👩‍💻ユーザー：** 「まだあるんですか...？😨」

**🐘ガネーシャ：** 「このシステム、複数の会社に導入する予定やろ？」

**👩‍💻ユーザー：** 「はい、そうですけど...」

**🐘ガネーシャ：** 「もし**A社は『メールで通知して』**、**B社は『うちは Slack がいい』**、**C社は『Teams に送って』**って言われたらどうする？」

**👩‍💻ユーザー：** 「え...お客さんごとに違う通知媒体...！？」

```php
<?php
// 👩‍💻ユーザーが考えた方法（❌ もう無理...）

class LogNotificationService
{
    public function notify(string $type, User $actor, array $payload): void
    {
        if (app()->environment('production')) {
            // 本番環境だけど、お客さんごとに違う...？
            if (config('app.client') === 'company_a') {
                Mail::raw(...);
            } elseif (config('app.client') === 'company_b') {
                Http::post('https://slack.com/api/...', [...]);
            } elseif (config('app.client') === 'company_c') {
                Http::post('https://teams.microsoft.com/...', [...]);
            }
        } elseif (app()->environment('staging')) {
            // ステージングも同じく分岐...？
            // ...
        } else {
            Log::info(...);
        }
    }
}

// 😱 if 文の地獄...！！
```

**👩‍💻ユーザー：** 「もう無理です...if 文だらけで訳がわからなくなります...😭」

**🐘ガネーシャ：** 「せやろ？**環境の違い**と**お客さんの要望の違い**、両方に対応せなアカンのに、if 文で対応しようとすると破綻するんや」

```
┌─────────────────────────────────────────────────────────────┐
│              if 文で対応しようとすると...                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  😱 問題点                                                  │
│                                                             │
│  ・if 文がどんどん増えて複雑になる                         │
│  ・クラス名と中身が合わなくなる                            │
│  ・新しい通知媒体が増えるたびに既存コードを修正            │
│  ・テストも複雑になる                                      │
│  ・バグが入りやすくなる                                    │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 本当に欲しいのは...                                    │
│                                                             │
│  ・通知媒体ごとに別々のクラスを作りたい                    │
│  ・UseCase は「どの媒体か」を知らなくていい                │
│  ・設定を変えるだけで切り替えたい                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 💡 解決策は？

**👩‍💻ユーザー：** 「結局、どうすればいいんですか...？😭」

**🐘ガネーシャ：** 「ここまでの問題を整理すると、全部**具体的なクラスに依存している**ことが原因なんや」

```
┌─────────────────────────────────────────────────────────────┐
│              問題のまとめ                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  クラス名と実装が合わなくなる                           │
│      → LogNotificationService なのにメール送信...         │
│                                                             │
│  2️⃣  UseCase を全部書き換える必要がある                     │
│      → 3つの UseCase で合計6箇所の変更...                 │
│                                                             │
│  3️⃣  if 文がどんどん増えて複雑になる                        │
│      → 本番/ステージング/開発/お客さんごと...             │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 原因：UseCase が「LogNotificationService」という        │
│           具体的なクラスに直接依存しているから              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「もっとスマートな方法があるんやで」

**👩‍💻ユーザー：** 「教えてください！🙏」

**🐘ガネーシャ：** 「せやな。そこで**Interface**を使うんや！」

---

### 🎯 今日の目標

```
┌─────────────────────────────────────────────────────────────┐
│                  🎯 今日のゴール                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  Interface とは何か理解する                             │
│                                                             │
│  2️⃣  Interface を使った DI の実装方法を学ぶ                 │
│      → UseCase のコードを変えずに実装を切り替える          │
│                                                             │
│  3️⃣  AppServiceProvider での bind() の使い方を学ぶ          │
│      → 環境ごと・お客さんごとに違う実装を使い分ける        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📖 第1章：Interface って何？

### 🎭 Interface = 契約書

**🐘ガネーシャ：** 「Interface っちゅうのは、**『このメソッドを持っていなければならない』という契約書**や」

```
┌─────────────────────────────────────────────────────────────┐
│              Interface（インターフェース）とは？             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「このメソッドを持っていなければならない」という契約書     │
│                                                             │
│  Interface は「何ができるか」だけを定義する。               │
│  「どうやるか」は実装クラスが決める。                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「契約書...？ちょっとイメージが湧かないです」

**🐘ガネーシャ：** 「ほな、例え話で説明したるわ。ワシの教え子のエジソンくんが電気を発明した時の話やけどな...」

---

### 🔌 例え話：コンセント

**🐘ガネーシャ：** 「**コンセント**を想像してみ」

```
┌─────────────────────────────────────────────────────────────┐
│              Interface = コンセントの形 🔌                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【コンセント（Interface）】                                │
│  「2つの穴がある」という規格                                │
│                                                             │
│         ┌─────┐                                             │
│         │ ○ ○ │  ← この形に合えば何でも差し込める          │
│         └─────┘                                             │
│                                                             │
│  【差し込めるもの（実装クラス）】                           │
│  ・🌀 扇風機 → 風を送る                                    │
│  ・📺 テレビ → 映像を映す                                  │
│  ・🍚 炊飯器 → ご飯を炊く                                  │
│                                                             │
│  コンセントは「2つの穴に差し込める」という契約だけ決めて、 │
│  実際に何をするかは各家電が決める。                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あ！コンセントは『形』だけ決めてて、何をするかは家電次第ってことですね！」

**🐘ガネーシャ：** 「**完璧や！** これがまさに Interface の考え方や」

---

### 📝 通知サービスで考えると

**🐘ガネーシャ：** 「通知サービスで考えるとこうなるで」

```
┌─────────────────────────────────────────────────────────────┐
│              NotificationServiceInterface = コンセント      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【NotificationServiceInterface（契約）】                   │
│  「notify() メソッドがある」という規格                      │
│                                                             │
│  【差し込めるもの（実装クラス）】                           │
│  ・📝 LogNotificationService → ログ出力                    │
│  ・📧 MailNotificationService → メール送信                 │
│  ・💬 SlackNotificationService → Slack送信                 │
│                                                             │
│  UseCase は「notify() を呼べる」ことだけ知っていて、        │
│  実際にログなのかメールなのかは知らない（知らなくていい）。 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「UseCase は『notify() が呼べる』ことだけ知ってて、中身は気にしないんですね！」

**🐘ガネーシャ：** 「せや！これを**疎結合（そけつごう）**って呼ぶんや。密接に繋がってないから、切り替えが簡単になる」

---

### 📊 具体的なコードで見てみよう

**🐘ガネーシャ：** 「実際のコードを見てみよか」

#### Interface（契約書）

```php
<?php
// app/Services/Notification/NotificationServiceInterface.php

namespace App\Services\Notification;

use App\Models\User;

/**
 * 通知サービスの Interface（契約）
 * 
 * 「notify() メソッドを持っていること」という契約
 * 実装の詳細（ログ/メール/Slack）は決めない
 */
interface NotificationServiceInterface
{
    /**
     * 通知を送信する
     */
    public function notify(string $type, User $actor, array $payload): void;
}
```

**👩‍💻ユーザー：** 「`interface` っていうキーワードを使うんですね！中身は空っぽ...」

**🐘ガネーシャ：** 「せや。Interface は**『何ができるか』だけ**を定義する。**『どうやるか』は書かない**んや」

---

#### 実装クラス①：ログ版

**🐘ガネーシャ：** 「今ある `LogNotificationService` に `implements` を追加するだけやで」

```php
<?php
// app/Services/Notification/LogNotificationService.php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 通知サービス（ログ版）
 * 
 * 開発環境用。実際のメール送信はせず、ログに出力するだけ。
 */
class LogNotificationService implements NotificationServiceInterface  // ← 契約を守る宣言
{
    public function notify(string $type, User $actor, array $payload): void
    {
        // ログに出力
        Log::info('[Notification]', [
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'payload' => $payload,
        ]);
    }
}
```

**👩‍💻ユーザー：** 「`implements NotificationServiceInterface` で『この契約を守ります』って宣言してるんですね！」

**🐘ガネーシャ：** 「せや！`implements` は『実装する』っちゅう意味や。この契約を守るで、って宣言しとるんや」

**👩‍💻ユーザー：** 「もし契約を守らなかったらどうなるんですか？」

**🐘ガネーシャ：** 「**ええ質問や！** 実際に試してみよか。もし `notify()` メソッドを実装しなかったらどうなるか見てみ」

---

#### 🚨 契約を破るとエラーになる

**🐘ガネーシャ：** 「例えば、こんな風にメソッド名を間違えたり、引数を変えたりしたらどうなるか見てみ」

```php
<?php
// ❌ 契約違反の例

class BadNotificationService implements NotificationServiceInterface
{
    // ❌ メソッド名が違う（notify ではなく send）
    public function send(string $type, User $actor, array $payload): void
    {
        // ...
    }
}
```

**🐘ガネーシャ：** 「これを実行しようとすると、こんなエラーが出るで」

```
Fatal error: Class BadNotificationService contains 1 abstract method 
and must therefore be declared abstract or implement the remaining methods 
(NotificationServiceInterface::notify)
```

**👩‍💻ユーザー：** 「`notify` メソッドがないって怒られてますね！」

**🐘ガネーシャ：** 「せや。引数の型が違ってもエラーになるで」

```php
<?php
// ❌ 引数の型が違う例

class BadNotificationService implements NotificationServiceInterface
{
    // ❌ 第2引数が User ではなく int（契約違反）
    public function notify(string $type, int $userId, array $payload): void
    {
        // ...
    }
}
```

```
Fatal error: Declaration of BadNotificationService::notify(string $type, int $userId, array $payload): void 
must be compatible with NotificationServiceInterface::notify(string $type, User $actor, array $payload): void
```

**👩‍💻ユーザー：** 「引数の型も一致しないとダメなんですね！厳しい...」

**🐘ガネーシャ：** 「**これが Interface の力や！** 契約書通りに実装せんとエラーになるから、『うっかり違うメソッド名にしてた』みたいなバグを**コードを実行する前に**防げるんや」

```
┌─────────────────────────────────────────────────────────────┐
│              Interface が守ってくれること                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ メソッド名の一致を保証                                  │
│     → notify() を send() と間違えてもエラーで気づける      │
│                                                             │
│  ✅ 引数の型・数の一致を保証                                │
│     → User を int に変えてしまってもエラーで気づける       │
│                                                             │
│  ✅ 戻り値の型の一致を保証                                  │
│     → void を string に変えてしまってもエラーで気づける    │
│                                                             │
│  💡 コードを実行する前（コンパイル時）にエラーになる！      │
│     → 本番環境でバグが発覚...という事態を防げる           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！契約があるから、実装を間違えたらすぐに気づけるんですね！」

**🐘ガネーシャ：** 「せや！ワシの教え子のニュートンくんも言うとったで。『契約があるから安心して実装できる』ってな」

**👩‍💻ユーザー：** 「ニュートンくん、そんなこと言ってましたっけ...」

**🐘ガネーシャ：** 「...まぁええやん！大事なのは『implements したら契約通りに実装せなアカン』っちゅうことや！」

---

#### 実装クラス②：メール版

```php
<?php
// app/Services/Notification/MailNotificationService.php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * 通知サービス（メール版）
 * 
 * 本番環境用。実際にメールを送信する。
 */
class MailNotificationService implements NotificationServiceInterface  // ← 同じ契約を守る
{
    public function notify(string $type, User $actor, array $payload): void
    {
        // 実際にメールを送信
        Mail::raw("通知: {$type}", function ($message) use ($actor) {
            $message->to($actor->email)
                    ->subject('タスク通知');
        });
    }
}
```

**👩‍💻ユーザー：** 「こっちも同じ Interface を `implements` してる！でも中身は全然違う！」

**🐘ガネーシャ：** 「**そこがポイントや！** どっちも `notify()` メソッドを持っとるから、同じ『コンセント』に差し込める。でも実際にやることは違う」

---

### 📊 図解：Interface の仕組み

```
┌─────────────────────────────────────────────────────────────┐
│              Interface と実装クラスの関係                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                NotificationServiceInterface                 │
│                    （契約：notify() がある）                │
│                            △                                │
│                            │ implements（実装する）         │
│          ┌─────────────────┼─────────────────┐              │
│          │                 │                 │              │
│          ▼                 ▼                 ▼              │
│  LogNotification      MailNotification  SlackNotification   │
│  Service（ログ）      Service（メール）  Service（Slack）   │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  すべてのクラスが notify() を持っている                    │
│  → Interface という「契約」を守っている                    │
│  → どれを使っても UseCase は動く！                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📖 第2章：UseCase を Interface に依存させる

### 🎭 具体的なクラスではなく Interface に依存

**🐘ガネーシャ：** 「ほな、UseCase を修正するで。**具体的なクラス**やなくて、**Interface**に依存させるんや」

**👩‍💻ユーザー：** 「今は `LogNotificationService` に依存してますよね」

**🐘ガネーシャ：** 「せや。それを `NotificationServiceInterface` に変えるんや。**3つの UseCase 全部**な」

```php
<?php
// app/UseCases/Task/CompleteTaskUseCase.php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\Notification\NotificationServiceInterface;  // ← Interface を use
use App\Services\Project\ProjectRules;
use App\Exceptions\ConflictException;

class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
        private NotificationServiceInterface $notificationService,  // ← Interface 型！
    ) {}

    public function execute(Task $task, User $user): Task
    {
        $this->projectRules->ensureMember($task->project, $user);
        $this->ensureCanComplete($task);

        $task->status = 'done';
        $task->save();

        // notify() を呼ぶ
        // ログ版でもメール版でも、Interface を満たしていれば動く！
        $this->notificationService->notify('task_completed', $user, $task->toArray());

        $task->load('createdBy');

        return $task;
    }

    private function ensureCanComplete(Task $task): void
    {
        if (!$task->isDoing()) {
            throw new ConflictException('作業中のタスクのみ完了できます');
        }
    }
}
```

**👩‍💻ユーザー：** 「あ！`LogNotificationService` じゃなくて `NotificationServiceInterface` になってる！」

**🐘ガネーシャ：** 「せや。これで UseCase は『notify() が呼べる何か』に依存しとる状態になった。具体的に何が来るかは知らんけど、`notify()` さえ呼べればええんや」

**👩‍💻ユーザー：** 「CreateTaskUseCase と StartTaskUseCase も同じように変えるんですね！」

**🐘ガネーシャ：** 「せや！一度変えてしまえば、もう二度と UseCase のコードを触る必要はないんや」

---

### 🤔 でも、どの実装が渡されるの？

**👩‍💻ユーザー：** 「ちょっと待ってください。Interface は契約書ですよね？実際に動くのは実装クラスのはず...誰が『どの実装を使うか』を決めるんですか？」

**🐘ガネーシャ：** 「**ええ質問や！** それを決めるのが `AppServiceProvider` や！」

---

## 📖 第3章：AppServiceProvider で実装を切り替える

### 🎭 bind() で「どの実装を使うか」を決める

**🐘ガネーシャ：** 「`AppServiceProvider` っちゅうファイルで、『この Interface にはこの実装を使え』って設定するんや」

**ファイル**: `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\LogNotificationService;
use App\Services\Notification\MailNotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ============================================
        // NotificationServiceInterface が必要な時は
        // LogNotificationService（ログ版）を渡せ
        // ============================================
        $this->app->bind(
            NotificationServiceInterface::class,  // この Interface が必要な時は
            LogNotificationService::class         // この実装を使え
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
```

**👩‍💻ユーザー：** 「`bind()` で Interface と実装クラスを紐づけるんですね！」

**🐘ガネーシャ：** 「せや！これで Laravel は『NotificationServiceInterface が必要になったら、LogNotificationService を作って渡せばええんやな』って分かるんや」

---

### 📊 Laravel の動きを確認

```
┌─────────────────────────────────────────────────────────────┐
│              bind() を設定した時の Laravel の動き            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. API リクエストが来る                                    │
│                                                             │
│  2. Laravel が UseCase を作ろうとする                       │
│     → コンストラクタに NotificationServiceInterface がある  │
│                                                             │
│  3. Laravel「Interface が必要やな。DI コンテナに聞くで」   │
│                                                             │
│  4. DI コンテナ「bind() で登録されてる！」                 │
│     「LogNotificationService を作れって書いてある」         │
│                                                             │
│  5. Laravel「ほな LogNotificationService を new するわ」   │
│                                                             │
│  6. UseCase に LogNotificationService が渡される           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🎭 実装を切り替えてみよう

**🐘ガネーシャ：** 「本番環境でメール送信に切り替えたい時は、`bind()` の第2引数を変えるだけや！」

```php
<?php

public function register(): void
{
    // ============================================
    // 【開発環境】ログ版を使う
    // ============================================
    // $this->app->bind(
    //     NotificationServiceInterface::class,
    //     LogNotificationService::class
    // );

    // ============================================
    // 【本番環境】メール版を使う
    // ============================================
    $this->app->bind(
        NotificationServiceInterface::class,
        MailNotificationService::class  // ← ここを変えるだけ！
    );
}
```

**👩‍💻ユーザー：** 「UseCase のコードは1行も変えてないのに、動作が変わるんですね！」

**🐘ガネーシャ：** 「**そこがInterface のメリットや！** UseCase は『notify() を呼ぶ』ことだけ知っとって、実際にログなのかメールなのかは知らん。だから切り替えが簡単なんや」

**👩‍💻ユーザー：** 「しかも、UseCase が3つあっても、AppServiceProvider の1箇所を変えるだけで全部切り替わるんですね！」

**🐘ガネーシャ：** 「**そこや！** さっき言うてた『3つの UseCase 全部書き換える問題』が解決するんや」

---

### 📝 環境ごとに自動で切り替える

**🐘ガネーシャ：** 「実際には、環境変数を使って自動で切り替えることが多いで」

```php
<?php

public function register(): void
{
    // 環境変数で切り替え
    if (app()->environment('production')) {
        // 本番環境 → メール送信
        $this->app->bind(
            NotificationServiceInterface::class,
            MailNotificationService::class
        );
    } else {
        // 開発環境 → ログ出力
        $this->app->bind(
            NotificationServiceInterface::class,
            LogNotificationService::class
        );
    }
}
```

**👩‍💻ユーザー：** 「`app()->environment('production')` って何ですか？」

**🐘ガネーシャ：** 「ええ質問や！これは**現在の環境を判定する**メソッドなんやけど、その前に `.env` ファイルについて説明せなアカンな」

---

### 💡 .env ファイルとは？

**🐘ガネーシャ：** 「`.env` は**環境変数を設定するファイル**や。Laravel プロジェクトのルートにあるで」

```
┌─────────────────────────────────────────────────────────────┐
│              .env ファイルとは？                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  環境ごとに異なる設定値を管理するファイル                   │
│                                                             │
│  【例】                                                     │
│  ・APP_ENV（アプリの環境：local / production）             │
│  ・データベースの接続先                                     │
│  ・メールサーバーの設定                                     │
│  ・APIキーやシークレット                                    │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 ポイント                                                │
│  ・.env はGitにコミットしない（.gitignore に含まれる）     │
│  ・開発環境と本番環境で別々の .env を使う                  │
│  ・コードを変えずに設定だけ変えられる                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「プロジェクトのルートディレクトリにあるで」

```bash
# プロジェクトのルートディレクトリ
study-task-app/
├── .env           ← これ！
├── .env.example   ← サンプル（Git にコミットされる）
├── app/
├── config/
├── ...
```

**👩‍💻ユーザー：** 「あ、見たことあります！」

**🐘ガネーシャ：** 「`.env` の中身を見てみ」

```bash
# .env の中身（一部）

APP_NAME=StudyTaskApp
APP_ENV=local          # ← ここが環境！
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=study_task_app
# ...
```

**🐘ガネーシャ：** 「`APP_ENV=local` っちゅう設定があるやろ？`app()->environment('production')` は、この `APP_ENV` の値を見て判定しとるんや」

```php
<?php

// app()->environment() の動き

app()->environment('production')
// → APP_ENV が 'production' かどうかを判定

app()->environment('local')
// → APP_ENV が 'local' かどうかを判定
```

**👩‍💻ユーザー：** 「なるほど！開発環境では `APP_ENV=local`、本番環境では `APP_ENV=production` にするんですね！」

**🐘ガネーシャ：** 「**完璧や！** だから、本番サーバーの `.env` で `APP_ENV=production` にしとけば、自動的にメール送信に切り替わるんや」

```
┌─────────────────────────────────────────────────────────────┐
│              環境ごとの .env 設定                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【開発環境】.env                                           │
│  APP_ENV=local                                              │
│  → app()->environment('production') は false               │
│  → LogNotificationService が使われる                       │
│                                                             │
│  【本番環境】.env                                           │
│  APP_ENV=production                                         │
│  → app()->environment('production') は true                │
│  → MailNotificationService が使われる                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「コードを変えずに、`.env` の設定だけで切り替えられるんですね！」

**🐘ガネーシャ：** 「せや！これが Interface の威力や」

---

## 📖 第4章：テストで Mock に切り替える

### 🎭 instance() は bind() より優先される

**🐘ガネーシャ：** 「テストの時は、前回と同じように `instance()` を使うで」

**👩‍💻ユーザー：** 「あれ？`bind()` で設定してるのに、`instance()` で上書きできるんですか？」

**🐘ガネーシャ：** 「せや！前回も説明したけど、優先順位を思い出してみ」

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
│  ─────────────────────────────────────────────────          │
│                                                             │
│  instance() が最優先だから、テストで Mock を登録すると      │
│  bind() の設定より優先される！                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！`instance()` は `bind()` より強いんですね！」

---

### 📝 テストコードを書く

**🐘ガネーシャ：** 「テストコードはこうなるで」

```php
<?php

use App\Services\Notification\NotificationServiceInterface;
use Mockery;

/**
 * doing ステータスのタスクを完了できる
 */
public function test_doingステータスのタスクを完了できる(): void
{
    $task = Task::factory()->create([
        'project_id' => $this->project->id,
        'created_by' => $this->user->id,
        'status' => 'doing',
    ]);

    // ============================================
    // Mock を作成（Interface の Mock）
    // ============================================
    $mockNotification = Mockery::mock(NotificationServiceInterface::class);  // ← Interface を Mock
    $mockNotification
        ->shouldReceive('notify')
        ->once()
        ->with(
            'task_completed',
            Mockery::on(fn($user) => $user->id === $this->user->id),
            Mockery::on(fn($payload) => $payload['id'] === $task->id)
        );

    // ============================================
    // instance() で Mock を登録（bind() より優先される）
    // ============================================
    $this->app->instance(NotificationServiceInterface::class, $mockNotification);

    // API を実行
    $response = $this->actingAs($this->user)
        ->postJson("/api/tasks/{$task->id}/complete");

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'status' => 'done',
        ]
    ]);
}
```

**👩‍💻ユーザー：** 「前回と似てますね！違いは `LogNotificationService` じゃなくて `NotificationServiceInterface` を Mock してるところ...」

**🐘ガネーシャ：** 「**その通りや！** UseCase は Interface に依存してるから、Mock も Interface で作るんや」

---

### 📊 全体の流れを整理

```
┌─────────────────────────────────────────────────────────────┐
│              Interface による切り替えの全体図                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【AppServiceProvider で設定】                              │
│  bind(Interface → LogNotificationService)                   │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【開発環境・本番環境】                                     │
│                                                             │
│  UseCase が Interface を要求                               │
│       ↓                                                     │
│  DI コンテナ「bind() で登録されてる」                      │
│       ↓                                                     │
│  LogNotificationService（ログ版）が渡される                │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【テスト】                                                 │
│                                                             │
│  instance(Interface → Mock) を実行                         │
│       ↓                                                     │
│  UseCase が Interface を要求                               │
│       ↓                                                     │
│  DI コンテナ「instance() で登録されてる（優先！）」        │
│       ↓                                                     │
│  Mock が渡される                                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📖 第5章：実際にファイルを作成しよう

### 📁 ディレクトリ構成

**🐘ガネーシャ：** 「ほな、実際にファイルを作っていこか。フォルダは既にあるから、Interface を追加して、既存のファイルを修正するだけやで」

```
┌─────────────────────────────────────────────────────────────┐
│              作成・修正するファイル                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  app/                                                       │
│  ├── Services/                                              │
│  │   └── Notification/                    ← 既存           │
│  │       ├── NotificationServiceInterface.php  ← 新規作成  │
│  │       ├── LogNotificationService.php        ← 修正      │
│  │       └── MailNotificationService.php       ← 新規作成  │
│  │                                                          │
│  ├── Providers/                                             │
│  │   └── AppServiceProvider.php           ← 修正           │
│  │                                                          │
│  └── UseCases/                                              │
│      └── Task/                                              │
│          ├── CreateTaskUseCase.php        ← 修正           │
│          ├── StartTaskUseCase.php         ← 修正           │
│          └── CompleteTaskUseCase.php      ← 修正           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📝 Step 1: Interface を作成

**🐘ガネーシャ：** 「まずは Interface ファイルを作るで。ターミナルで touch コマンドを実行してな」

```bash
touch app/Services/Notification/NotificationServiceInterface.php
```

**ファイル**: `app/Services/Notification/NotificationServiceInterface.php`

```php
<?php

namespace App\Services\Notification;

use App\Models\User;

/**
 * 通知サービスの Interface
 * 
 * 「notify() メソッドを持っていること」という契約
 */
interface NotificationServiceInterface
{
    /**
     * 通知を送信する
     *
     * @param string $type    通知タイプ（例: 'task_completed'）
     * @param User   $actor   操作を実行したユーザー
     * @param array  $payload 通知に含めるデータ
     * @return void
     */
    public function notify(string $type, User $actor, array $payload): void;
}
```

---

### 📝 Step 2: ログ版の実装クラスを修正

**🐘ガネーシャ：** 「既存の `LogNotificationService` に `implements` を追加するだけやで」

**ファイル**: `app/Services/Notification/LogNotificationService.php`

```php
<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 通知サービス（ログ版）
 * 
 * 開発環境用。実際のメール送信はせず、ログに出力するだけ。
 */
class LogNotificationService implements NotificationServiceInterface  // ← 追加
{
    /**
     * 通知を送信する（ログ出力）
     */
    public function notify(string $type, User $actor, array $payload): void
    {
        Log::info('[Notification]', [
            'type' => $type,
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'payload' => $payload,
        ]);
    }
}
```

**🐘ガネーシャ：** 「前回作った `NotificationService` に`implements NotificationServiceInterface` を追加するんや」

---

### 📝 Step 4: メール版の実装クラスを作成

**🐘ガネーシャ：** 「次はメール版のクラスを作るで」

```bash
touch app/Services/Notification/MailNotificationService.php
```

**ファイル**: `app/Services/Notification/MailNotificationService.php`

```php
<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * 通知サービス（メール版）
 * 
 * 本番環境用。実際にメールを送信する。
 */
class MailNotificationService implements NotificationServiceInterface
{
    /**
     * 通知を送信する（メール送信）
     */
    public function notify(string $type, User $actor, array $payload): void
    {
        Mail::raw("通知タイプ: {$type}", function ($message) use ($actor) {
            $message->to($actor->email)
                    ->subject('タスク通知');
        });
    }
}
```

---

### 📝 Step 4: AppServiceProvider を修正

**ファイル**: `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\LogNotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // NotificationServiceInterface が必要な時は LogNotificationService を渡す
        $this->app->bind(
            NotificationServiceInterface::class,
            LogNotificationService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}

```

---

### 📝 Step 5: 3つの UseCase を修正

**🐘ガネーシャ：** 「3つの UseCase 全部、`LogNotificationService` を `NotificationServiceInterface` に変えるで」

**ファイル**: `app/UseCases/Task/CompleteTaskUseCase.php`

```php
<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
// use App\Services\Notification\LogNotificationService;  ← 削除
use App\Services\Notification\NotificationServiceInterface;  // ← 変更
use App\Services\Project\ProjectRules;
use App\Exceptions\ConflictException;

class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
        private NotificationServiceInterface $notificationService,  // ← 変更
    ) {}

    public function execute(Task $task, User $user): Task
    {
        $this->projectRules->ensureMember($task->project, $user);
        $this->ensureCanComplete($task);

        $task->status = 'done';
        $task->save();

        $this->notificationService->notify('task_completed', $user, $task->toArray());

        $task->load('createdBy');

        return $task;
    }

    private function ensureCanComplete(Task $task): void
    {
        if (!$task->isDoing()) {
            throw new ConflictException('作業中のタスクのみ完了できます');
        }
    }
}
```

---

### 📝 Step 6: テストを修正

**ファイル**: `tests/Feature/Api/TaskApiTest.php`

use 文を変更：

```php
// use App\Services\Notification\LogNotificationService; ← 削除
use App\Services\Notification\NotificationServiceInterface;  // ← 変更
use Mockery;
```

Mock の作成部分を変更：

```php
// 変更前
$mockNotification = Mockery::mock(LogNotificationService::class);

// 変更後
$mockNotification = Mockery::mock(NotificationServiceInterface::class);
```

instance() の登録部分を変更：

```php
// 変更前
$this->app->instance(LogNotificationService::class, $mockNotification);

// 変更後
$this->app->instance(NotificationServiceInterface::class, $mockNotification);
```

---

### 🎭 テストを実行

```bash
sail artisan test --filter=TaskApiTest
```

```
   PASS  Tests\Feature\Api\TaskApiTest
  ✓ タスクを作成できる                                       2.35s
  ✓ todoステータスのタスクを開始できる                       0.09s
  ✓ doingステータスのタスクを完了できる                      0.08s
  ✓ todoステータスのタスクは完了できない                     0.32s
  ...

  Tests:    8 passed (21 assertions)
  Duration: 3.98s
```

**👩‍💻ユーザー：** 「全部通った！🎉」

**🐘ガネーシャ：** 「Interface を使っても、テストはちゃんと動くやろ？」

---

## 📖 第6章：実際に切り替えて動作確認しよう

### 🎭 Interface の威力を体感する

**🐘ガネーシャ：** 「テストが通ったところで、**Interface の本当の威力**を体感してみよか」

**👩‍💻ユーザー：** 「本当の威力...？」

**🐘ガネーシャ：** 「今は `LogNotificationService`（ログ版）を使っとるやろ？これを `MailNotificationService`（メール版）に切り替えて、**たった1箇所の変更で全 UseCase に反映される**ことを確認するで」

**👩‍💻ユーザー：** 「おお！やってみたいです！」

---

### 📝 Step 1: Mailtrap を設定する

**🐘ガネーシャ：** 「本番環境では実際のメールサーバーを使うんやけど、教材では安全にテストするために **Mailtrap** を使うで」

```
┌─────────────────────────────────────────────────────────────┐
│              Mailtrap とは？ 📧                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  開発・テスト用のダミーメールサーバー                       │
│                                                             │
│  ✅ メールを送信しても、実際には届かない                   │
│  ✅ Mailtrap の管理画面で送信内容を確認できる              │
│  ✅ 本番のメールアドレスに誤送信する心配がない             │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 本番環境では...                                        │
│  ・SendGrid、Amazon SES、実際の SMTP サーバーなどを使う    │
│  ・今回は教材なので Mailtrap で安全に確認！                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！実際にメールが飛ばないから安心ですね」

---

#### Mailtrap のアカウント作成

1. https://mailtrap.io/ にアクセス
2. 「Sign Up」から無料アカウントを作成
3. ログイン後、「Email Testing」→「Inboxes」を選択
4. 「My Inbox」をクリック
5. 「SMTP Settings」タブで接続情報を確認

---

#### .env にメール設定を追加

**🐘ガネーシャ：** 「第3章で説明した `.env` ファイルに、メールの設定を追加するで」

**ファイル**: `.env`

```bash
# ============================================
# メール設定（Mailtrap 用）
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username  # ← Mailtrap の Username
MAIL_PASSWORD=your_mailtrap_password  # ← Mailtrap の Password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**🐘ガネーシャ：** 「`.env` を変更したら、設定のキャッシュをクリアして再生成するんやで」

```bash
sail artisan config:clear && sail artisan config:cache
```

**👩‍💻ユーザー：** 「これ必要なんですか？」

**🐘ガネーシャ：** 「Laravel は設定をキャッシュしとることがあるんや。`.env` を変えても反映されへん時があるから、毎回クリアしておくのが確実やで」

**🐘ガネーシャ：** 「`MAIL_USERNAME` と `MAIL_PASSWORD` は Mailtrap の管理画面からコピーしてな」

**👩‍💻ユーザー：** 「この設定は、`MailNotificationService` の中で使われるんですか？」

**🐘ガネーシャ：** 「せや！Laravel の `Mail::raw()` は、この `.env` の設定を見てメールを送るんや。コードには書かなくていい」

```php
<?php
// MailNotificationService の中

Mail::raw("通知: {$type}", function ($message) use ($actor) {
    $message->to($actor->email);
    // ↑ .env の MAIL_HOST, MAIL_USERNAME などを自動で使う
});
```

---

### 📝 Step 2: 現在の動作を確認（ログ版）

**🐘ガネーシャ：** 「まず、今の状態（ログ版）を確認しよか」

**ファイル**: `app/Providers/AppServiceProvider.php`（現在の状態）

```php
<?php

public function register(): void
{
    // 現在はログ版を使っている
    $this->app->bind(
        NotificationServiceInterface::class,
        LogNotificationService::class  // ← ログ版
    );
}
```

**🐘ガネーシャ：** 「API を実行する前に、データベースをリセットしておこか」

```bash
sail artisan migrate:refresh --seed
```

**🐘ガネーシャ：** 「ほな、Postman でタスク完了 API を実行してみ」

```
POST /api/tasks/3/complete
Authorization: Bearer {token}
```

**🐘ガネーシャ：** 「ログファイルを開いて確認してみ」

```
📁 storage/logs/laravel-YYYY-MM-DD.log
```

**💡 ヒント：** ログファイルは日付ごとに分かれてるで。今日の日付のファイルを開いてな。  
例：`storage/logs/laravel-2026-01-31.log`

ファイルを開くと、こんな感じのログが出力されてるはずや：

```
[2024-01-15 10:30:45] local.INFO: [Notification] {
    "type": "task_completed",
    "actor_id": 1,
    "actor_name": "山田太郎",
    "payload": { ... }
}
```

**👩‍💻ユーザー：** 「ログに出力されてますね！」

---

### 📝 Step 3: メール版に切り替える（1箇所だけ！）

**🐘ガネーシャ：** 「ほな、メール版に切り替えるで。**変更するのは AppServiceProvider の1箇所だけ**や！」

**ファイル**: `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\LogNotificationService;
use App\Services\Notification\MailNotificationService;  // ← 追加
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ============================================
        // ここを変えるだけで、全 UseCase に反映される！
        // ============================================
        $this->app->bind(
            NotificationServiceInterface::class,
            MailNotificationService::class  // ← LogNotificationService から変更！
        );
    }
}
```

**👩‍💻ユーザー：** 「本当に1行変えるだけ...！」

**🐘ガネーシャ：** 「せや。**UseCase は1つも触ってない**やろ？」

---

### 📝 Step 4: メール送信を確認

**🐘ガネーシャ：** 「データベースをリセットして、もう一回 API を実行してみ」

```bash
sail artisan migrate:refresh --seed
```

```
POST /api/tasks/3/complete
Authorization: Bearer {token}
```

**🐘ガネーシャ：** 「今度は Mailtrap の管理画面を見てみ」

**👩‍💻ユーザー：** 「あっ！メールが届いてる！🎉」

```
┌─────────────────────────────────────────────────────────────┐
│  📧 Mailtrap - My Inbox                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  From: noreply@example.com                                  │
│  To: user@example.com                                       │
│  Subject: タスク通知                                        │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  通知タイプ: task_completed                                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「すごい！AppServiceProvider を1行変えただけで、メール送信に切り替わった！」

---

### 📝 Step 5: 他の UseCase も確認

**🐘ガネーシャ：** 「データベースをリセットして、タスク作成とタスク開始も試してみ」

```bash
sail artisan migrate:refresh --seed
```

```
# タスク作成
POST /api/projects/{project_id}/tasks
→ Mailtrap に「task_created」のメールが届く

# タスク開始  
POST /api/tasks/4/start
→ Mailtrap に「task_started」のメールが届く
```

**👩‍💻ユーザー：** 「3つの UseCase 全部、メール送信に切り替わってる！どの UseCase も修正してないのに！」

**🐘ガネーシャ：** 「**これが Interface の威力や！**」

```
┌─────────────────────────────────────────────────────────────┐
│              Interface の威力を実感！                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【変更した箇所】                                           │
│  AppServiceProvider.php の1行だけ                          │
│                                                             │
│  【影響を受けた箇所】                                       │
│  ・CreateTaskUseCase（タスク作成）→ メール送信に切り替え  │
│  ・StartTaskUseCase（タスク開始）→ メール送信に切り替え   │
│  ・CompleteTaskUseCase（タスク完了）→ メール送信に切り替え│
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  UseCase のコードは1行も変更していない！                   │
│  → これが「疎結合」の力！                                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📝 Step 6: ログ版に戻す

**🐘ガネーシャ：** 「確認できたら、開発用にログ版に戻しておこか」

**ファイル**: `app/Providers/AppServiceProvider.php`

```php
<?php

public function register(): void
{
    // 開発中はログ版を使う
    $this->app->bind(
        NotificationServiceInterface::class,
        LogNotificationService::class  // ← 戻す
    );
}
```

**👩‍💻ユーザー：** 「これでまたログ出力に戻りましたね！」

**🐘ガネーシャ：** 「せや。本番にデプロイする時だけ `MailNotificationService` に変えればええんや」

---

### 🤔 あれ？そういえば...

**👩‍💻ユーザー：** 「あ、ちょっと待ってください。今ふと思ったんですけど...」

**🐘ガネーシャ：** 「ん？どしたん？」

**👩‍💻ユーザー：** 「**前回のレッスンでは AppServiceProvider に何も書いてなかった**のに、`LogNotificationService` とか `ProjectRules` とか、ちゃんと DI されてましたよね？なんで動いてたんですか？」

**🐘ガネーシャ：** 「**おお、ええところに気づいたな！** 実はな、Laravel は賢いから、**具体的なクラスの場合は何も設定しなくても勝手に DI してくれる**んや」

---

### 💡 Laravel のデフォルト DI（自動解決）

```
┌─────────────────────────────────────────────────────────────┐
│              Laravel のデフォルト DI（自動解決）             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【具体的なクラスの場合】                                   │
│  AppServiceProvider に何も書かなくても、Laravel が勝手に   │
│  new してくれる！                                          │
│                                                             │
│  例: LogNotificationService を DI する場合                 │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  public function __construct(                               │
│      private LogNotificationService $service  // 具体的クラス│
│  ) {}                                                       │
│                                                             │
│  → Laravel「LogNotificationService が必要やな」           │
│  → Laravel「特に設定ないけど、具体的なクラスやから」      │
│  → Laravel「そのまま new するわ」                         │
│  → new LogNotificationService() が渡される                 │
│                                                             │
│  ✅ AppServiceProvider に何も書かなくても動く！            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「だから前回は何も設定しなくても動いてたんですね！」

**🐘ガネーシャ：** 「せや。**具体的なクラス**を指定してる場合は、Laravel が『あ、このクラスを new すればええんやな』って分かるから、勝手にやってくれるんや」

**👩‍💻ユーザー：** 「あ！そういえば、Controller で `Request $request` とか書いても何も設定してなかったですよね？」

**🐘ガネーシャ：** 「**ええところに気づいたな！** 実は今まで使ってきたいろんなクラスも、全部 Laravel が勝手に DI してくれとったんや」

```php
<?php
// 今まで何も設定せずに DI されてたもの

// Controller でよく使う
public function store(StoreTaskRequest $request)  // ← FormRequest
{
    // ...
}

// UseCase でよく使う
class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,           // ← 自作の Service
        private LogNotificationService $service,      // ← 自作の Service
    ) {}
}

// Controller から UseCase を呼ぶ時
public function complete(Task $task, CompleteTaskUseCase $useCase)  // ← UseCase
{
    // ...
}
```

**👩‍💻ユーザー：** 「`Request` も `UseCase` も `Service` も、全部 Laravel が勝手に作ってくれてたんですね！」

**🐘ガネーシャ：** 「せや！今まで意識せずに使っとったけど、全部 **Laravel の DI コンテナ**が裏で動いとったんや」

```
┌─────────────────────────────────────────────────────────────┐
│              今まで Laravel が自動で DI してくれてたもの     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Controller のメソッド引数】                              │
│  ・Request $request                                         │
│  ・StoreTaskRequest $request（FormRequest）                │
│  ・Task $task（Route Model Binding）                       │
│  ・CompleteTaskUseCase $useCase                            │
│                                                             │
│  【UseCase のコンストラクタ】                               │
│  ・ProjectRules $projectRules                              │
│  ・LogNotificationService $notificationService             │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  全部、具体的なクラスだから Laravel が勝手に new してくれる│
│  AppServiceProvider に何も書かなくても動く！               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「今まで当たり前のように使ってたけど、裏では DI コンテナが動いてたんですね...！」

**🐘ガネーシャ：** 「せや。Laravel は賢いから、**具体的なクラス**やったら自動で解決してくれる。前回のレッスンのコードも同じや」

```php
<?php
// 前回のレッスンで動いてた理由

class CompleteTaskUseCase
{
    public function __construct(
        private LogNotificationService $service  // ← 具体的なクラス
    ) {}
}

// Laravel の動き:
// 1. LogNotificationService が必要やな
// 2. AppServiceProvider に設定...ないな
// 3. でも具体的なクラスやから、そのまま new するわ
// 4. new LogNotificationService() を渡す
// → 何も設定しなくても動く！
```

---

### 🤔 じゃあなぜ今回は AppServiceProvider が必要だった？

**👩‍💻ユーザー：** 「じゃあ、なんで今回は AppServiceProvider に `bind()` を書く必要があったんですか？」

**🐘ガネーシャ：** 「**Interface の場合は話が違う**んや」

```
┌─────────────────────────────────────────────────────────────┐
│              Interface の場合は設定が必要！                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Interface の場合】                                       │
│  Laravel は「どの実装を使えばいいか」分からない！          │
│                                                             │
│  例: NotificationServiceInterface を DI する場合           │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  public function __construct(                               │
│      private NotificationServiceInterface $service          │
│  ) {}                                                       │
│                                                             │
│  → Laravel「Interface が必要やな」                        │
│  → Laravel「Interface は new できへん...」                │
│  → Laravel「LogNotificationService？MailNotificationService？」│
│  → Laravel「どの実装を使えばええんや？」                  │
│  → ❌ エラー！                                             │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 だから AppServiceProvider で教えてあげる必要がある！   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あー！Interface は `new` できないから、『どの実装を使うか』を教えてあげないといけないんですね！」

**🐘ガネーシャ：** 「**完璧や！** 今までの流れを整理するとこうなる」

```
┌─────────────────────────────────────────────────────────────┐
│              まとめ：いつ AppServiceProvider が必要？        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【具体的なクラスを DI する場合】（今までのレッスン）      │
│  → AppServiceProvider に設定不要 ✅                        │
│  → Laravel が勝手に new してくれる                        │
│  → Request, UseCase, Service など全部これ                 │
│                                                             │
│  【Interface を DI する場合】（今回のレッスン）            │
│  → AppServiceProvider に bind() が必要 ⚠️                  │
│  → 「どの実装を使うか」を教える必要がある                 │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 Interface を使う = 柔軟に切り替えられる                │
│     その代わり「どれを使うか」を設定する必要がある        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど...！今まで Request とか UseCase とか、全部 Laravel が裏で DI してくれてたんですね。Interface を使う時だけ設定が必要なんだ」

**🐘ガネーシャ：** 「せやろ？具体的なクラスは Laravel が勝手にやってくれる。でも Interface は『どれを使うか』を設定せなアカン。**その代わり、さっき体験したみたいに自由に切り替えられるようになる**んや」

**👩‍💻ユーザー：** 「トレードオフなんですね！」

**🐘ガネーシャ：** 「**その通りや！** さすがワシの弟子やな」

---

### 💡 補足：本番環境での設定

```
┌─────────────────────────────────────────────────────────────┐
│              本番環境での設定について                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【今回の教材】                                             │
│  ・Mailtrap を使って安全にメール送信を確認                 │
│  ・実際のメールアドレスには届かない                        │
│                                                             │
│  【本番環境では】                                           │
│  ・SendGrid、Amazon SES、実際の SMTP サーバーを使う        │
│  ・.env の MAIL_* を本番用の設定に変更                     │
│  ・実際のユーザーにメールが届く                            │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 ポイント                                                │
│  ・MailNotificationService のコードは変えない              │
│  ・.env の設定を変えるだけで本番のメールサーバーを使える   │
│  ・Interface のおかげで、通知の「送り方」と「送り先」を    │
│    別々に管理できる！                                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「Interface で『ログかメールか』を切り替えて、.env で『どのメールサーバーを使うか』を設定するんですね！」

**🐘ガネーシャ：** 「完璧に理解しとるやん！さすガネーシャの弟子や！」

---

### 📝 Step 7: 環境ごとに自動切り替えに変更

**🐘ガネーシャ：** 「さて、今まで手動で切り替えてたけど、**本番では自動で切り替わるようにしたい**よな？」

**👩‍💻ユーザー：** 「そうですね！毎回手動で変えるのは忘れそうです...」

**🐘ガネーシャ：** 「せやろ？第3章で説明した `app()->environment()` を使って、環境ごとに自動切り替えするように書き換えるで」

---

#### AppServiceProvider を環境ごとに切り替える

**ファイル**: `app/Providers/AppServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Services\Notification\NotificationServiceInterface;
use App\Services\Notification\LogNotificationService;
use App\Services\Notification\MailNotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ============================================
        // 環境ごとに通知サービスを切り替え
        // ============================================
        if (app()->environment('production')) {
            // 本番環境 → メール送信
            $this->app->bind(
                NotificationServiceInterface::class,
                MailNotificationService::class
            );
        } else {
            // 開発環境（local）→ ログ出力
            $this->app->bind(
                NotificationServiceInterface::class,
                LogNotificationService::class
            );
        }
    }
}
```

**👩‍💻ユーザー：** 「第3章で見たコードですね！」

**🐘ガネーシャ：** 「せや。`.env` の `APP_ENV` を見て自動で切り替わるんや。ほな、実際に動作確認してみよか！」

---

#### 動作確認：APP_ENV を切り替えて体験しよう

**🐘ガネーシャ：** 「今から `.env` の `APP_ENV` を変えて、**ログ版とメール版が切り替わる**のを体験するで！」

**👩‍💻ユーザー：** 「おお！やってみたいです！」

---

##### Step 7-1: 現在の状態を確認（APP_ENV=local）

**🐘ガネーシャ：** 「まず、今の `.env` を確認してみ」

**ファイル**: `.env`

```bash
APP_ENV=local
```

**🐘ガネーシャ：** 「`APP_ENV=local` やから、`LogNotificationService`（ログ版）が使われるはずや。API を実行する前に、データベースをリセットしておこか」

```bash
sail artisan migrate:refresh --seed
```

**🐘ガネーシャ：** 「ほな、API を実行してみ」

```
POST /api/tasks/3/complete
Authorization: Bearer {token}
```

**🐘ガネーシャ：** 「ログファイルを確認してみ」

```
📁 storage/logs/laravel-YYYY-MM-DD.log
```

**💡 ヒント：** ログファイルは日付ごとに分かれてるで。今日の日付のファイルを開いてな。

```
[2024-01-15 10:30:45] local.INFO: [Notification] {
    "type": "task_completed",
    "actor_id": 1,
    "actor_name": "山田太郎",
    "payload": { ... }
}
```

**👩‍💻ユーザー：** 「ログに出力されてますね！Mailtrap にはメールは届いてないはず...」

**🐘ガネーシャ：** 「せや。`APP_ENV=local` やから `LogNotificationService` が使われて、ログ出力だけや」

---

##### Step 7-2: APP_ENV を production に変更

**🐘ガネーシャ：** 「ほな、`APP_ENV` を `production` に変えてみ」

**ファイル**: `.env`

```bash
APP_ENV=production
```

**🐘ガネーシャ：** 「`.env` を変えたら、キャッシュをクリアするんやで」

```bash
sail artisan config:clear && sail artisan config:cache
```

---

##### Step 7-3: メール版の動作を確認

**🐘ガネーシャ：** 「データベースをリセットして、もう一回 API を実行してみ」

```bash
sail artisan migrate:refresh --seed
```

```
POST /api/tasks/4/start
Authorization: Bearer {token}
```

**🐘ガネーシャ：** 「今度は Mailtrap の管理画面を見てみ」

**👩‍💻ユーザー：** 「あっ！メールが届いてる！🎉」

```
┌─────────────────────────────────────────────────────────────┐
│  📧 Mailtrap - My Inbox                                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  From: noreply@example.com                                  │
│  To: user@example.com                                       │
│  Subject: タスク通知                                        │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  通知タイプ: task_started                                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「`APP_ENV` を変えただけで、ログ版からメール版に切り替わった！」

**🐘ガネーシャ：** 「**これが環境変数と Interface の組み合わせの威力や！**」

```
┌─────────────────────────────────────────────────────────────┐
│              APP_ENV による自動切り替えを体験！              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【APP_ENV=local】                                          │
│  → LogNotificationService が使われる                       │
│  → ログに出力される ✅ 確認済み                           │
│                                                             │
│  【APP_ENV=production】                                     │
│  → MailNotificationService が使われる                      │
│  → Mailtrap にメールが届く ✅ 確認済み                    │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  ✅ PHP コードを1行も変えずに切り替え成功！                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

##### Step 7-4: APP_ENV を local に戻す

**🐘ガネーシャ：** 「確認できたら、開発用に `APP_ENV` を戻しておこか」

**ファイル**: `.env`

```bash
APP_ENV=local
```

```bash
sail artisan config:clear && sail artisan config:cache
```

**👩‍💻ユーザー：** 「これでまたログ出力に戻りましたね！」

**🐘ガネーシャ：** 「せや。**実務では本番サーバーにデプロイする時だけ `APP_ENV=production` になる**から、自動で切り替わるんや」

---

#### 💡 参考：実務での本番環境メール設定

**🐘ガネーシャ：** 「ちなみに、実務では本番環境のメール設定はこんな感じになるで」

```
┌─────────────────────────────────────────────────────────────┐
│              ⚠️ 参考：実務での本番環境メール設定             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【今回の教材】                                             │
│  ・開発も本番も Mailtrap を使用                            │
│  ・実際のメールアドレスには届かない（安全！）              │
│                                                             │
│  【実務の本番環境では】                                     │
│  ・Mailtrap は使わない                                     │
│  ・SendGrid、Amazon SES、実際の SMTP サーバーを設定       │
│  ・実際のユーザーにメールが届く                            │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 設定例（SendGrid の場合）                              │
│                                                             │
│  MAIL_MAILER=smtp                                           │
│  MAIL_HOST=smtp.sendgrid.net                               │
│  MAIL_PORT=587                                              │
│  MAIL_USERNAME=apikey                                       │
│  MAIL_PASSWORD=SG.xxxxxxxxxxxxxxxxxxxxxxxx                  │
│  MAIL_ENCRYPTION=tls                                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！Mailtrap は教材や開発用で、実務の本番では本物のメールサーバーを使うんですね」

**🐘ガネーシャ：** 「せや。実務では **MailNotificationService のコードは変えずに**、`.env` の設定を変えるだけで本番のメールサーバーを使えるようになるんや。今回の教材では Mailtrap 固定やけどな」

**👩‍💻ユーザー：** 「つまり、今回学んだのは `APP_ENV` で『ログかメールか』を切り替える部分ですね！」

**🐘ガネーシャ：** 「**その通りや！** 今回の教材ではそこを体験してもらったんや。実務では、さらに `.env` で『どのメールサーバーを使うか』も設定する。2段階で分かれとるんやで」

**👩‍💻ユーザー：** 「将来 Slack 通知を追加したい時も、`SlackNotificationService` を作って、if 文を追加するだけですね！」

**🐘ガネーシャ：** 「**完璧や！** お前はもう一人前のエンジニアやで！」

---

## 📖 第7章：前回との比較

### 📊 パターン A（Interface なし）vs パターン B（Interface あり）

**🐘ガネーシャ：** 「前回と今回の違いを整理するで」

```
┌─────────────────────────────────────────────────────────────┐
│              パターン A vs パターン B                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【パターン A: Interface なし（前回）】                     │
│                                                             │
│  UseCase → LogNotificationService（具体的なクラス）        │
│                                                             │
│  ✅ メリット                                                │
│  ・シンプルで分かりやすい                                   │
│  ・ファイル数が少ない                                       │
│  ・Mock テストは問題なくできる                             │
│                                                             │
│  ❌ デメリット                                              │
│  ・実装を切り替えるには UseCase のコードを変更する必要あり│
│  ・UseCase が複数あると、全部書き換えが必要               │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【パターン B: Interface あり（今回）】                     │
│                                                             │
│  UseCase → NotificationServiceInterface（契約）            │
│                    ↑                                        │
│            bind() で紐づけ                                  │
│                    ↓                                        │
│  LogNotificationService / MailNotificationService / etc...  │
│                                                             │
│  ✅ メリット                                                │
│  ・UseCase のコードを変えずに実装を切り替えられる          │
│  ・環境ごとに違う実装を使い分けられる                      │
│  ・AppServiceProvider の1箇所を変えれば全 UseCase に反映  │
│  ・テスト時も簡単に Mock に差し替えられる                  │
│                                                             │
│  ❌ デメリット                                              │
│  ・ファイル数が増える（Interface + 実装クラス）            │
│  ・最初は仕組みが分かりにくい                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🤔 どっちを使うべき？

**👩‍💻ユーザー：** 「結局、どっちを使えばいいんですか？」

**🐘ガネーシャ：** 「状況によるな。こう考えるとええで」

```
┌─────────────────────────────────────────────────────────────┐
│              どっちを使うべき？                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【パターン A（Interface なし）を選ぶ場合】                 │
│  ・実装が1つしかない（切り替える予定がない）               │
│  ・シンプルさを優先したい                                   │
│  ・小規模なプロジェクト                                     │
│                                                             │
│  【パターン B（Interface あり）を選ぶ場合】                 │
│  ・環境ごとに実装を切り替えたい                            │
│  ・将来的に実装が増える可能性がある                        │
│  ・外部サービス（メール、決済、API）を扱う                 │
│  ・チーム開発で明確な契約が欲しい                          │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  💡 迷ったら、最初はパターン A で始めて、                  │
│     必要になったらパターン B にリファクタリングするのもアリ│
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「なるほど！最初からパターン B にする必要はないんですね」

**🐘ガネーシャ：** 「せや。YAGNI（You Ain't Gonna Need It）っちゅう原則があってな、『必要になるまで作るな』って意味や。Interface も必要になってから作ればええんや」

---

## 📝 まとめ

### 🎯 今日学んだこと

```
┌─────────────────────────────────────────────────────────────┐
│                    📝 Lesson 7-8 まとめ                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣  Interface とは                                         │
│      → 「このメソッドを持っていること」という契約書        │
│      → 「何ができるか」だけ定義、「どうやるか」は書かない │
│      → コンセントの形🔌のようなもの                        │
│                                                             │
│  2️⃣  AppServiceProvider の bind() と .env                   │
│      → 「この Interface にはこの実装を使え」と設定         │
│      → app()->environment() で .env の APP_ENV を判定      │
│      → 環境変数（.env）で自動切り替えが可能               │
│                                                             │
│  3️⃣  Interface を使うメリット                               │
│      → UseCase のコードを変えずに実装を切り替えられる      │
│      → 環境ごとに違う実装を使い分けられる                  │
│      → 疎結合になり、テストしやすくなる                    │
│                                                             │
│  4️⃣  テストでの instance()                                  │
│      → bind() より instance() が優先される                 │
│      → テスト時だけ Mock に差し替えられる                  │
│                                                             │
│  5️⃣  実際に切り替えて体感！                                 │
│      → 手動で切り替え → 全 UseCase に反映された           │
│      → Mailtrap でメール送信も安全に確認できた            │
│                                                             │
│  6️⃣  Laravel のデフォルト DI との違い                       │
│      → 具体的なクラス：AppServiceProvider 設定不要        │
│      → Interface：bind() で「どの実装か」を教える必要あり│
│      → Interface を使う = 柔軟性とのトレードオフ          │
│                                                             │
│  7️⃣  環境ごとに自動切り替え                                 │
│      → app()->environment('production') で判定            │
│      → 本番は実際のメールサーバーを .env に設定           │
│                                                             │
│  8️⃣  パターン A vs パターン B                               │
│      → 状況に応じて使い分ける                              │
│      → 迷ったらシンプルな方から始める                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 全体像

```
┌─────────────────────────────────────────────────────────────┐
│              Interface を使った DI の全体像                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                NotificationServiceInterface                 │
│                    （契約：notify() がある）                │
│                            △                                │
│                            │ implements                     │
│          ┌─────────────────┼─────────────────┐              │
│          │                 │                 │              │
│          ▼                 ▼                 ▼              │
│  LogNotification      MailNotification  Mock（テスト用）    │
│  Service（ログ）      Service（メール）                     │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【AppServiceProvider + app()->environment()】              │
│  APP_ENV=local → LogNotificationService                    │
│  APP_ENV=production → MailNotificationService              │
│                                                             │
│  【テスト】                                                 │
│  instance(Interface → Mock) で上書き（優先）               │
│                                                             │
│  ─────────────────────────────────────────────────          │
│                                                             │
│  【UseCase】                                                │
│  Interface にだけ依存（具体的な実装は知らない）            │
│  → どの実装が来ても動く！                                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🍨 おまけ：今回のキーワード

| キーワード | 意味 |
|------------|------|
| Interface | 「このメソッドを持っていること」という契約 |
| implements | 「この契約を守ります」という宣言 |
| 疎結合 | 密接に繋がっていない状態。切り替えが簡単 |
| bind() | Interface と実装クラスを紐づける |
| instance() | 特定のオブジェクトを登録する（bind より優先） |
| Mailtrap | 開発・テスト用のダミーメールサーバー |
| YAGNI | 必要になるまで作るな |

---

**🐘ガネーシャ：** 「これで DI の基本は完璧や！Interface を使いこなせるようになったら、もう一人前のエンジニアやで」

**👩‍💻ユーザー：** 「ありがとうございます！『new しない、外からもらう』『Interface で契約を決める』が大事なんですね！」

**🐘ガネーシャ：** 「その通りや！さすガネーシャや！🐘✨」

**👩‍💻ユーザー & 🐘ガネーシャ：** 「はい、Oh, My God!!」 🙏
