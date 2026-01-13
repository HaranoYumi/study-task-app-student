# Lesson6-5: try-catch 乱用地獄 🐘

## 〜「とりあえず try-catch」の落とし穴〜

---

## 🎭 プロローグ：安心感を求めた結果

**👩‍💻 ユーザー：** 「ガネーシャさん！前回教えてもらった try-catch を使ってみました！」

**🐘 ガネーシャ：** 「おお、どんな感じや？」

**👩‍💻 ユーザー：** 「エラーが起きても大丈夫なように、メソッド全体を try-catch で囲みました！」

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

**👩‍💻 ユーザー：** 「これで予期しないエラーが起きても安心ですよね！」

**🐘 ガネーシャ：** 「...お前、この try-catch、意味ないで」

**👩‍💻 ユーザー：** 「えっ！？」

**🐘 ガネーシャ：** 「まあ、これはまだマシや。ちょっと待て...お前、他のところにも try-catch 書いとるやろ。全部見せてみ」

**👩‍💻 ユーザー：** 「えっ、はい...タスク作成のところにも書きました」

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
        // TODO: あとで書く
    }
}
```

**🐘 ガネーシャ：** 「...おい。catch の中、空っぽやんけ」

**👩‍💻 ユーザー：** 「あ、それは後で書こうと思って忘れてました...でも大丈夫ですよね？Laravel がデフォルトでキャッチしてくれるんでしたよね？」

**🐘 ガネーシャ：** 「アカンアカン！**自分で catch を書いたら、Laravel のデフォルト処理は動かへん**んやで！」

**👩‍💻 ユーザー：** 「え！？」

**🐘 ガネーシャ：** 「これ、めっちゃ危険や。何が起きてるか説明したる」

```
┌─────────────────────────────────────────────────────────────┐
│              catch 内が空っぽの場合                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  例外が発生！                                                │
│       │                                                     │
│       ▼                                                     │
│  自分の catch (Exception $e) で捕まる                       │
│       │                                                     │
│       ▼                                                     │
│  catch 内が空っぽ...                                        │
│       │                                                     │
│       ▼                                                     │
│  何も起きない！                                              │
│  ├── レスポンスが返らない（画面が真っ白）                  │
│  ├── ログも残らない（原因不明）                            │
│  └── Laravel のデフォルト処理も動かない！                  │
│                                                             │
│  ⚠️ 「後で書く」つもりで忘れると最悪の結果に...            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「えっ、Laravel がデフォルトで処理してくれると思ってました...」

**🐘 ガネーシャ：** 「**自分で catch を書いた時点で、Laravel の出番はなくなる**んや。catch は Laravel のデフォルトより『優先』されるからな」

**👩‍💻 ユーザー：** 「じゃあ、空っぽの catch を書いたら、エラーが完全に消えちゃう...？」

**🐘 ガネーシャ：** 「せや！これを『握りつぶし』って言うんや。エラーを catch して、何もしないでなかったことにしてしまう。最悪のパターンやで」

---

## 📖 第 1 章：デフォルトと変わらない try-catch

### 🎭 何が問題なのか

**🐘 ガネーシャ：** 「さっきの空っぽ catch は論外として、最初に見せてくれたコードも問題があるで。try-catch を外したらどうなると思う？」

**👩‍💻 ユーザー：** 「えっと...エラーが起きたらプログラムが止まる...？」

**🐘 ガネーシャ：** 「違うで。Lesson6-4 で教えたやろ。**Laravel が自動で catch してくれる**んや」

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

**👩‍💻 ユーザー：** 「あ...確かに、どっちも 500 エラーを返すだけ...」

**🐘 ガネーシャ：** 「せや。しかも、お前のコードの方が**悪い**まであるで」

---

### 🔍 むしろ悪くなるパターン

**🐘 ガネーシャ：** 「実は、try-catch を書いたせいで問題が起きることがあるんや」

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

**🐘 ガネーシャ：** 「Lesson6-4 で教えた『Exception の階層構造』を覚えとるか？」

**👩‍💻 ユーザー：** 「はい！`Exception` は『すべての例外の親』で、`catch (Exception $e)` って書くと全部の例外が捕まるんですよね」

**🐘 ガネーシャ：** 「せや！だから `ModelNotFoundException` も `QueryException` も、全部この catch で捕まってしまうんや」

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
│  ModelNotFoundException → catch (Exception $e) で捕まる     │
│                        → 500 を返す ❌                      │
│                                                             │
│  Lesson6-4 で学んだ通り、Exception は「すべての例外の親」   │
│  → 本来 404 で返すべきものが 500 になってしまう！           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「えっ、try-catch を書いたせいで、正しいステータスコードが返らなくなる...？」

