# Lesson8: try-catch 乱用地獄 🐘
## 〜「とりあえず try-catch」の落とし穴〜

---

## 🎭 プロローグ：安心感を求めた結果

**👩‍💻ユーザー：** 「ガネーシャさん！前回教えてもらった try-catch を使ってみました！」

**🐘ガネーシャ：** 「おお、どんな感じや？」

**👩‍💻ユーザー：** 「エラーが起きても大丈夫なように、メソッド全体を try-catch で囲みました！」

```php
public function complete(Request $request, Task $task)
{
    try {
        // 権限チェック
        $isMember = $task->project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }

        // 状態チェック
        if ($task->status !== 'doing') {
            return response()->json([
                'message' => '作業中のタスクのみ完了できます',
            ], 409);
        }

        $task->update(['status' => 'done']);
        $task->load('createdBy');

        return new TaskResource($task);

    } catch (Exception $e) {
        return response()->json([
            'message' => 'エラーが発生しました',
        ], 500);
    }
}
```

**👩‍💻ユーザー：** 「これで予期しないエラーが起きても安心ですよね！」

**🐘ガネーシャ：** 「...お前、この try-catch、意味ないで」

**👩‍💻ユーザー：** 「えっ！？」

---

## 📖 第1章：デフォルトと変わらない try-catch

### 🎭 何が問題なのか

**🐘ガネーシャ：** 「さっきのコード、try-catch を外したらどうなると思う？」

**👩‍💻ユーザー：** 「えっと...エラーが起きたらプログラムが止まる...？」

**🐘ガネーシャ：** 「違うで。Lesson7 で教えたやろ。**Laravel が自動で catch してくれる**んや」

```
┌─────────────────────────────────────────────────────────────┐
│              try-catch を書かなくても...                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【try-catch なし】                                          │
│  例外発生 → Laravel が catch → 500エラーを返す              │
│                                                             │
│  【try-catch あり（お前のコード）】                          │
│  例外発生 → catch → 500エラーを返す                         │
│                                                             │
│  → 結果、同じやん！                                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あ...確かに、どっちも500エラーを返すだけ...」

**🐘ガネーシャ：** 「せや。しかも、お前のコードの方が**悪い**まであるで」

---

### 🔍 むしろ悪くなるパターン

**🐘ガネーシャ：** 「実は、try-catch を書いたせいで問題が起きることがあるんや」

```php
public function complete(Request $request, Task $task)
{
    try {
        // ... 色々な処理 ...

        // ここで ModelNotFoundException が発生したとする
        $project = Project::findOrFail($projectId);

    } catch (Exception $e) {
        // 全部 500 になる！
        return response()->json([
            'message' => 'エラーが発生しました',
        ], 500);
    }
}
```

**問題点：**

```
┌─────────────────────────────────────────────────────────────┐
│           try-catch を書いたせいで起きる問題                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【try-catch なしの場合】                                    │
│  ModelNotFoundException → Laravel が 404 を返す ✅           │
│                                                             │
│  【try-catch ありの場合（お前のコード）】                    │
│  ModelNotFoundException → catch で捕まる                    │
│                        → 500 を返す ❌                      │
│                                                             │
│  本来 404 で返すべきものが 500 になってしまう！              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「えっ、try-catch を書いたせいで、正しいステータスコードが返らなくなる...？」

**🐘ガネーシャ：** 「せや。Laravel のデフォルト処理の方が賢いんや」

---

### 📊 Laravel のデフォルト vs 雑な try-catch

| 例外 | Laravel デフォルト | 雑な try-catch |
|------|-------------------|----------------|
| `ModelNotFoundException` | **404** | 500 ❌ |
| `ValidationException` | **422** | 500 ❌ |
| `AuthenticationException` | **401** | 500 ❌ |
| `QueryException` | **500** + 詳細ログ | 500（詳細なし）❌ |
| その他の例外 | **500** + 詳細ログ | 500（詳細なし）❌ |

