# Lesson6-2: Laravel が自動でやってくれるエラーハンドリング 🐘

## 〜書かなくていいコードを知ろう〜

---

## 🌿 ブランチ切り替えと準備

課題に取り組む前に、リモートの全てのブランチを取得してから、Lesson 用のブランチに切り替えてください：

```bash
# リモートの全てのブランチ情報を取得
git fetch origin

# Lesson用のブランチに切り替え
git checkout lesson6-2

# リモートの最新状態に更新
git pull origin lesson6-2
```

**推奨：** 各 Lesson ごとに専用のブランチで作業することで、作業を整理しやすくなります。

### データベースの準備

データベースを初期状態に戻してください：

```bash
sail artisan migrate:refresh --seed
```

### フロントエンドの再起動

Vite の開発サーバーを再起動してください：

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

## 🎭 プロローグ：衝撃の事実

**👩‍💻 ユーザー：** 「ガネーシャさん、前回ステータスコードを教えてもらったので、早速全部自分で書いてみました！」

**🐘 ガネーシャ：** 「おお、やる気あるやん。見せてみ」

```php
// 👩‍💻ユーザーが書いたコード
public function show($id)
{
    $task = Task::find($id);

    if (!$task) {
        return response()->json([
            'message' => 'タスクが見つかりません'
        ], 404);
    }

    return response()->json($task);
}
```

**🐘 ガネーシャ：** 「...お前、これ全部のメソッドに書いとるんか？」

**👩‍💻 ユーザー：** 「はい！show、update、destroy、全部に書きました！」

**🐘 ガネーシャ：** 「ご苦労さん...でもな、実はこれ、書かんでええねん」

**👩‍💻 ユーザー：** 「えっ！？」

**🐘 ガネーシャ：** 「Laravel は賢いからな、基本的なエラーハンドリングは**自動で**やってくれるんや」

**👩‍💻 ユーザー：** 「じゃあ私の努力は...」

**🐘 ガネーシャ：** 「無駄やったな！ガハハ！」

**👩‍💻 ユーザー：** 「ひどい！！」

---

## 📖 第 1 章：Laravel が自動でやってくれること一覧

**🐘 ガネーシャ：** 「まずは全体像を見せたるわ」

```
┌─────────────────────────────────────────────────────────────┐
│       Laravelが自動でやってくれるエラーハンドリング         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ✅ 404 Not Found                                           │
│     → Route Model Binding / findOrFail()                    │
│                                                             │
│  ✅ 422 Unprocessable Entity                                │
│     → FormRequest のバリデーション                          │
│                                                             │
│  ✅ 401 Unauthorized                                        │
│     → auth ミドルウェア / Sanctum                           │
│                                                             │
│  ✅ 500 Internal Server Error                               │
│     → 予期せぬエラーの自動キャッチ                          │
│                                                             │
│  ✅ 405 Method Not Allowed                                  │
│     → 存在しないHTTPメソッド                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「こんなにあるんですか！？」

**🐘 ガネーシャ：** 「せや。つまりお前が書いた 404 のコード、実は Laravel に任せられたんや」

**👩‍💻 ユーザー：** 「...」

**🐘 ガネーシャ：** 「まぁ落ち込むな。知らんかっただけや。これから一個ずつ見ていくで！」

---

## 🔍 第 2 章：404 Not Found を自動で返す方法

### 🎭 方法 1：Route Model Binding（超おすすめ）

**🐘 ガネーシャ：** 「まずはこれや。一番ラクな方法」

#### ❌ 自分で書く場合（面倒）

```php
// routes/api.php
Route::get('/tasks/{id}', [TaskController::class, 'show']);

// TaskController.php
public function show($id)  // ← ただの数字を受け取る
{
    $task = Task::find($id);

    if (!$task) {  // ← 自分でチェック
        return response()->json([
            'message' => 'タスクが見つかりません'
        ], 404);
    }

    return response()->json($task);
}
```

#### ✅ Route Model Binding を使う場合（簡単）

```php
// routes/api.php
Route::get('/tasks/{task}', [TaskController::class, 'show']);
//                 ↑ モデル名と同じにする（小文字）