**🐘 ガネーシャ：** 「せや。Laravel のデフォルト処理の方が賢いんや」

---

### ⚠️ catch は Laravel のデフォルトより「優先」される

**🐘 ガネーシャ：** 「ここ大事やで。**try-catch を書くと、Laravel のデフォルト処理より先に catch が実行される**んや」

```
┌─────────────────────────────────────────────────────────────┐
│              例外処理の優先順位                               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  例外が発生！                                                │
│       │                                                     │
│       ▼                                                     │
│  ┌─────────────────────────────────────┐                   │
│  │ 自分で書いた catch があるか？       │                   │
│  └─────────────┬───────────────────────┘                   │
│           │           │                                     │
│          Yes          No                                    │
│           │           │                                     │
│           ▼           ▼                                     │
│   【自分の catch】  【Laravel のデフォルト】                │
│   ・自分の処理が    ・適切なステータスコード                │
│     実行される      ・自動でログ記録                        │
│   ・Laravelの処理   ・スタックトレース保存                  │
│     は実行されない                                          │
│                                                             │
│  ⚠️ 自分で catch すると、Laravel の便利な処理が             │
│     全部スキップされてしまう！                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「つまり、自分で catch を書くと、Laravel の自動処理が動かなくなる...？」

**🐘 ガネーシャ：** 「その通りや！だから『意味のある catch』を書かんと、Laravel に任せた方がええんや」

---

### 📊 Laravel のデフォルト vs 雑な try-catch

| 例外                      | Laravel デフォルト | 雑な try-catch    |
| ------------------------- | ------------------ | ----------------- |
| `ModelNotFoundException`  | **404**            | 500 ❌            |
| `ValidationException`     | **422**            | 500 ❌            |
| `AuthenticationException` | **401**            | 500 ❌            |
| `QueryException`          | **500** + 詳細ログ | 500（詳細なし）❌ |
| その他の例外              | **500** + 詳細ログ | 500（詳細なし）❌ |

**🐘 ガネーシャ：** 「Laravel のデフォルトは、例外の種類に応じて適切なステータスコードを返してくれる。でも雑な `catch (Exception $e)` で全部捕まえると、全部 500 になってしまうんや」

**👩‍💻 ユーザー：** 「しかも開発環境なら `APP_DEBUG=true` で詳細も見れるんですよね」

**🐘 ガネーシャ：** 「せや！Laravel に任せた方が、ステータスコードもログもスタックトレースも全部ちゃんと出してくれる。自分で雑に書くより賢いんや」

---

## 💀 第 2 章：エラーの「握りつぶし」

### 🎭 もう一つの問題

**🐘 ガネーシャ：** 「さっきのコード、もう一つ問題があるで。ログを見てみ」

**👩‍💻 ユーザー：** 「ログ...？」

```php
catch (Exception $e) {
    return response()->json([
        'message' => 'エラーが発生しました',
    ], 500);
    // ← ログを記録していない！
}
```

**🐘 ガネーシャ：** 「エラーが起きても、**何が起きたか分からない**んや」

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
│  例外発生 → 自分の catch で捕まえる                         │
│         → Laravel のログ処理はスキップされる                │
│         → ログに何も残らない！                              │
│         → 何が起きたか分からない！                          │
│                                                             │
│  ⚠️ catch が優先されるので、Laravel の自動ログ記録も        │
│     実行されなくなってしまう！                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「あ...本番で問題が起きても、原因が分からない...」

**🐘 ガネーシャ：** 「これを『握りつぶし』って言うんや。エラーを catch して、何もしないでなかったことにしてしまう」

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

**🐘 ガネーシャ：** 「全部アウトや。catch したなら、最低限ログは記録せなアカン。じゃないと Laravel が自動で記録してくれるはずのログも残らんくなるで」

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

**👩‍💻 ユーザー：** 「ログを記録すれば、後から原因を調べられるんですね」

**🐘 ガネーシャ：** 「せや。でもな...」

---

## 🤔 第 3 章：そもそも try-catch を書く必要があるのか？

### 🎭 立ち止まって考える

**🐘 ガネーシャ：** 「ここで一回立ち止まって考えてみ。そもそも、その try-catch は必要か？」

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

**👩‍💻 ユーザー：** 「あれ...Laravel のデフォルトで十分...？」

**🐘 ガネーシャ：** 「せや！わざわざ自分で書く必要ないんや」

**👩‍💻 ユーザー：** 「いや、でも待ってください！デフォルトだと『Server Error』とか『500 Internal Server Error』ってそっけないメッセージになっちゃいますよね？それをカスタマイズしたくて、あえて catch に書いてるんですよ！」

**🐘 ガネーシャ：** 「ほう、ええとこ突いてきたな」

**👩‍💻 ユーザー：** 「ユーザーに分かりやすいエラーメッセージを返したいから、catch で `return response()->json(['message' => '...'` って書いてるんです」

**🐘 ガネーシャ：** 「気持ちは分かる。でもな、**全メソッドに try-catch 書いてエラーメッセージをカスタマイズしとったら、とんでもないことになる**で？」

**👩‍💻 ユーザー：** 「...確かに、メソッドが 100 個あったら 100 箇所に書くことになる...」

**🐘 ガネーシャ：** 「せやろ？しかも全部同じような処理になるやん。それ、**一括で設定できる方法がある**んや」

**👩‍💻 ユーザー：** 「一括で！？」

**🐘 ガネーシャ：** 「Laravel の **Handler（例外ハンドラー）** っていう仕組みがあってな、大元のところでエラーメッセージを一括でカスタマイズできるんや。それはまた別のレッスンで教えたる」

**👩‍💻 ユーザー：** 「なるほど...エラーメッセージのカスタマイズは、try-catch じゃなくて Handler でやるんですね」

**🐘 ガネーシャ：** 「せや。だから **try-catch は『あえて書く』もん**なんや。特定のエラーだけ特別な処理をしたい時にな」

---

### 🔑 try-catch を書く意味

**🐘 ガネーシャ：** 「整理するで。try-catch は『デフォルトとは違う処理をしたい時』にあえて書くもんや」

```
┌─────────────────────────────────────────────────────────────┐
│              try-catch を書く意味                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ❌ こういう理由で try-catch を書くのは間違い               │
│  ├── 「とりあえず安心のため」                              │
│  └── 「エラーメッセージをカスタマイズしたいから」          │
│      → Handler で一括設定できる（別レッスンで学ぶ）        │
│                                                             │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  ✅ try-catch を「あえて書く」場面                          │
│  ├── 失敗しても処理を続けたい（外部API連携など）           │
│  ├── 特定のエラー時だけ通知を飛ばしたい                    │
│  ├── リトライしたい                                         │
│  └── ロールバック処理を入れたい                             │
│                                                             │
│  → つまり「特定のエラーを指定して catch する」ことが多い！ │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「『とりあえず安心のため』じゃなくて、『明確な目的があるから書く』んですね」

**🐘 ガネーシャ：** 「その通りや！しかも、catch する時は Lesson 6-4 で学んだように**特定の例外クラスを指定する**ことが多いで。`catch (Exception $e)` で全部キャッチするんやなくてな」

**👩‍💻 ユーザー：** 「`catch (QueryException $e)` みたいに、DB エラーだけキャッチするとか！」

**🐘 ガネーシャ：** 「せや！それが『あえて書く』ってことや。例えば、DB エラーの時だけ通知を飛ばすならこんな感じやな」

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

    } catch (QueryException $e) {
        // DB エラー時だけ管理者に通知
        $this->notifyAdmin('DB エラー発生', $e);
        throw $e;  // 上に投げ直す（握りつぶさない）
    }
}
```

**👩‍💻 ユーザー：** 「特定の例外だけ catch して、その後 throw で投げ直してるんですね！」

**🐘 ガネーシャ：** 「せや！これなら通知を送った後、Laravel のデフォルト処理にも任せられる。握りつぶしてないのがポイントやで」

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
│  Q3: 特定のエラー時だけ通知を飛ばしたい？                    │
│      → Yes: try-catch を書く（Lesson6-4で学んだ通り）       │
│      → No: 書かなくてOK                                     │
│                                                             │
│  Q4: 外部APIなど、特別な失敗処理が必要？                     │
│      → Yes: try-catch を書く                                │
│      → No: 書かなくてOK                                     │
│                                                             │
│  全部 No なら → try-catch は不要！                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🏗️ 第 4 章：UseCase に切り分けた場合

### 🎭 ガネーシャ、UseCase を発見する

**🐘 ガネーシャ：** 「ちょっと待て...お前のファイル、もっと見せてみ」

**👩‍💻 ユーザー：** 「え、まだ何かありますか...？」

**🐘 ガネーシャ：** 「おっ！お前、UseCase に切り分けとるファイルもあるやんけ！」

**👩‍💻 ユーザー：** 「あ、それは...ちょうど UseCase への切り分けを教わって、今リファクタリング中なんです。まだ途中なんですけど...」

**🐘 ガネーシャ：** 「ほう、見せてみ」

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
                return $result;  // response を返す
            }

            // 状態チェック（privateメソッド）
            $result = $this->ensureCanComplete($task);
            if ($result !== true) {
                return $result;  // response を返す
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
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません',
            ], 403);
        }
        return true;
    }
}
```