**🐘ガネーシャ：** 「Laravel のデフォルトは、例外の種類に応じて適切なステータスコードを返してくれる。でも雑な try-catch で全部捕まえると、全部 500 になってしまうんや」

**👩‍💻ユーザー：** 「しかも開発環境なら `APP_DEBUG=true` で詳細も見れるんですよね」

**🐘ガネーシャ：** 「せや！Laravel に任せた方が、ステータスコードもログもスタックトレースも全部ちゃんと出してくれる。自分で雑に書くより賢いんや」

---

## 💀 第2章：エラーの「握りつぶし」

### 🎭 もう一つの問題

**🐘ガネーシャ：** 「さっきのコード、もう一つ問題があるで。ログを見てみ」

**👩‍💻ユーザー：** 「ログ...？」

```php
catch (Exception $e) {
    return response()->json([
        'message' => 'エラーが発生しました',
    ], 500);
    // ← ログを記録していない！
}
```

**🐘ガネーシャ：** 「エラーが起きても、**何が起きたか分からない**んや」

```
┌─────────────────────────────────────────────────────────────┐
│                    握りつぶしの問題                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Laravel デフォルト】                                      │
│  例外発生 → ログに詳細が記録される                          │
│         → storage/logs/laravel.log で確認できる             │
│                                                             │
│  【雑な try-catch】                                          │
│  例外発生 → catch で捕まえる                                │
│         → ログに何も残らない                                │
│         → 何が起きたか分からない！                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「あ...本番で問題が起きても、原因が分からない...」

**🐘ガネーシャ：** 「これを『握りつぶし』って言うんや。エラーを catch して、何もしないでなかったことにしてしまう」

---

### 🔍 握りつぶしのバリエーション

```php
// ❌ パターン1：ログなし（お前のコード）
catch (Exception $e) {
    return response()->json(['message' => 'エラー'], 500);
}

// ❌ パターン2：完全に空
catch (Exception $e) {
    // 何もしない
}

// ❌ パターン3：コメントだけ
catch (Exception $e) {
    // TODO: あとで対応する
}

// ❌ パターン4：エラーなのに成功を返す
catch (Exception $e) {
    return response()->json(['message' => '成功しました'], 200);  // 嘘！
}
```

**🐘ガネーシャ：** 「全部アウトや。catch したなら、最低限ログは記録せなアカン」

---

### ✅ catch するなら最低限やること

```php
// ✅ 最低限：ログを記録する
catch (Exception $e) {
    Log::error('タスク完了エラー', [
        'task_id' => $task->id,
        'user_id' => $request->user()->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'message' => 'エラーが発生しました',
    ], 500);
}
```

**👩‍💻ユーザー：** 「ログを記録すれば、後から原因を調べられるんですね」

**🐘ガネーシャ：** 「せや。でもな...」

---

## 🤔 第3章：そもそも try-catch を書く必要があるのか？

### 🎭 立ち止まって考える

**🐘ガネーシャ：** 「ここで一回立ち止まって考えてみ。そもそも、その try-catch は必要か？」

```php
// お前が書いたコード
public function complete(Request $request, Task $task)
{
    try {
        // ... 処理 ...
    } catch (Exception $e) {
        Log::error(...);
        return response()->json(['message' => 'エラー'], 500);
    }
}
```

```php
// try-catch を書かない場合
public function complete(Request $request, Task $task)
{
    // ... 処理 ...
    
    // 例外が起きたら Laravel が：
    // 1. ログに記録する ✅
    // 2. 適切なステータスコードを返す ✅
}
```

**👩‍💻ユーザー：** 「あれ...Laravel のデフォルトで十分...？」

**🐘ガネーシャ：** 「せや！わざわざ自分で書く必要ないんや」

---

### 🔑 try-catch を書く意味

**🐘ガネーシャ：** 「ここ大事やで。try-catch は『あえて書く』もんなんや」

```
┌─────────────────────────────────────────────────────────────┐
│              try-catch を書く意味                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  try-catch は「デフォルトとは違う処理をしたい時」に書く     │
│                                                             │
│  【デフォルトと違う処理の例】                                │
│  ├── 独自のログを記録したい                                 │
│  ├── こちらで指定したエラーメッセージを返したい             │
│  ├── 失敗しても処理を続けたい                               │
│  ├── リトライしたい                                         │
│  └── ロールバック処理を入れたい                             │
│                                                             │
│  【そういうのがないなら】                                    │
│  → Laravel のデフォルト処理に任せる                         │
│  → try-catch は書かなくてOK                                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「『とりあえず安心のため』じゃなくて、『明確な目的があるから書く』んですね」