// TaskController.php
public function show(Task $task)  // ← Taskモデルを直接受け取る！
{
    // 存在しないIDの場合、ここに来る前に自動で404が返る！
    return response()->json($task);
}
```

**👩‍💻 ユーザー：** 「え、これだけ？if も要らないんですか？」

**🐘 ガネーシャ：** 「要らん！Laravel が勝手に 404 返してくれる」

### 🔬 実際に試してみよう

```bash
# 存在するタスク
GET /api/tasks/1

→ 200 OK
{
  "id": 1,
  "title": "買い物に行く"
}

# 存在しないタスク
GET /api/tasks/99999

→ 404 Not Found  ← 自動で返る！
{
  "message": "No query results for model [App\\Models\\Task] 99999."
}
```

**👩‍💻 ユーザー：** 「本当だ！何も書いてないのに 404 が返ってる！」

**🐘 ガネーシャ：** 「せやろ？これが Route Model Binding の魔法や」

---

### 🎭 方法 2：findOrFail() を使う

**🐘 ガネーシャ：** 「Route Model Binding が使えない場合は、findOrFail()を使うんや」

#### ❌ find() の場合

```php
$task = Task::find($id);
// 見つからない場合 → null が返る
// → 自分で if (!$task) のチェックが必要
```

#### ✅ findOrFail() の場合

```php
$task = Task::findOrFail($id);
// 見つからない場合 → 自動で404が返る！
// → ifチェック不要！
```

**👩‍💻 ユーザー：** 「find()を findOrFail()に変えるだけ？」

**🐘 ガネーシャ：** 「そう！たった 6 文字追加するだけで、if 文が消える」

### 📝 使い分け

| メソッド        | 見つからない時 | 使う場面              |
| --------------- | -------------- | --------------------- |
| `find()`        | `null` を返す  | 「無くても OK」な処理 |
| `findOrFail()`  | 自動で 404     | 「無いとダメ」な処理  |
| `firstOrFail()` | 自動で 404     | 条件検索で 1 件取得   |

### 🎯 実践例

```php
// ❌ 古い書き方
public function update(Request $request, $id)
{
    $task = Task::find($id);
    if (!$task) {
        return response()->json(['message' => 'Not found'], 404);
    }
    $task->update($request->all());
    return response()->json($task);
}

// ✅ 新しい書き方（Route Model Binding）
public function update(Request $request, Task $task)
{
    $task->update($request->all());
    return response()->json($task);
}

// ✅ findOrFail を使う場合
public function update(Request $request, $id)
{
    $task = Task::findOrFail($id);  // これだけでOK！
    $task->update($request->all());
    return response()->json($task);
}
```

---

## 📝 第 3 章：422 Unprocessable Entity を自動で返す方法

**🐘 ガネーシャ：** 「次はバリデーションエラーや。これも Laravel に任せられる」

### 🎭 FormRequest を使う

#### ❌ コントローラーに直接書く場合（ダサい）

```php
public function store(Request $request)
{
    // バリデーションを手動で実行
    $validator = Validator::make($request->all(), [
        'title' => 'required|max:255',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'バリデーションエラー',
            'errors' => $validator->errors()
        ], 422);
    }

    $task = Task::create($request->all());
    return response()->json($task, 201);
}
```

**👩‍💻 ユーザー：** 「私これ書いてました...」

**🐘 ガネーシャ：** 「毎回これ書くの面倒やろ？FormRequest を使えば一発や」

#### ✅ FormRequest を使う場合（スマート）

```bash
# ① まずFormRequestを作成
php artisan make:request StoreTaskRequest
```

```php
// ② app/Http/Requests/StoreTaskRequest.php
class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;  // 認可チェック（今は常にtrue）
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です',
            'title.max' => 'タイトルは255文字以内で入力してください',
        ];
    }
}
```

```php
// ③ コントローラーで使う
public function store(StoreTaskRequest $request)  // ← FormRequestを注入！
{
    // ここに来た時点でバリデーションは通過済み！
    // 失敗してたら自動で422が返る
    $task = Task::create($request->validated());
    return response()->json($task, 201);
}
```

**👩‍💻 ユーザー：** 「if 文が消えた！」

**🐘 ガネーシャ：** 「せや。バリデーションに失敗したら、コントローラーの中に入る前に自動で 422 が返るんや」

### 🔬 自動で返るレスポンス

```bash
# タイトルが空の場合
POST /api/tasks
{
  "title": "",
  "description": "テスト"
}