**👩‍💻 ユーザー：** 「ちゃんと Service に切り分けて、private メソッドも作りました！」

**🐘 ガネーシャ：** 「切り分けはできとる。でもな、これ**問題だらけ**やで」

**👩‍💻 ユーザー：** 「えっ、そんなに...？」

**🐘 ガネーシャ：** 「まず、**さっきも指摘したように `catch (Exception $e)` で全部捕まえてしまっとる**やろ。第 3 章で言うたやん」

**👩‍💻 ユーザー：** 「あ...特定の例外クラスを指定しないとダメなんでしたね」

**🐘 ガネーシャ：** 「せや。でもな、それ以前にもっと根本的な問題があるんや」

---

### 🔍 問題 1：return response() は catch されない！

**🐘 ガネーシャ：** 「お前、この UseCase を Controller から呼んどるやろ？」

**👩‍💻 ユーザー：** 「はい、こんな感じで...」

```php
// TaskController.php
public function complete(Request $request, Task $task)
{
    try {
        return $this->completeTaskUseCase->execute($task, $request->user());
    } catch (Exception $e) {
        return response()->json(['message' => 'エラー'], 500);
    }
}
```

**🐘 ガネーシャ：** 「この Controller の catch、**UseCase 内の `return response()` は catch できへん**で」