**🐘ガネーシャ：** 「その通りや！目的がないなら書く必要ない。Laravel に任せた方が賢いんや」

---

### 📊 判断基準：try-catch を書くべきか？

```
┌─────────────────────────────────────────────────────────────┐
│            try-catch を書くべきか？判断基準                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Q1: エラーが起きても処理を続けたい？                        │
│      → Yes: try-catch を書く                                │
│      → No: 書かなくてOK                                     │
│                                                             │
│  Q2: デフォルトと違うエラーメッセージを返したい？            │
│      → Yes: try-catch を書く                                │
│      → No: 書かなくてOK                                     │
│                                                             │
│  Q3: 外部APIなど、特別な失敗処理が必要？                     │
│      → Yes: try-catch を書く                                │
│      → No: 書かなくてOK                                     │
│                                                             │
│  全部 No なら → try-catch は不要！                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏗️ 第4章：UseCase に切り分けた場合

### 🎭 新たな問題

**🐘ガネーシャ：** 「ほな次や。お前、UseCase への切り分けは終わっとるんやったな」

**👩‍💻ユーザー：** 「はい！でも、同じように try-catch を書いてしまいました...」

```php
// CompleteTaskUseCase.php
class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    public function execute(Task $task, User $user)
    {
        try {
            // 権限チェック（Serviceに委譲）
            $result = $this->projectRules->ensureMember($task->project, $user);
            if ($result !== true) {
                return $result;  // ❌ response を返してしまう
            }

            // 状態チェック（privateメソッド）
            $result = $this->ensureCanComplete($task);
            if ($result !== true) {
                return $result;  // ❌ response を返してしまう
            }

            $task->update(['status' => 'done']);
            $task->load('createdBy');

            return new TaskResource($task);

        } catch (Exception $e) {
            Log::error('タスク完了エラー: ' . $e->getMessage());
            return response()->json(['message' => 'エラー'], 500);
        }
    }

    private function ensureCanComplete(Task $task)
    {
        if (!$task->isDoing()) {
            // ❌ throw ではなく response を返してしまう
            return response()->json([
                'message' => '作業中のタスクのみ完了できます',
            ], 409);
        }
        return true;
    }
}
```

```php
// ProjectRules.php（Serviceに委譲した権限チェック）
class ProjectRules
{
    public function ensureMember(Project $project, User $user)
    {
        $isMember = $project->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$isMember) {
            // ❌ こっちも throw ではなく response を返してしまう
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }
        return true;
    }
}
```

**👩‍💻ユーザー：** 「ちゃんと Service に切り分けて、private メソッドも作りました！」

**🐘ガネーシャ：** 「切り分けはできとる。でもな、**private メソッドや Service で response を返しとる**のが問題なんや」

**👩‍💻ユーザー：** 「えっ、切り分けたのにダメなんですか？」

**🐘ガネーシャ：** 「せや。切り分けても response を返しとったら意味ないんや。UseCase も Service も HTTP のことを知ったらアカン」

---

### 🔍 UseCase で response を返す問題

```
┌─────────────────────────────────────────────────────────────┐
│            UseCase で response を返す問題                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ 責務の混在                                              │
│     UseCase が「ビジネスロジック」と「HTTP通信」の           │
│     両方を知ってしまっている                                │
│                                                             │
│  2️⃣ テストしにくい                                          │
│     response()->json() の結果を検証するのは面倒             │
│                                                             │
│  3️⃣ 再利用できない                                          │
│     CLI から使いたい時、JSON レスポンスは要らない           │
│                                                             │
│  4️⃣ 戻り値の型が不安定                                      │
│     成功時: TaskResource                                    │
│     失敗時: JsonResponse                                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「UseCase は HTTP のことを知ったらアカンのや」