→ 422 Unprocessable Entity  ← 自動で返る！
{
  "message": "タイトルは必須です",
  "errors": {
    "title": ["タイトルは必須です"]
  }
}
```

**👩‍💻 ユーザー：** 「エラーメッセージも自動で入るんですね！」

**🐘 ガネーシャ：** 「messages()メソッドで日本語にカスタマイズもできるで」

### 📊 FormRequest のメリット

```
┌─────────────────────────────────────────────────────────────┐
│              FormRequest を使うメリット                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ コントローラーがスッキリする                            │
│     → バリデーションロジックが分離される                    │
│                                                             │
│  2️⃣ 再利用できる                                            │
│     → 同じバリデーションを複数のメソッドで使える            │
│                                                             │
│  3️⃣ 自動で422が返る                                         │
│     → if文を書かなくていい                                  │
│                                                             │
│  4️⃣ テストしやすい                                          │
│     → バリデーションだけ単体テストできる                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔐 第 4 章：401 Unauthorized を自動で返す方法

**🐘 ガネーシャ：** 「認証エラーも Laravel に任せられる。これはもう設定済みかもしれんな」

### 🎭 auth ミドルウェア

```php
// routes/api.php

// ❌ 認証チェックなし（誰でもアクセス可能）
Route::get('/tasks', [TaskController::class, 'index']);

// ✅ 認証チェックあり（ログイン必須）
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});
```

**👩‍💻 ユーザー：** 「middleware('auth:sanctum')を付けるだけですか？」

**🐘 ガネーシャ：** 「せや。これでログインしてない人がアクセスしたら、自動で 401 が返る」

### 🔬 自動で返るレスポンス

```bash
# 認証トークンなしでアクセス
GET /api/projects
(Authorization ヘッダーなし)

→ 401 Unauthorized  ← 自動で返る！
{
  "message": "Unauthenticated."
}
```

**🐘 ガネーシャ：** 「コントローラーの中で『ログインしてるかチェック』とか書かんでええんやで」

**👩‍💻 ユーザー：** 「ルーティングの設定だけで済むんですね！」

### 💡 ポイント：認証が必要なルートをグループ化

```php
// routes/api.php

// 認証不要
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 認証必須（この中は全部ログイン必要）
Route::middleware('auth:sanctum')->group(function () {
    // ここに書いたルートは全部401チェックが自動で入る
    Route::get('/user', [UserController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // タスク関連
    Route::apiResource('tasks', TaskController::class);

    // プロジェクト関連
    Route::apiResource('projects', ProjectController::class);
});
```

**🐘 ガネーシャ：** 「こうやってグループ化しとくと、認証必要なルートが一目で分かるし、書き漏れも防げるで」

---

## 💥 第 5 章：500 Internal Server Error の自動処理

**🐘 ガネーシャ：** 「500 エラーは『出したらアカン』やつやけど、万が一出た時も Laravel が処理してくれる」

**👩‍💻 ユーザー：** 「前回、500 は開発者側のミスって習いました」

**🐘 ガネーシャ：** 「せや、よう覚えとるな。タイポとか文法エラーとか、ワシらのコードミスで起きるやつや。でも、万が一そういうバグが混入しても、Laravel が適切に処理してくれるんやで」

### 🎭 予期せぬエラーの自動キャッチ

```php
// 例えば、こんなバグがあったとする
public function show(Task $task)
{
    // うっかりnullのプロパティにアクセス
    $length = $task->nonexistent->value;  // 💥 エラー！

    return response()->json($task);
}
```

**👩‍💻 ユーザー：** 「これ、エラーになりますよね？」

**🐘 ガネーシャ：** 「せや。でも Laravel が勝手にキャッチして 500 を返してくれる」

### 🔬 自動で返るレスポンス

```bash
# 本番環境（APP_DEBUG=false）
→ 500 Internal Server Error
{
  "message": "Server Error"
}

# 開発環境（APP_DEBUG=true）
→ 500 Internal Server Error
{
  "message": "Attempt to read property ...",
  "exception": "ErrorException",
  "file": "/app/Http/Controllers/TaskController.php",
  "line": 25,
  "trace": [...]
}
```

**🐘 ガネーシャ：** 「本番では詳細を隠して、開発中は詳細を見せる。これも自動でやってくれるんや」

**👩‍💻 ユーザー：** 「`APP_DEBUG` って何ですか？」