**👩‍💻 ユーザー：** 「え！？どういうことですか？」

**🐘 ガネーシャ：** 「**return と catch は全く別もん**なんや」

```
┌─────────────────────────────────────────────────────────────┐
│          return response() は catch されない！               │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【return response()】                                       │
│  ├── 「値を返す」だけ                                       │
│  ├── たまたまその値がエラーメッセージのJSON                 │
│  ├── でも、ただの「戻り値」として扱われる                   │
│  └── catch には引っかからない！                             │
│                                                             │
│  【throw】                                                   │
│  ├── 「エラーという事象」を発生させる                       │
│  ├── catch はこの「事象」をキャッチする                     │
│  └── return response() は「事象」じゃない                   │
│                                                             │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  つまり...                                                   │
│  UseCase 内の return response() は                          │
│  Controller の catch を素通りして、そのままフロントに返る！ │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「えっ、じゃあ Controller の try-catch は意味ないってことですか？」

**🐘 ガネーシャ：** 「UseCase が `return response()` してる場合はな。catch は『エラーという事象』をキャッチするんや。`return response()` は『値を返してる』だけやから、catch には引っかからへん」

---

### 🎭 Controller で書くならまだいい

**🐘 ガネーシャ：** 「まあ、Controller で `return response()` を書くならまだいいんや」

```php
// Controller ならまだOK
public function complete(Request $request, Task $task)
{
    if ($task->status !== 'doing') {
        return response()->json(['message' => '完了できません'], 409);
    }
    // ...
}
```

**🐘 ガネーシャ：** 「Controller は HTTP を知っとるから、response を返すのは責務の範囲内や。でも UseCase や Service で response を返すのは**責務の混在**になるんや」

---

### 🔍 問題 2：Controller で if 判定すると冗長

**👩‍💻 ユーザー：** 「じゃあ、UseCase は値を返すだけにして、Controller で if 判定すればいいですか？」

```php
// UseCase（文字列を返すパターン）
class CompleteTaskUseCase
{
    public function execute(Task $task, User $user)
    {
        // 権限チェック
        $isMember = $task->project->users()
            ->where('users.id', $user->id)
            ->exists();
        
        if (!$isMember) {
            return 'not_member';  // 文字列を返す
        }

        // 状態チェック
        if (!$task->isDoing()) {
            return 'not_doing';  // 文字列を返す
        }

        $task->update(['status' => 'done']);
        $task->load('createdBy');
        return $task;  // 成功時は Task を返す
    }
}