**👩‍💻ユーザー：** 「じゃあ、どうすればいいんですか？」

**🐘ガネーシャ：** 「`throw` を使うんや。でもその前に、throw について詳しく教えたるわ」

---

## 📖 第5章：throw を理解しよう

### 🎭 return と throw の違い

**🐘ガネーシャ：** 「まず return と throw の違いを理解せなアカン」

#### return は「普通に帰る」

```php
private function ensureCanComplete(Task $task)
{
    if (!$task->isDoing()) {
        return response()->json(['message' => '完了できません'], 409);
    }
    return true;
}

// 使う側
$result = $this->ensureCanComplete($task);
if ($result !== true) {  // ← 毎回チェックが必要
    return $result;
}
```

**問題点：**
- 呼び出し側が「戻り値をチェックする責任」がある
- チェックを忘れるとバグになる

---

#### throw は「緊急脱出」

```php
private function ensureCanComplete(Task $task): void
{
    if (!$task->isDoing()) {
        throw new Exception('作業中のタスクのみ完了できます');
    }
}

// 使う側
$this->ensureCanComplete($task);  // エラーなら例外が飛ぶ
// ここに来たら正常 ← チェック不要！
```

**メリット：**
- 呼び出し側はチェック不要
- 忘れても例外が勝手に飛ぶ

---

### 📊 return vs throw 比較

```
┌─────────────────────────────────────────────────────────────┐
│                 return vs throw                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【return】                                                  │
│  ├── 値を返す                                               │
│  ├── 呼び出し元は戻り値をチェックする必要がある            │
│  ├── チェックを忘れるとバグになる                          │
│  └── 「正常系の一部」として扱われる                        │
│                                                             │
│  【throw】                                                   │
│  ├── 例外を投げる                                           │
│  ├── 呼び出し元は強制的に対応させられる                    │
│  ├── 無視できない（catch されるまで飛び続ける）            │
│  └── 「異常系」として明確に扱われる                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「return は『無視できる』けど、throw は『無視できない』んですね！」

**🐘ガネーシャ：** 「せや！だからエラーを確実に伝えたい時は throw を使うんや」

---

### 🎯 throw すると何が起きるか

**🐘ガネーシャ：** 「throw すると、そこから呼び出し元に向かって『巻き戻り』が始まるんや」

```
┌─────────────────────────────────────────────────────────────┐
│                 throw の動き                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   Controller                                                │
│   ├── UseCase を呼ぶ                                        │
│   │   ├── ensureCanComplete() を呼ぶ                        │
│   │   │   └── throw new Exception() 💥                      │
│   │   │                                                     │
│   │   │       ↑ ここから「巻き戻り」が始まる               │
│   │   │                                                     │
│   │   └── 以降の処理は実行されない                         │
│   └── 以降の処理は実行されない                             │
│                                                             │
│   → catch されるまで上に登っていく                         │
│   → catch がなければ Laravel が自動で catch                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** 「catch がなくても Laravel が受け取ってくれるんですね！」

**🐘ガネーシャ：** 「せや！だから UseCase で catch を書く必要ないんや」

---

### 🔑 いつ throw を使うか