**🐘 ガネーシャ：** 「ええ質問や！これは `.env` ファイルにある設定で、めっちゃ大事やから覚えとき」

---

### 🔑 APP_DEBUG とは？

```
┌─────────────────────────────────────────────────────────────┐
│                 APP_DEBUG の設定                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📁 .env ファイル                                           │
│  ─────────────                                              │
│  APP_DEBUG=true   ← 開発環境                                │
│  APP_DEBUG=false  ← 本番環境                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📊 APP_DEBUG による違い

```
┌─────────────────────────────────────────────────────────────┐
│                 APP_DEBUG の違い                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【APP_DEBUG=true（開発環境）】                              │
│  ├── エラーメッセージの詳細が表示される                    │
│  ├── スタックトレース（どこでエラーが起きたか）が見れる    │
│  ├── ファイル名、行番号が表示される                        │
│  └── デバッグに便利！🔧                                    │
│                                                             │
│  【APP_DEBUG=false（本番環境）】                             │
│  ├── 「Server Error」としか表示されない                    │
│  ├── 詳細情報は隠される                                    │
│  └── セキュリティのため（攻撃者にヒントを与えない）🔒      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「なんで本番では詳細を隠すんですか？」

**🐘 ガネーシャ：** 「ファイルパスやデータベースの情報が漏れたら、攻撃者にヒントを与えてしまうからや。セキュリティの基本やで」

**👩‍💻 ユーザー：** 「じゃあ本番で `APP_DEBUG=true` にしたら危険なんですね」

**🐘 ガネーシャ：** 「**絶対アカン！** 本番では必ず `APP_DEBUG=false` にするんやで。これ、ワシの教え子のザッカーバーグくんも最初やらかしてたわ」

**👩‍💻 ユーザー：** 「えっ、あのザッカーバーグが？」

**🐘 ガネーシャ：** 「...まぁ、細かいことはええやんけ。とにかく覚えとき！」

---

### ⚠️ 重要：500 が出たらバグ！

```
┌─────────────────────────────────────────────────────────────┐
│                    500エラーの心得                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  500が出たら → それはバグや！直さなアカン！                 │
│                                                             │
│  ・「500が自動で返るから大丈夫」ではない                    │
│  ・500はユーザーにとって最悪の体験                          │
│  ・原因を調べて修正する必要がある                           │
│                                                             │
│  確認方法：                                                 │
│  ・storage/logs/laravel.log を見る                          │
│  ・開発環境で APP_DEBUG=true にする                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 第 6 章：まとめ - 何を書いて、何を書かないか

**🐘 ガネーシャ：** 「ここまでの内容をまとめるで！」

### ✅ Laravel に任せるもの（書かなくていい）

| コード  | 状況                 | 任せ方                             |
| ------- | -------------------- | ---------------------------------- |
| **404** | データが存在しない   | Route Model Binding / findOrFail() |
| **422** | バリデーションエラー | FormRequest                        |
| **401** | 未認証               | auth:sanctum ミドルウェア          |
| **500** | 予期せぬエラー       | 自動キャッチ（何もしなくて OK）    |

### ❌ 自分で書く必要があるもの（次回詳しく）

| コード  | 状況               | 例                                 |
| ------- | ------------------ | ---------------------------------- |
| **409** | ビジネスルール違反 | 完了済みタスクは削除できない       |
| **403** | 権限チェック       | プロジェクトメンバーのみアクセス可 |

**👩‍💻 ユーザー：** 「409 は自動化できないんですね」

**🐘 ガネーシャ：** 「せや。ビジネスルールはシステムによって違うからな。これは次回教えるわ」

---

## 🎯 第 7 章：Before / After で見る改善

**🐘 ガネーシャ：** 「最後に、ビフォーアフターを見せたるわ」

### 😱 Before（冗長なコード）

```php
class TaskController extends Controller
{
    public function show($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($task);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::create($request->all());
        return response()->json($task, 201);
    }

    public function update(Request $request, $id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $task->update($request->all());
        return response()->json($task);
    }

    public function destroy($id)
    {
        $task = Task::find($id);
        if (!$task) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $task->delete();
        return response()->json(['message' => '削除しました']);
    }
}
```

### ✨ After（スッキリしたコード）

```php
class TaskController extends Controller
{
    public function show(Task $task)
    {
        return response()->json($task);
    }