// Controller で if 判定するパターン
public function complete(Request $request, Task $task)
{
    $result = $this->completeTaskUseCase->execute($task, $request->user());

    if ($result === 'not_member') {
        return response()->json(['message' => '権限がありません'], 403);
    }
    if ($result === 'not_doing') {
        return response()->json(['message' => '完了できません'], 409);
    }

    return new TaskResource($result);
}
```

**🐘 ガネーシャ：** 「これも問題あるで」

```
┌─────────────────────────────────────────────────────────────┐
│          Controller で if 判定する問題                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ 毎回 if を書かないといけない                            │
│     UseCase を呼ぶたびに同じような if 文を書く羽目に        │
│                                                             │
│  2️⃣ 記載漏れのリスク                                        │
│     新しいエラーケースを追加した時、if を書き忘れる         │
│     → バグになる                                           │
│                                                             │
│  3️⃣ UseCase と Controller の知識が重複                      │
│     エラーの種類を両方が知っている必要がある                │
│     → 変更時に両方を修正しないといけない                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「確かに...UseCase でエラーを追加したら、Controller の if も追加しないと...」

**🐘 ガネーシャ：** 「しかも忘れたらバグや。**人間は絶対忘れる**んやで」

---

### 🔍 問題 3：UseCase で response を返す本質的な問題

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

---

### 😫 じゃあどうすればいいの？

**👩‍💻 ユーザー：** 「`return response()` しても catch できない、Controller で if 判定しても冗長...じゃあ UseCase でエラーを伝えたい時はどうすればいいんですかぁ〜」

**🐘 ガネーシャ：** 「そこで出てくるのが **`throw`** なんや！！」

**👩‍💻 ユーザー：** 「throw...！」

**🐘 ガネーシャ：** 「`throw` を使えば、UseCase からエラーを『事象』として伝えられる。Controller で if 判定する必要もないし、catch もできるんや」

---

## 📖 第 5 章：throw を理解しよう

### 🎭 return と throw の違い

**🐘 ガネーシャ：** 「まず return と throw の違いをしっかり理解せなアカン」

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

-   呼び出し側が「戻り値をチェックする責任」がある
-   チェックを忘れるとバグになる
-   **catch には引っかからない**

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

-   呼び出し側はチェック不要
-   忘れても例外が勝手に飛ぶ
-   **catch でキャッチできる**

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
│  ├── **catch には引っかからない**                          │
│  └── 「正常系の一部」として扱われる                        │
│                                                             │
│  【throw】                                                   │
│  ├── 例外を投げる                                           │
│  ├── 呼び出し元は強制的に対応させられる                    │
│  ├── 無視できない（catch されるまで飛び続ける）            │
│  ├── **catch でキャッチできる**                            │
│  └── 「異常系」として明確に扱われる                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「return は『無視できる』し『catch されない』けど、throw は『無視できない』し『catch できる』んですね！」

**🐘 ガネーシャ：** 「せや！だからエラーを確実に伝えたい時は throw を使うんや」

---

### 🎯 throw すると何が起きるか

**🐘 ガネーシャ：** 「throw すると、そこから呼び出し元に向かって『巻き戻り』が始まるんや」

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

**👩‍💻 ユーザー：** 「catch がなくても Laravel が受け取ってくれるんですね！」

**🐘 ガネーシャ：** 「せや！だから UseCase で catch を書く必要ないんや」

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

## 🚀 第 6 章：正しい方法 - UseCase では throw だけ！

### 🎭 考え方を変える

**🐘 ガネーシャ：** 「UseCase でのエラー処理、考え方を変えるで」

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

**👩‍💻 ユーザー：** 「切り分けはしたんですけど...」

**🐘 ガネーシャ：** 「private メソッドで response を返しとるやろ。これやと HTTP の知識が UseCase に漏れてしまっとる」

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

**👩‍💻 ユーザー：** 「あれ、でも throw Exception だと全部 500 エラーになりませんか？」

**🐘 ガネーシャ：** 「おおっ！めっちゃええとこに気づいたな！さすがや！」

**👩‍💻 ユーザー：** 「えへへ」

**🐘 ガネーシャ：** 「確かに今のままやと全部 500 になる。Lesson6-4 で教えた通り、Exception は『すべての例外の親』やからな。403 や 409 を返したい場合は『カスタム例外』を作るんやけど、それは次回のレッスンで詳しく教えたるわ」

**👩‍💻 ユーザー：** 「カスタム例外...？」

**🐘 ガネーシャ：** 「参考までにチラ見せしたるわ。こんな感じや」

```php
// 参考：カスタム例外を使うとこうなる（次回のレッスンで詳しく解説）

// ForbiddenException.php（カスタム例外）
class ForbiddenException extends Exception
{
    // 403 を返すための例外
}

// BusinessRuleException.php（カスタム例外）
class BusinessRuleException extends Exception
{
    // 409 を返すための例外
}

// ProjectRules.php
public function ensureMember(Project $project, User $user): void
{
    if (!$isMember) {
        throw new ForbiddenException('このプロジェクトにアクセスする権限がありません');
        // ↑ 403 用のカスタム例外を投げる
    }
}

// ensureCanComplete
private function ensureCanComplete(Task $task): void
{
    if (!$task->isDoing()) {
        throw new BusinessRuleException('作業中のタスクのみ完了できます');
        // ↑ 409 用のカスタム例外を投げる
    }
}
```