```
┌─────────────────────────────────────────────────────────────┐
│                 throw を使う場面                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ throw を使うべき場面                                    │
│  ├── 権限がない（処理を続けてはいけない）                  │
│  ├── ビジネスルール違反（状態遷移エラーなど）              │
│  ├── データが見つからない（存在しないデータへの操作）      │
│  └── 呼び出し元に「確実に」エラーを伝えたい                │
│                                                             │
│  ❌ throw を使わない方がいい場面                            │
│  ├── 検索結果が0件 → return [] でOK                        │
│  └── 予想される失敗で、代替処理がある場合                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 第6章：正しい方法 - UseCase では throw だけ！

### 🎭 考え方を変える

**🐘ガネーシャ：** 「UseCase でのエラー処理、考え方を変えるで」

```
┌─────────────────────────────────────────────────────────────┐
│                 UseCase でのエラー処理                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ❌ 間違い                                                  │
│     UseCase で try-catch して response を返す               │
│                                                             │
│  ✅ 正解                                                    │
│     UseCase では throw だけ                                 │
│     catch は書かない                                        │
│     Laravel が自動で catch してくれる                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🎯 Before / After

#### ❌ Before：切り分けたけど response を返してしまう

```php
class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    public function execute(Task $task, User $user)
    {
        try {
            // 切り分けてるけど...
            $result = $this->projectRules->ensureMember($task->project, $user);
            if ($result !== true) {
                return $result;  // ❌ response がそのまま返る
            }

            $result = $this->ensureCanComplete($task);
            if ($result !== true) {
                return $result;  // ❌ response がそのまま返る
            }

            $task->update(['status' => 'done']);
            $task->load('createdBy');
            return new TaskResource($task);

        } catch (Exception $e) {
            Log::error('エラー: ' . $e->getMessage());
            return response()->json(['message' => 'エラー'], 500);
        }
    }

    // ❌ throw ではなく response を返してしまう
    private function ensureCanComplete(Task $task)
    {
        if (!$task->isDoing()) {
            return response()->json([
                'message' => '作業中のタスクのみ完了できます',
            ], 409);
        }
        return true;
    }
}
```

**👩‍💻ユーザー：** 「切り分けはしたんですけど...」

**🐘ガネーシャ：** 「private メソッドで response を返しとるやろ。これやと HTTP の知識が UseCase に漏れてしまっとる」

---

#### ✅ After：throw だけ、catch は書かない

```php
<?php

namespace App\UseCases\Task;

use App\Models\Task;
use App\Models\User;
use App\Services\Project\ProjectRules;
use Exception;

class CompleteTaskUseCase
{
    public function __construct(
        private ProjectRules $projectRules,
    ) {}

    /**
     * @return Task  ← 戻り値は常に Task！
     */
    public function execute(Task $task, User $user): Task
    {
        // 1. 権限チェック（例外を投げる）
        $this->projectRules->ensureMember($task->project, $user);

        // 2. 状態チェック（例外を投げる）
        $this->ensureCanComplete($task);

        // 3. 状態変更
        $task->status = 'done';
        $task->save();

        // 4. リレーションロード
        $task->load('createdBy');

        return $task;
    }

    private function ensureCanComplete(Task $task): void
    {
        if (!$task->isDoing()) {
            throw new Exception('作業中のタスクのみ完了できます');
        }
    }
}
```

```php
// ProjectRules.php
class ProjectRules
{
    public function ensureMember(Project $project, User $user): void
    {
        $isMember = $project->users()
            ->where('users.id', $user->id)
            ->exists();

        if (!$isMember) {
            throw new Exception('このプロジェクトにアクセスする権限がありません');
        }
    }
}
```

```php
// Controller
public function complete(Request $request, Task $task): TaskResource
{
    $task = $this->completeTaskUseCase->execute($task, $request->user());
    return new TaskResource($task);
}
```

**👩‍💻ユーザー：** 「あれ、でも Exception だと全部 500 エラーになりませんか？」

**🐘ガネーシャ：** 「ええ質問や！確かに今のままやと全部 500 になる。403 や 409 を返したい場合は『カスタム例外』を作るんやけど、それは次回のレッスンで教えたるわ。今日は『throw を使う』ことを覚えてくれ」

---

### 📊 Before / After 比較