    public function store(StoreTaskRequest $request)
    {
        $task = Task::create($request->validated());
        return response()->json($task, 201);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $task->update($request->validated());
        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['message' => '削除しました']);
    }
}
```

**👩‍💻 ユーザー：** 「コード量が半分以下になってる！」

**🐘 ガネーシャ：** 「せやろ？これが Laravel の力や。『書かないコードが一番バグらない』って、ワシの教え子のアインシュタインくんも言うてたわ」

**👩‍💻 ユーザー：** 「アインシュタインはそんなこと言ってないと思います...」

**🐘 ガネーシャ：** 「まぁええやんけ！」

---

## 📝 演習課題

**🐘 ガネーシャ：** 「最後に演習や！」

### Q1：以下のコードをリファクタリングしてください

```php
public function destroy($id)
{
    $task = Task::find($id);

    if (!$task) {
        return response()->json([
            'message' => 'タスクが見つかりません'
        ], 404);
    }

    $task->delete();
    return response()->json(['message' => '削除しました']);
}
```

<details>
<summary>📖 模範解答を見る</summary>

```php
public function destroy(Task $task)  // Route Model Binding
{
    $task->delete();
    return response()->json(['message' => '削除しました']);
}
```

</details>

---

### Q2：どのエラーコードが自動で返るか答えてください

```
1. Route::get('/tasks/{task}', ...) で存在しないIDにアクセス
2. FormRequestのバリデーションに失敗
3. auth:sanctum ミドルウェアでトークンなしでアクセス
4. コード内で予期せぬエラーが発生した時
```

<details>
<summary>📖 答えを見る</summary>

```
1. 404 Not Found
2. 422 Unprocessable Entity
3. 401 Unauthorized
4. 500 Internal Server Error
```

</details>

---

### Q3：以下のコードを FormRequest を使ってリファクタリングしてください

```php
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|max:255',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'バリデーションエラー',
            'errors' => $validator->errors()
        ], 422);
    }

    $task = Task::create($request->all());
    return response()->json($task, 201);
}
```

<details>
<summary>📖 模範解答を見る</summary>

```php
// app/Http/Requests/StoreTaskRequest.php
class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'description' => 'nullable|string',
        ];
    }
}

// TaskController.php
public function store(StoreTaskRequest $request)
{
    $task = Task::create($request->validated());
    return response()->json($task, 201);
}
```

</details>

---

## 🐘 ガネーシャの最後のメッセージ

**🐘 ガネーシャ：** 「今日学んだことをまとめるで」

```
┌─────────────────────────────────────────────────────────────┐
│                      今日の学び                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ Laravelは賢い                                           │
│     → 基本的なエラーは自動で処理してくれる                  │
│                                                             │
│  2️⃣ 「書かないコード」を知ることが大事                      │
│     → 無駄なコードはバグの温床                              │
│                                                             │
│  3️⃣ Laravelの機能を使いこなす                               │
│     → Route Model Binding（404を自動で）                    │
│     → FormRequest（422を自動で）                            │
│     → Middleware（401を自動で）                             │
│                                                             │
│  4️⃣ 自分で書くべきものは次回                                │
│     → ビジネスルール違反（409）                             │
│     → 権限チェック（403）                                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘 ガネーシャ：** 「お前な、プログラミングで大事なのは『いかに書かないか』や」

**👩‍💻 ユーザー：** 「書かないことが大事...」

**🐘 ガネーシャ：** 「せや。フレームワークが用意してくれてる機能を使わんと、同じことを何度も書くことになる。それはバグを生む原因や」

**👩‍💻 ユーザー：** 「確かに、さっきの Before のコード、同じような if 文が何回も出てきてましたね」

**🐘 ガネーシャ：** 「そういうことや。次回は『自分で書かなアカンもの』を教えるで。409 とか、権限チェックとかな」

**👩‍💻 ユーザー：** 「はい！楽しみにしてます！」

**🐘 ガネーシャ：** 「よっしゃ！さすガネーシャや！🐘✨」

---

## 📚 次回予告：Lesson6-3

**「自分で書く必要があるエラーハンドリング」**

-   409 Conflict の実装方法
-   403 Forbidden の実装方法
-   実際のコードで練習

**🐘 ガネーシャ：** 「次は実践編や。覚悟しとけよ〜」