**👩‍💻 ユーザー：** 「なるほど！例外の種類を分けることで、ステータスコードも分けられるんですね！」

**🐘 ガネーシャ：** 「せや！今日は『throw を使う』ことを覚えてくれ。カスタム例外の詳細は次回や」

---

### 📊 Before / After 比較

| 項目           | Before                         | After               |
| -------------- | ------------------------------ | ------------------- |
| try-catch      | あり                           | **なし**            |
| response()     | 3 箇所                         | **0 箇所**          |
| 戻り値の型     | `TaskResource \| JsonResponse` | **`Task`**          |
| HTTP の知識    | UseCase が知っている           | **Controller だけ** |
| テストしやすさ | 低い                           | **高い**            |

**👩‍💻 ユーザー：** 「めっちゃスッキリ！try-catch がなくなった！」

**🐘 ガネーシャ：** 「せやろ？throw するだけで、あとは Laravel に任せるんや」

---

## 🎯 第 7 章：try-catch が本当に必要な場面

### 🎭 じゃあ、いつ使うの？

**👩‍💻 ユーザー：** 「try-catch をあまり使わない方がいいのは分かりました。でも、いつ使えばいいんですか？」

**🐘 ガネーシャ：** 「ええ質問や。本当に必要な場面を教えたる」

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
│  3️⃣ 特定のエラー時だけ通知を飛ばしたい場合                  │
│     → Lesson6-4 で学んだ catch (QueryException $e) など     │
│                                                             │
│  4️⃣ DBトランザクションでロールバックしたい場合              │
│     → 複数の更新をまとめて取り消す                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🎯 実践例 1：外部 API 連携（Slack 通知）

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
        } catch (GuzzleHttp\Exception\RequestException $e) {
            // HTTP通信エラーだけを catch
            // Slackが落ちてても、タスク完了自体は成功
            Log::warning('Slack通知失敗: ' . $e->getMessage());
            // ここでは throw しない（処理を続ける）
        }

        $task->load('createdBy');
        return $task;
    }
}
```

**👩‍💻 ユーザー：** 「あっ！`catch (Exception $e)` じゃなくて、`catch (RequestException $e)` って特定のエラーを指定してる！」

**🐘 ガネーシャ：** 「おお、ええとこに気づいたな！さっき学んだことがちゃんと身についとるやん」

**👩‍💻 ユーザー：** 「`RequestException` って何ですか？」

**🐘 ガネーシャ：** 「Guzzle っていう HTTP クライアントライブラリが投げる例外や。外部 API に通信する時によく使うで。これを指定すれば『HTTP 通信エラー』だけを catch できる」

**👩‍💻 ユーザー：** 「特定の型を指定すれば、予期しないエラーは Laravel のデフォルト処理に任せられるんですね！」

**🐘 ガネーシャ：** 「せや！これが握りつぶしを防ぐコツや」

---

### 😵 ダメな例との比較

```php
// ❌ 全部キャッチ（握りつぶしのリスク大）
try {
    $this->slackNotifier->notify($message);
} catch (Exception $e) {
    // 全部の例外を catch してしまう
    // → 予期しないバグも握りつぶしてしまう可能性
    Log::warning('通知失敗: ' . $e->getMessage());
}

// ✅ 特定の型だけキャッチ（想定したエラーだけ）
try {
    $this->slackNotifier->notify($message);
} catch (GuzzleHttp\Exception\RequestException $e) {
    // HTTP通信のエラーだけを catch
    Log::warning('Slack通知失敗: ' . $e->getMessage());
}
// → それ以外の予期しないエラーは Laravel に任せる
```

**🐘 ガネーシャ：** 「まあ、外部 API の場合は『とにかく失敗しても続けたい』から `Exception` で全部キャッチすることもあるけどな。その場合は**必ずログを残す**ことが大事や」

---

### ⚠️ よくある間違い：throw と catch の型が合っていない

**🐘 ガネーシャ：** 「あと、初心者がよくやる間違いを教えたるわ」

**👩‍💻 ユーザー：** 「何ですか？」

**🐘 ガネーシャ：** 「**throw する例外と、catch する例外の型が合ってない**パターンや」

```php
// ❌ よくある間違い：型が合っていない
public function store(Request $request, Project $project)
{
    try {
        // ここで Exception を throw している
        if (!$this->canCreateTask($project)) {
            throw new Exception('タスクを作成できません');
        }

        $task = Task::create([...]);
        return new TaskResource($task);

    } catch (QueryException $e) {
        // QueryException だけを catch している
        // → Exception は catch されない！
        Log::error('DB エラー: ' . $e->getMessage());
        throw $e;
    }
}
```

**👩‍💻 ユーザー：** 「あっ！throw は `Exception` なのに、catch は `QueryException` だから、catch されないんですね！」

**🐘 ガネーシャ：** 「せや！Lesson6-4 で学んだ階層構造を思い出してみ」

```
Exception（親）
  ├── RuntimeException
  │     └── PDOException
  │           └── QueryException ← これだけ catch
  └── LogicException