| 項目 | Before | After |
|------|--------|-------|
| try-catch | あり | **なし** |
| response() | 3箇所 | **0箇所** |
| 戻り値の型 | `TaskResource \| JsonResponse` | **`Task`** |
| HTTP の知識 | UseCase が知っている | **Controller だけ** |
| テストしやすさ | 低い | **高い** |

**👩‍💻ユーザー：** 「めっちゃスッキリ！try-catch がなくなった！」

**🐘ガネーシャ：** 「せやろ？throw するだけで、あとは Laravel に任せるんや」

---

## 🎯 第7章：try-catch が本当に必要な場面

### 🎭 じゃあ、いつ使うの？

**👩‍💻ユーザー：** 「try-catch をあまり使わない方がいいのは分かりました。でも、いつ使えばいいんですか？」

**🐘ガネーシャ：** 「ええ質問や。本当に必要な場面を教えたる」

```
┌─────────────────────────────────────────────────────────────┐
│              try-catch が本当に必要な場面                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ 外部API連携                                             │
│     → 外部サービスは落ちる可能性がある                      │
│     → 失敗しても自分のシステムは動かしたい                  │
│                                                             │
│  2️⃣ 失敗しても処理を続けたい場合                            │
│     → 通知は失敗しても、メイン処理は成功させたい            │
│     → ログは記録すること！                                  │
│                                                             │
│  3️⃣ DBトランザクションでロールバックしたい場合              │
│     → 複数の更新をまとめて取り消す                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🎯 実践例1：外部API連携（Slack通知）

```php
class CompleteTaskUseCase
{
    public function execute(Task $task, User $user): Task
    {
        // 検証
        $this->projectRules->ensureMember($task->project, $user);
        $this->ensureCanComplete($task);

        // タスク完了
        $task->status = 'done';
        $task->save();

        // Slack通知（失敗しても処理は成功させたい）
        try {
            $this->slackNotifier->notify("タスク「{$task->title}」が完了しました");
        } catch (Exception $e) {
            // Slackが落ちてても、タスク完了自体は成功
            Log::warning('Slack通知失敗: ' . $e->getMessage());
            // ここでは throw しない（処理を続ける）
        }

        $task->load('createdBy');
        return $task;
    }
}
```

**👩‍💻ユーザー：** 「Slack が落ちてても、タスク完了は成功するんですね」

**🐘ガネーシャ：** 「せや。これは『意図的に処理を続ける』パターンや。ログは記録しとるから、後から調査もできる」

---

### 🎯 実践例2：DBトランザクション

```php
public function execute(Task $task, User $user): Task
{
    $this->projectRules->ensureMember($task->project, $user);
    $this->ensureCanComplete($task);

    try {
        DB::beginTransaction();

        $task->status = 'done';
        $task->save();

        // 完了履歴を記録
        TaskHistory::create([
            'task_id' => $task->id,
            'action' => 'completed',
            'user_id' => $user->id,
        ]);

        DB::commit();

    } catch (Exception $e) {
        DB::rollBack();
        Log::error('タスク完了処理でエラー: ' . $e->getMessage());
        throw $e;  // ← 上に投げ直す！握りつぶさない！
    }

    $task->load('createdBy');
    return $task;
}
```

**👩‍💻ユーザー：** 「catch の中で `throw $e` してますね」

**🐘ガネーシャ：** 「せや。ロールバックした後、例外を上に投げ直しとる。握りつぶしてへんのがポイントや」

---

### 📊 try-catch 判断フローチャート

```
                    エラーが起きた時...
                           │
                           ▼
              ┌─────────────────────────┐
              │ 処理を続けたい？         │
              └───────────┬─────────────┘
                    │           │
                   Yes          No
                    │           │
                    ▼           ▼
         ┌──────────────┐  ┌──────────────┐
         │ try-catch    │  │ throw だけ   │
         │ で catch     │  │ Laravelに    │
         │ ログ記録！   │  │ 任せる       │
         └──────────────┘  └──────────────┘