throw new Exception() ← これは catch されない！
```

**🐘 ガネーシャ：** 「`QueryException` は `Exception` の子孫やから、catch できるのは `QueryException` とその子孫だけや。親の `Exception` は catch できへん」

**👩‍💻 ユーザー：** 「なるほど...じゃあどうすればいいんですか？」

**🐘 ガネーシャ：** 「2 つの方法があるで」

```php
// ✅ 解決策 1：catch を Exception にする（全部 catch する）
try {
    if (!$this->canCreateTask($project)) {
        throw new Exception('タスクを作成できません');
    }
    $task = Task::create([...]);
    return new TaskResource($task);

} catch (Exception $e) {
    // Exception なら全部 catch できる
    Log::error('エラー: ' . $e->getMessage());
    throw $e;
}

// ✅ 解決策 2：複数の catch を書く（型ごとに分ける）
try {
    if (!$this->canCreateTask($project)) {
        throw new Exception('タスクを作成できません');
    }
    $task = Task::create([...]);
    return new TaskResource($task);

} catch (QueryException $e) {
    // DB エラーだけ特別な処理
    Log::error('DB エラー: ' . $e->getMessage());
    throw $e;
} catch (Exception $e) {
    // それ以外の Exception
    Log::error('エラー: ' . $e->getMessage());
    throw $e;
}
```

**👩‍💻 ユーザー：** 「複数の catch を書けば、型ごとに処理を分けられるんですね！」

**🐘 ガネーシャ：** 「せや！Lesson6-4 で学んだやろ？でもな、**特定の型だけ catch したいなら、throw も同じ型にする**のが基本や」

```php
// ✅ 推奨：throw と catch の型を合わせる
try {
    // QueryException を想定しているなら...
    $task = Task::create([...]);  // これが QueryException を投げる
    return new TaskResource($task);

} catch (QueryException $e) {
    // QueryException だけを catch
    Log::error('DB エラー: ' . $e->getMessage());
    throw $e;
}
// → Exception は catch されずに Laravel に任せる
```

**🐘 ガネーシャ：** 「要するに、**throw する型と catch する型が合ってるか、常に意識せなアカン**ってことや」

---

### 🎯 実践例 2：DB トランザクション

**🐘 ガネーシャ：** 「次はトランザクションのパターンや。これは次回のレッスンで詳しく解説するから、今は『こんなパターンもあるんやな』くらいで見ておいてくれ」

**👩‍💻 ユーザー：** 「はい！」

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

**👩‍💻 ユーザー：** 「catch の中で `throw $e` してますね」

**🐘 ガネーシャ：** 「せや。ロールバックした後、例外を上に投げ直しとる。握りつぶしてへんのがポイントや。詳しくは次回のレッスンで教えたるから、今は『try-catch を使う場面もある』ってことだけ覚えといてな」

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

## 📝 第 8 章：各層の責務を整理する

**🐘 ガネーシャ：** 「最後に、各層が何をすべきか整理しとこか」

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

## 📊 第 9 章：まとめ

**🐘 ガネーシャ：** 「今日学んだことをまとめるで」

```
┌─────────────────────────────────────────────────────────────┐
│                      今日の学び                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ 「とりあえず try-catch」は意味がない                    │
│     → Laravel のデフォルト処理と変わらない                  │
│     → むしろ正しいステータスコードが返らなくなる            │
│                                                             │
│  2️⃣ catch は Laravel のデフォルトより「優先」される         │
│     → 自分で catch すると、Laravel の自動処理がスキップ    │
│     → 自動ログ記録もされなくなる！                          │
│                                                             │
│  3️⃣ Exception は「すべての例外の親」（Lesson6-4 の復習）    │
│     → catch (Exception $e) は全部捕まえてしまう            │
│     → 本来 404 のエラーも 500 になってしまう               │
│                                                             │
│  4️⃣ 握りつぶしは絶対ダメ                                    │
│     → catch するなら最低限ログを記録する                    │
│     → または throw $e で投げ直す                            │
│                                                             │
│  5️⃣ return vs throw                                         │
│     → return は無視できる（チェック忘れるとバグ）           │
│     → throw は無視できない（確実にエラーを伝える）          │
│                                                             │
│  6️⃣ UseCase / Service では throw だけ                       │
│     → try-catch は書かない                                  │
│     → response() も返さない                                 │
│     → Laravel が自動で処理してくれる                        │
│                                                             │
│  7️⃣ try-catch が必要な場面は限られる                        │
│     → 外部API連携（失敗しても続けたい）                     │
│     → 特定エラー時の通知                                    │
│     → DBトランザクション（ロールバック）                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### ❌ → ✅ 変換早見表

| Before（ダメ）                  | After（正しい）           |
| ------------------------------- | ------------------------- |
| 「とりあえず」try-catch         | 本当に必要か考える        |
| UseCase で `response()->json()` | `throw new Exception()`   |
| catch 内でログなし              | 最低限 `Log::error()`     |
| catch 内で握りつぶし            | `throw $e` で投げ直す     |
| 全部を大きな try で囲む         | 必要な部分だけ try で囲む |

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
2. **ログを記録していない** → catch が優先されるので、Laravel の自動ログ記録もされない
3. **全部 500 になる** → Exception は「すべての例外の親」なので、QueryException なども全部 500 になってしまう

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

| 場面                  | try-catch | 理由                             |
| --------------------- | --------- | -------------------------------- |
| 1. ステータスチェック | **不要**  | throw するだけ、Laravel に任せる |
| 2. 決済 API           | **必要**  | 失敗時の対応が必要               |
| 3. findOrFail()       | **不要**  | Laravel が自動で 404 を返す      |
| 4. Slack 通知         | **必要**  | 失敗しても処理を続けたい         |
| 5. 複数テーブル更新   | **必要**  | ロールバックが必要               |

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

-   try-catch を削除
-   `response()->json()` を `throw` に変更
-   握りつぶしを削除

※ 403 や 409 を返したい場合は「カスタム例外」を使うが、それは次回のレッスンで学ぶ

</details>

---

## 🐘 ガネーシャの最後のメッセージ

**🐘 ガネーシャ：** 「今日のポイントはこれや」

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

**🐘 ガネーシャ：** 「ワシの教え子のダ・ヴィンチくんも言うとったわ。『シンプルさは究極の洗練である』って」

**👩‍💻 ユーザー：** 「それは本当に言ってそうですね！」

**🐘 ガネーシャ：** 「せやろ？『とりあえず try-catch』で囲むのは洗練とは逆や。シンプルに throw だけ書いて、Laravel に任せる。これがプロの書き方やで」

**👩‍💻 ユーザー：** 「はい！try-catch は本当に必要な時だけ使います！」

**🐘 ガネーシャ：** 「よっしゃ！さすガネーシャや！🐘✨」

---

## 📚 次回予告

**🐘 ガネーシャ：** 「今日は throw を学んだな。次は『トランザクション』を教えたるわ」

**👩‍💻 ユーザー：** 「トランザクション...？」

**🐘 ガネーシャ：** 「複数の DB 操作を『全部成功するか、全部取り消すか』にする仕組みや。プロジェクト作成でオーナーも一緒に登録するやろ？あれが途中で失敗したらどうなると思う？」

**👩‍💻 ユーザー：** 「オーナーのいないプロジェクトができちゃう...？」

**🐘 ガネーシャ：** 「せや！それを防ぐのがトランザクションや。次回しっかり教えたるで！」

### 振り返り

| Lesson      | タイトル                     | 学んだこと                                   |
| ----------- | ---------------------------- | -------------------------------------------- |
| 6-1         | ステータスコードとは         | 200, 400, 404, 500 などの意味                |
| 6-2         | Laravel が自動でやること     | Route Model Binding, FormRequest, APP_DEBUG  |
| 6-3         | 自分で書くエラーハンドリング | 403, 409 の実装                              |
| 6-4         | try-catch の基本             | try-catch の概念、例外の階層構造、複数 catch |
| **6-5**     | **UseCase での throw**       | **return vs throw、UseCase では throw だけ** |
| 6-6（次回） | トランザクション             | 複数テーブル更新を安全に                     |

**🐘 ガネーシャ：** 「ほな、次回もよろしくな！さすガネーシャや！🐘✨」