```

---

## 📝 第8章：各層の責務を整理する

**🐘ガネーシャ：** 「最後に、各層が何をすべきか整理しとこか」

```
┌─────────────────────────────────────────────────────────────┐
│                     各層の責務                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Controller】                                              │
│  ├── HTTP リクエストを受け取る                              │
│  ├── UseCase を呼び出す                                     │
│  ├── HTTP レスポンスを返す                                  │
│  └── ※ ビジネスロジックは書かない                          │
│                                                             │
│  【UseCase】                                                 │
│  ├── ビジネスロジックの「流れ」を組み立てる                 │
│  ├── エラー時は throw する                                  │
│  ├── try-catch は基本書かない                               │
│  └── ※ HTTP のことは知らない                               │
│                                                             │
│  【Service / Rules】                                         │
│  ├── 複数の UseCase で共通して使うルール                    │
│  ├── エラー時は throw する                                  │
│  └── ※ HTTP のことは知らない                               │
│                                                             │
│  【Laravel Handler】                                         │
│  ├── throw された例外を自動で catch                         │
│  ├── 適切な HTTP レスポンスに変換                           │
│  └── ログも自動で記録                                       │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 第9章：まとめ

**🐘ガネーシャ：** 「今日学んだことをまとめるで」

```
┌─────────────────────────────────────────────────────────────┐
│                      今日の学び                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ 「とりあえず try-catch」は意味がない                    │
│     → Laravel のデフォルト処理と変わらない                  │
│     → むしろ正しいステータスコードが返らなくなる            │
│                                                             │
│  2️⃣ 握りつぶしは絶対ダメ                                    │
│     → catch するなら最低限ログを記録する                    │
│     → または throw $e で投げ直す                            │
│                                                             │
│  3️⃣ return vs throw                                         │
│     → return は無視できる（チェック忘れるとバグ）           │
│     → throw は無視できない（確実にエラーを伝える）          │
│                                                             │
│  4️⃣ UseCase / Service では throw だけ                       │
│     → try-catch は書かない                                  │
│     → response() も返さない                                 │
│     → Laravel が自動で処理してくれる                        │
│                                                             │
│  5️⃣ try-catch が必要な場面は限られる                        │
│     → 外部API連携（失敗しても続けたい）                     │
│     → DBトランザクション（ロールバック）                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### ❌ → ✅ 変換早見表

| Before（ダメ） | After（正しい） |
|----------------|-----------------|
| 「とりあえず」try-catch | 本当に必要か考える |
| UseCase で `response()->json()` | `throw new Exception()` |
| catch 内でログなし | 最低限 `Log::error()` |
| catch 内で握りつぶし | `throw $e` で投げ直す |
| 全部を大きな try で囲む | 必要な部分だけ try で囲む |

---

## 📝 演習課題

### Q1：以下のコードの問題点を指摘してください

```php
public function store(Request $request, Project $project)
{
    try {
        $task = Task::create([
            'project_id' => $project->id,
            'title' => $request->title,
            'status' => 'todo',
            'created_by' => $request->user()->id,
        ]);

        return new TaskResource($task);

    } catch (Exception $e) {
        return response()->json(['message' => 'エラー'], 500);
    }
}
```

<details>
<summary>📖 答えを見る</summary>

**問題点：**

1. **try-catch が不要** → Laravel のデフォルトと変わらない
2. **ログを記録していない** → 何が起きたか分からない
3. **全部 500 になる** → QueryException なども 500 になってしまう

**修正版（try-catch を削除）：**

```php
public function store(Request $request, Project $project)
{
    $task = Task::create([
        'project_id' => $project->id,
        'title' => $request->title,
        'status' => 'todo',
        'created_by' => $request->user()->id,
    ]);

    return new TaskResource($task);
}
```

</details>

---

### Q2：以下の場面で try-catch は必要ですか？

```
1. UseCase でタスクのステータスをチェックする
2. 外部の決済APIにリクエストを送る
3. findOrFail() でタスクを取得する
4. Slack に通知を送る（失敗しても処理は続けたい）
5. 複数テーブルを更新する（失敗したらロールバック）
```

<details>
<summary>📖 答えを見る</summary>

| 場面 | try-catch | 理由 |
|------|-----------|------|
| 1. ステータスチェック | **不要** | throw するだけ、Laravel に任せる |
| 2. 決済API | **必要** | 失敗時の対応が必要 |
| 3. findOrFail() | **不要** | Laravel が自動で 404 を返す |
| 4. Slack通知 | **必要** | 失敗しても処理を続けたい |
| 5. 複数テーブル更新 | **必要** | ロールバックが必要 |

</details>

---

### Q3：以下のコードを正しく修正してください

```php
class UpdateTaskUseCase
{
    public function execute(Task $task, array $data): Task
    {
        try {
            if ($task->status === 'done') {
                return response()->json(['message' => '編集不可'], 409);
            }

            $task->update($data);
            return $task;

        } catch (Exception $e) {
            // あとで対応
        }
    }
}
```

<details>
<summary>📖 答えを見る</summary>

```php
class UpdateTaskUseCase
{
    public function execute(Task $task, array $data): Task
    {
        if ($task->isDone()) {
            throw new Exception('完了済みのタスクは編集できません');
        }

        $task->update($data);
        return $task;
    }
}
```

**修正ポイント：**
- try-catch を削除
- `response()->json()` を `throw` に変更
- 握りつぶしを削除

※ 403 や 409 を返したい場合は「カスタム例外」を使うが、それは次回のレッスンで学ぶ

</details>

---

## 🐘 ガネーシャの最後のメッセージ

**🐘ガネーシャ：** 「今日のポイントはこれや」

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│     「とりあえず try-catch」は意味がない！                  │
│                                                             │
│      UseCase / Service では throw だけ！                    │
│                                                             │
│      本当に必要な時だけ try-catch を書く！                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘ガネーシャ：** 「ワシの教え子のダ・ヴィンチくんも言うとったわ。『シンプルさは究極の洗練である』って」

**👩‍💻ユーザー：** 「それは本当に言ってそうですね！」

**🐘ガネーシャ：** 「せやろ？『とりあえず try-catch』で囲むのは洗練とは逆や。シンプルに throw だけ書いて、Laravel に任せる。これがプロの書き方やで」

**👩‍💻ユーザー：** 「はい！try-catch は本当に必要な時だけ使います！」

**🐘ガネーシャ：** 「よっしゃ！さすガネーシャや！🐘✨」

---

## 📚 次回予告

**🐘ガネーシャ：** 「今日は throw を学んだな。次は『トランザクション』を教えたるわ」

**👩‍💻ユーザー：** 「トランザクション...？」

**🐘ガネーシャ：** 「複数のDB操作を『全部成功するか、全部取り消すか』にする仕組みや。プロジェクト作成でオーナーも一緒に登録するやろ？あれが途中で失敗したらどうなると思う？」

**👩‍💻ユーザー：** 「オーナーのいないプロジェクトができちゃう...？」

**🐘ガネーシャ：** 「せや！それを防ぐのがトランザクションや。次回しっかり教えたるで！」

### 振り返り

| Lesson | タイトル | 学んだこと |
|--------|----------|------------|
| 6-1 | ステータスコードとは | 200, 400, 404, 500 などの意味 |
| 6-2 | Laravel が自動でやること | Route Model Binding, FormRequest, APP_DEBUG |
| 6-3 | 自分で書くエラーハンドリング | 403, 409 の実装 |
| 7 | try-catch の基本 | try-catch の概念、Laravel の自動処理 |
| **8** | **UseCase での throw** | **return vs throw、UseCase では throw だけ** |
| 9（次回） | トランザクション | 複数テーブル更新を安全に |

**🐘ガネーシャ：** 「ほな、次回もよろしくな！さすガネーシャや！🐘✨」
