# Lesson6-3: ビジネスルールのエラーハンドリング 🐘

## 〜ビジネスルールはお前が守れ！〜

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
git checkout -b lesson6-3
```

**推奨：** 各 Lesson ごとに専用のブランチを作成することで、作業を整理しやすくなります。

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

## 🎭 プロローグ：間違ったエラーコード

**👩‍💻 ユーザー：** 「ガネーシャさん、前回教えてもらった通りに Route Model Binding と FormRequest を使ったら、コードがめっちゃスッキリしました！」

**🐘 ガネーシャ：** 「おお、ええやん。成長しとるな」

**👩‍💻 ユーザー：** 「もうエラーハンドリングは完璧です！」

**🐘 ガネーシャ：** 「ほう、ほな一回コード見せてみ」

```php
// 👩‍💻 ユーザーが書いたコード
public function destroy(Task $task)
{
    // 完了済みタスクは削除できない
    if ($task->status === 'done') {
        return response()->json([
            'message' => '完了済みのタスクは削除できません'
        ], 401);  // ← これ！
    }

    $task->delete();
    return response()->json(['message' => '削除しました']);
}
```

**🐘 ガネーシャ：** 「...お前、これ何のエラーコードや？」

**👩‍💻 ユーザー：** 「えっと...401 です」

**🐘 ガネーシャ：** 「**なんで 401 やねん！！！**」

**👩‍💻 ユーザー：** 「だ、だって...404 でもないし、422 でもないし...どれに該当するか分からなかったので適当に 401 で...」

**🐘 ガネーシャ：** 「適当！？お前な、401 って何のエラーやった？」

**👩‍💻 ユーザー：** 「えっと...『誰やねん』...あ」

**🐘 ガネーシャ：** 「そう！『ログインしてない』時のエラーや！完了済みタスクを削除しようとするのと、ログインしてないのと、何の関係があるんや！」

**👩‍💻 ユーザー：** 「すみません...」

**🐘 ガネーシャ：** 「はぁ...まぁ、分からんかったのはしゃあない。でもな、**適当にエラーコード選ぶのが一番アカン**んや。フロントエンドの開発者が『なんで 401 が返ってくるん？』って混乱するやろ」

**👩‍💻 ユーザー：** 「確かに...じゃあ、正解は何ですか？」

**🐘 ガネーシャ：** 「ちょい待て。その前に、お前が実装したこのルール、よう見てみ」

**👩‍💻 ユーザー：** 「『完了済みタスクは削除できない』...ですよね」

**🐘 ガネーシャ：** 「せや。これな、**ビジネスルール**って言うんや」

**👩‍💻 ユーザー：** 「ビジネスルール...？」

**🐘 ガネーシャ：** 「そう！『データは存在する、権限もある、入力値も正しい。でもこのシステム独自のルールでダメ』っていう状況や。お前が書いたのはまさにビジネスルールのチェックなんやで」

**👩‍💻 ユーザー：** 「あ、なるほど...！でも、なんでビジネスルールは Laravel が自動でやってくれないんですか？404 とか 422 は自動でしたよね？」

**🐘 ガネーシャ：** 「ええ質問や！それはな、**ビジネスルールはそのシステム独自のものやから**や」

**👩‍💻 ユーザー：** 「独自のもの...？」

**🐘 ガネーシャ：** 「せや。例えば『完了済みタスクは削除できない』っていうルール、これはお前のシステムのルールやろ？別のシステムやったら『完了済みでも削除できる』かもしれん。システムによって違うんや」

**👩‍💻 ユーザー：** 「確かに...」

**🐘 ガネーシャ：** 「Laravel は『データが存在しない（404）』とか『入力値が間違ってる（422）』とか、**どのシステムでも共通のルール**は自動で判定できる。でもな、『完了済みタスクは削除できない』みたいな**お前のシステム独自のルール**は、Laravel は知らんやろ？」

**👩‍💻 ユーザー：** 「あー！Laravel はこのシステムのビジネスルールを知らないから、判定できないんですね！」

**🐘 ガネーシャ：** 「そういうことや！だから、**自分でどの時にエラーを飛ばすか記載して、その時は適切なエラーコードを書く**必要があるんや。これが『自分で書くエラーハンドリング』ってやつや」

**👩‍💻 ユーザー：** 「なるほど！じゃあ、このビジネスルール違反の時は何のエラーコードを使えばいいんですか？」

**🐘 ガネーシャ：** 「それが **409 Conflict** や。今日はこの『ビジネスルール違反』の時に使うエラーコードについて教えたるわ」

---

## 📖 第 1 章：自分で書く必要があるもの

**🐘 ガネーシャ：** 「まず、何を自分で書かなアカンか整理するで」

```
┌─────────────────────────────────────────────────────────────┐
│          自分で書く必要があるエラーハンドリング              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🔴 409 Conflict（ビジネスルール違反）                      │
│     → 完了済みタスクは編集できない                          │
│     → todo → doing → done の順でしか進めない               │
│     → プロジェクトのオーナーは最低1人必要                   │
│                                                             │
│  🔴 403 Forbidden（権限チェック）                           │
│     → プロジェクトメンバーのみアクセス可能                  │
│     → 自分が作成したタスクのみ編集可能                      │
│     → オーナー/管理者のみメンバー追加可能                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「409 と 403 が自分で書く必要があるんですね」

**🐘 ガネーシャ：** 「せや。この 2 つは『システムによって違う』から、Laravel は自動でやってくれへんねん」

---

## 🔍 第 2 章：409 Conflict を実装する

### 🎭 409 って何だっけ？

**🐘 ガネーシャ：** 「まず復習や。409 ってどういう意味やった？」

**👩‍💻 ユーザー：** 「えっと...『ルール的にダメ』でしたっけ？」

**🐘 ガネーシャ：** 「せや！もうちょい正確に言うと...」

```
┌─────────────────────────────────────────────────────────────┐
│                    409 Conflict とは                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「リクエスト自体は正しいけど、                              │
│   ビジネスルール的にその操作はできない」                    │
│                                                             │
│  特徴：                                                     │
│  ・データは存在する（404じゃない）                          │
│  ・権限もある（403じゃない）                                │
│  ・入力値も正しい（422じゃない）                            │
│  ・でも「今の状態」ではダメ！                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「404 でも 403 でも 422 でもない、でもダメな時...」

**🐘 ガネーシャ：** 「そう！具体例で見ていこか」

---

### 🎯 実装例 1：完了済みタスクは編集できない

**🐘 ガネーシャ：** 「まずは一番シンプルな例や」

#### 仕様

```
・タスクのステータスが「done」の場合、タイトルや説明を編集できない
・編集しようとしたら 409 Conflict を返す
```

#### 実装

```php
// TaskController.php

public function update(UpdateTaskRequest $request, Task $task)
{
    // ビジネスルールチェック：完了済みは編集不可
    if ($task->status === 'done') {
        return response()->json([
            'message' => '完了済みのタスクは編集できません'
        ], 409);
    }

    $task->update($request->validated());
    return response()->json($task);
}
```

**📍 Postman で試すエンドポイント**

```http
PUT http://localhost/api/tasks/1
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": "タイトル変更したい"
}
```

**使用するダミーデータ:**

-   **タスク ID: 1** → 「開発環境のセットアップ」（status: done）
-   **ログインユーザー:** owner@example.com

**期待される結果:** 409 Conflict

```json
{
    "message": "完了済みのタスクは編集できません"
}
```

**👩‍💻 ユーザー：** 「あれ、思ったよりシンプルですね」

**🐘 ガネーシャ：** 「せや。if 文で条件チェックして、ダメなら 409 を返す。それだけや」

---

### 🎯 実装例 1-2：完了済みタスクは削除できない

**🐘 ガネーシャ：** 「編集と同じで、完了済みタスクは削除もできへんで」

#### 仕様

```
・タスクのステータスが「done」の場合、削除できない
・削除しようとしたら 409 Conflict を返す
```

#### 実装

```php
// TaskController.php

public function destroy(Task $task)
{
    // ビジネスルールチェック：完了済みタスクは削除できない
    if ($task->status === 'done') {
        return response()->json([
            'message' => '完了済みのタスクは削除できません'
        ], 409);
    }

    $task->delete();
    return response()->json(['message' => 'タスクを削除しました']);
}
```

**📍 Postman で試すエンドポイント**

```http
DELETE http://localhost/api/tasks/1
Authorization: Bearer {your_token}
```

**使用するダミーデータ:**

-   **タスク ID: 1** → 「開発環境のセットアップ」（status: done）
-   **ログインユーザー:** owner@example.com

**期待される結果:** 409 Conflict

```json
{
    "message": "完了済みのタスクは削除できません"
}
```

**👩‍💻 ユーザー：** 「これも同じパターンですね！」

**🐘 ガネーシャ：** 「せや。完了したタスクは履歴として残しとくんや」

---

### 🎯 実装例 2：タスクの状態遷移ルール

**🐘 ガネーシャ：** 「次はもうちょい複雑な例や。タスクのステータス変更にはルールがある」

#### 仕様

```
┌─────────────────────────────────────────────────────────────┐
│                タスクの状態遷移ルール                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│    todo ──→ doing ──→ done                                  │
│     │         │         │                                   │
│     │         │         ✗ 戻れない                          │
│     │         │                                             │
│     │         ✗ todoに戻れない                              │
│     │                                                       │
│     ✗ いきなりdoneにできない                                │
│                                                             │
│  許可される操作：                                           │
│  ・start：todo → doing                                      │
│  ・complete：doing → done                                   │
│                                                             │
│  禁止される操作：                                           │
│  ・todo → done（いきなり完了）                              │
│  ・done → doing（完了から戻す）                             │
│  ・doing → todo（作業中から戻す）                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

#### 実装：タスク開始（todo → doing）

```php
// TaskController.php

/**
 * タスクを開始する（todo → doing）
 * POST /api/tasks/{task}/start
 */
public function start(Task $task)
{
    // ビジネスルールチェック：todoからのみ開始可能
    if ($task->status !== 'todo') {
        return response()->json([
            'message' => '未着手のタスクのみ開始できます'
        ], 409);
    }

    $task->status = 'doing';
    $task->save();

    return response()->json($task);
}
```

**📍 Postman で試すエンドポイント（異常系）**

**ケース 1：作業中のタスクを再開始しようとする**

```http
POST http://localhost/api/tasks/3/start
Authorization: Bearer {your_token}
```

-   **タスク ID: 3** → 「認証機能の実装」（status: doing）
-   **期待結果:** 409 Conflict「未着手のタスクのみ開始できます」

**ケース 2：完了済みタスクを開始しようとする**

```http
POST http://localhost/api/tasks/2/start
Authorization: Bearer {your_token}
```

-   **タスク ID: 2** → 「データベース設計」（status: done）
-   **期待結果:** 409 Conflict「未着手のタスクのみ開始できます」

---

#### 実装：タスク完了（doing → done）

```php
/**
 * タスクを完了する（doing → done）
 * POST /api/tasks/{task}/complete
 */
public function complete(Task $task)
{
    // ビジネスルールチェック：doingからのみ完了可能
    if ($task->status !== 'doing') {
        return response()->json([
            'message' => '作業中のタスクのみ完了できます'
        ], 409);
    }

    $task->status = 'done';
    $task->save();

    return response()->json($task);
}
```

**📍 Postman で試すエンドポイント（異常系）**

**ケース：未着手のタスクをいきなり完了しようとする**

```http
POST http://localhost/api/tasks/4/complete
Authorization: Bearer {your_token}
```

-   **タスク ID: 4** → 「API ドキュメントの作成」（status: todo）
-   **期待結果:** 409 Conflict「作業中のタスクのみ完了できます」

---

**👩‍💻 ユーザー：** 「なるほど！それぞれのメソッドで『今のステータス』をチェックするんですね」

**🐘 ガネーシャ：** 「せや。状態遷移のルールは、こうやって一つずつチェックするんや」

#### ルーティング

```php
// routes/api.php

Route::middleware('auth:sanctum')->group(function () {
    // 通常のCRUD
    Route::apiResource('tasks', TaskController::class);

    // 状態変更用のエンドポイント
    Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
    Route::post('/tasks/{task}/complete', [TaskController::class, 'complete']);
});
```

#### テストしてみよう

```bash
# ケース1：正常に開始できる（todo → doing）
POST /api/tasks/1/start
（タスク1のステータスが「todo」の場合）

→ 200 OK
{
  "id": 1,
  "title": "買い物に行く",
  "status": "doing"  ← 変わった！
}

# ケース2：すでに作業中のタスクを開始しようとする
POST /api/tasks/1/start
（タスク1のステータスが「doing」の場合）

→ 409 Conflict
{
  "message": "未着手のタスクのみ開始できます"
}

# ケース3：未着手のタスクをいきなり完了しようとする
POST /api/tasks/2/complete
（タスク2のステータスが「todo」の場合）

→ 409 Conflict
{
  "message": "作業中のタスクのみ完了できます"
}
```

---

### 🎯 実装例 3：プロジェクトのオーナーは最低 1 人必要 & 未完了タスクがあるメンバーは削除不可

**🐘 ガネーシャ：** 「これはちょっと複雑や。メンバー削除の時に使う」

#### 仕様

```
・プロジェクトには最低1人のオーナー（project_owner）が必要
・最後のオーナーを削除しようとしたら 409 Conflict を返す
・未完了タスク（todo, doing）を持つメンバーは削除できない
・削除しようとしたら 409 Conflict を返す
```

#### 実装

```php
// ProjectMemberController.php

/**
 * プロジェクトからメンバーを削除
 * DELETE /api/projects/{project}/members/{user}
 */
public function destroy(Request $request, Project $project, $userId): JsonResponse
{
    // 削除対象のユーザーを取得
    $targetUser = $project->users()
        ->where('users.id', $userId)
        ->first();

    if (!$targetUser) {
        return response()->json([
            'message' => 'User is not a member of this project.',
        ], 404);
    }

    // ビジネスルールチェック1：最後のオーナーは削除不可
    if ($targetUser->pivot->role === 'project_owner') {
        $ownerCount = $project->users()
            ->wherePivot('role', 'project_owner')
            ->count();

        if ($ownerCount <= 1) {
            return response()->json([
                'message' => 'プロジェクトの最後のオーナーは削除できません'
            ], 409);
        }
    }

    // ビジネスルールチェック2：未完了タスクがあるメンバーは削除不可
    $hasIncompleteTasks = $project->tasks()
        ->where('created_by', $userId)
        ->whereIn('status', ['todo', 'doing'])
        ->exists();

    if ($hasIncompleteTasks) {
        return response()->json([
            'message' => '未完了のタスクがあるメンバーは削除できません'
        ], 409);
    }

    // 削除実行
    $project->users()->detach($userId);

    return response()->json([
        'message' => 'メンバーを削除しました'
    ]);
}
```

**👩‍💻 ユーザー：** 「削除する前に、オーナーが何人いるかと、未完了タスクがあるかをチェックするんですね」

**🐘 ガネーシャ：** 「せや。『削除した後に困ること』を事前にチェックする。これがビジネスルールの実装や」

---

### 📊 409 のパターンまとめ

**🐘 ガネーシャ：** 「ここまでの 409 パターンをまとめるで」

| パターン                 | チェック内容                | メッセージ例                         | Postman エンドポイント             |
| ------------------------ | --------------------------- | ------------------------------------ | ---------------------------------- |
| 状態チェック（編集）     | `$task->status === 'done'`  | 完了済みのタスクは編集できません     | `PUT /api/tasks/1`                 |
| 状態チェック（削除）     | `$task->status === 'done'`  | 完了済みのタスクは削除できません     | `DELETE /api/tasks/1`              |
| 状態遷移（開始）         | `$task->status !== 'todo'`  | 未着手のタスクのみ開始できます       | `POST /api/tasks/3/start`          |
| 状態遷移（完了）         | `$task->status !== 'doing'` | 作業中のタスクのみ完了できます       | `POST /api/tasks/4/complete`       |
| 数量チェック（オーナー） | `$ownerCount <= 1`          | 最後のオーナーは削除できません       | `DELETE /api/projects/1/members/1` |
| 関連チェック（タスク）   | `$hasIncompleteTasks`       | 未完了タスクがあるメンバーは削除不可 | `DELETE /api/projects/1/members/2` |
| 重複チェック             | `既にメンバー`              | このユーザーは既にメンバーです       | -                                  |

```
┌─────────────────────────────────────────────────────────────┐
│                  409を使う判断基準                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  以下の全てに当てはまる時 → 409 Conflict                    │
│                                                             │
│  ✅ データは存在する（404じゃない）                         │
│  ✅ 権限はある（403じゃない）                               │
│  ✅ 入力値は正しい（422じゃない）                           │
│  ✅ でも「ビジネスルール」的にダメ                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚫 第 3 章：403 Forbidden を実装する

### 🎭 403 って何だっけ？

**🐘 ガネーシャ：** 「次は 403 や。これも復習しとこか」

```
┌─────────────────────────────────────────────────────────────┐
│                    403 Forbidden とは                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  「ログインはしてるけど、この操作をする権限がない」         │
│                                                             │
│  401との違い：                                              │
│  ・401 = 「誰か分からん」（ログインしてない）               │
│  ・403 = 「誰か分かった上で、ダメ」（権限がない）           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「401 は『ログインしろ』で、403 は『ログインしてるけどダメ』ですね」

**🐘 ガネーシャ：** 「そういうこと！」

---

### 🎯 実装例 1：プロジェクトメンバーのみアクセス可能

**🐘 ガネーシャ：** 「一番よくある 403 のパターンや」

#### 仕様

```
・タスクはプロジェクトに紐づいている
・そのプロジェクトのメンバーのみタスクを閲覧・操作できる
・メンバーじゃない人がアクセスしたら 403 Forbidden
```

#### 実装

```php
// TaskController.php

public function show(Request $request, Task $task)
{
    // 権限チェック：プロジェクトメンバーかどうか
    $project = $task->project;
    $isMember = $project->users()
        ->where('users.id', $request->user()->id)
        ->exists();

    if (!$isMember) {
        return response()->json([
            'message' => 'このプロジェクトにアクセスする権限がありません'
        ], 403);
    }

    return new TaskResource($task);
}
```

**👩‍💻 ユーザー：** 「users()リレーションで、ログインユーザーがプロジェクトメンバーかチェックするんですね」

**🐘 ガネーシャ：** 「せや。同じチェックを show、update、destroy 全部に書く必要があるんや」

---

### 🎯 実装例 2：オーナー/管理者のみメンバー追加可能

**🐘 ガネーシャ：** 「次は『ロール（役割）による権限チェック』や」

#### 仕様

```
・メンバー追加は project_owner または project_admin のみ可能
・project_member（一般メンバー）は追加できない
```

#### 実装

```php
// ProjectMemberController.php

/**
 * プロジェクトにメンバーを追加
 * POST /api/projects/{project}/members
 */
public function store(Request $request, Project $project)
{
    // 権限チェック：オーナーまたは管理者か
    $currentUser = $project->users()
        ->where('users.id', auth()->id())
        ->first();

    // メンバーじゃない
    if (!$currentUser) {
        return response()->json([
            'message' => 'このプロジェクトにアクセスする権限がありません'
        ], 403);
    }

    // メンバーだけど、オーナー/管理者じゃない
    if (!in_array($currentUser->pivot->role, ['project_owner', 'project_admin'])) {
        return response()->json([
            'message' => 'メンバーを追加する権限がありません（オーナーまたは管理者のみ）'
        ], 403);
    }

    // 重複チェック（409）
    $exists = $project->users()
        ->where('users.id', $request->user_id)
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'このユーザーは既にプロジェクトのメンバーです'
        ], 409);
    }

    // メンバー追加
    $project->users()->attach($request->user_id, [
        'role' => $request->role ?? 'project_member'
    ]);

    $newMember = $project->users()
        ->where('users.id', $request->user_id)
        ->first();

    return response()->json($newMember, 201);
}
```

**👩‍💻 ユーザー：** 「403 が 2 種類ありますね。『メンバーじゃない』と『メンバーだけど権限不足』」

**🐘 ガネーシャ：** 「ええとこ気づいたな！同じ 403 でもメッセージを変えることで、ユーザーに『何がダメなのか』を伝えられるんや」

---

### 💡 リファクタリング：権限チェックを共通化

```php
// ProjectMemberController.php（または BaseController.php）

/**
 * プロジェクトへのアクセス権限をチェック
 * @return User|null
 */
private function getCurrentProjectUser(Project $project)
{
    return $project->users()
        ->where('users.id', auth()->id())
        ->first();
}

/**
 * 管理権限（オーナー/管理者）があるかチェック
 */
private function canManageMembers($user): bool
{
    return $user && in_array($user->pivot->role, ['project_owner', 'project_admin']);
}
```

使い方：

```php
public function store(Request $request, Project $project)
{
    $currentUser = $this->getCurrentProjectUser($project);

    if (!$currentUser) {
        return response()->json([
            'message' => 'このプロジェクトにアクセスする権限がありません'
        ], 403);
    }

    if (!$this->canManageMembers($currentUser)) {
        return response()->json([
            'message' => 'メンバーを追加する権限がありません'
        ], 403);
    }

    // 以下、メンバー追加処理...
}
```

---

### 📊 403 のパターンまとめ

| パターン         | チェック内容                 | メッセージ例                         |
| ---------------- | ---------------------------- | ------------------------------------ |
| メンバーチェック | プロジェクトに所属しているか | アクセスする権限がありません         |
| ロールチェック   | owner/admin かどうか         | メンバーを追加する権限がありません   |
| 所有者チェック   | 自分が作成したデータか       | 他のユーザーのタスクは編集できません |

```
┌─────────────────────────────────────────────────────────────┐
│                  403を使う判断基準                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  以下に当てはまる時 → 403 Forbidden                         │
│                                                             │
│  ✅ ログインはしている（401じゃない）                       │
│  ✅ データは存在する（404じゃない）                         │
│  ✅ でも「この操作をする権限」がない                        │
│                                                             │
│  よくあるパターン：                                         │
│  ・所属していないプロジェクトへのアクセス                   │
│  ・一般メンバーが管理者機能を使おうとした                   │
│  ・他人のデータを操作しようとした                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📝 第 4 章：403 と 409 の使い分け

**👩‍💻 ユーザー：** 「403 と 409、どっちを使えばいいか迷うことありそうです...」

**🐘 ガネーシャ：** 「ええ質問や。判断基準を教えたる」

```
┌─────────────────────────────────────────────────────────────┐
│                 403 vs 409 の判断基準                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  🚫 403 Forbidden                                           │
│  ────────────────────                                       │
│  「誰が」やろうとしてるかが問題                             │
│                                                             │
│  ・この人にはそもそも権限がない                             │
│  ・別の人（権限ある人）なら同じ操作ができる                 │
│                                                             │
│  例：一般メンバーがプロジェクトを削除しようとした           │
│      → オーナーなら削除できる                               │
│                                                             │
│  ──────────────────────────────────────────────────────     │
│                                                             │
│  ⚠️ 409 Conflict                                            │
│  ────────────────────                                       │
│  「何を」やろうとしてるかが問題                             │
│                                                             │
│  ・誰がやっても同じ結果（ダメ）                             │
│  ・データの「状態」が問題                                   │
│                                                             │
│  例：完了済みタスクを編集しようとした                       │
│      → オーナーでも管理者でも編集できない                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 具体例で考えてみよう

| 状況                                   | 403 or 409 | 理由                           |
| -------------------------------------- | ---------- | ------------------------------ |
| 一般メンバーがメンバー追加しようとした | **403**    | オーナーなら追加できる         |
| 完了済みタスクを編集しようとした       | **409**    | 誰がやってもダメ               |
| 他人のプロジェクトを見ようとした       | **403**    | メンバーなら見れる             |
| 最後のオーナーを削除しようとした       | **409**    | 誰がやってもダメ（ルール違反） |
| todo 状態のタスクを完了しようとした    | **409**    | 誰がやってもダメ（順序違反）   |

**👩‍💻 ユーザー：** 「『誰がやっても同じ結果』なら 409、『人によって変わる』なら 403 ですね！」

**🐘 ガネーシャ：** 「完璧や！さすガネーシャの教え子！」

---

## 🎯 第 5 章：実践 - タスク管理システムの実装

**🐘 ガネーシャ：** 「ここまでの知識を使って、実際のコントローラーを完成させるで」

### TaskController 完成版

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends ApiController
{

    /**
     * タスク詳細取得
     * GET /api/tasks/{task}
     */
    public function show(Request $request, Task $task): TaskResource|JsonResponse
    {
        // 403チェック：プロジェクトメンバーか
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません'
            ], 403);
        }

        return new TaskResource($task);
    }

    /**
     * タスク更新
     * PUT /api/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Task $task): TaskResource|JsonResponse
    {
        // 403チェック：プロジェクトメンバーか
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません'
            ], 403);
        }

        // 409チェック：完了済みタスクは編集不可
        if ($task->status === 'done') {
            return response()->json([
                'message' => '完了済みのタスクは編集できません'
            ], 409);
        }

        $task->update($request->validated());
        return new TaskResource($task);
    }

    /**
     * タスク削除
     * DELETE /api/tasks/{task}
     */
    public function destroy(Request $request, Task $task): JsonResponse
    {
        // 403チェック：プロジェクトメンバーか
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません'
            ], 403);
        }

        // 409チェック：完了済みタスクは削除不可
        if ($task->status === 'done') {
            return response()->json([
                'message' => '完了済みのタスクは削除できません'
            ], 409);
        }

        $task->delete();
        return response()->json(['message' => 'タスクを削除しました']);
    }

    /**
     * タスク開始
     * POST /api/tasks/{task}/start
     */
    public function start(Request $request, Task $task): TaskResource|JsonResponse
    {
        // 403チェック：プロジェクトメンバーか
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません'
            ], 403);
        }

        // 409チェック：todoからのみ開始可能
        if ($task->status !== 'todo') {
            return response()->json([
                'message' => '未着手のタスクのみ開始できます'
            ], 409);
        }

        $task->status = 'doing';
        $task->save();

        return new TaskResource($task);
    }

    /**
     * タスク完了
     * POST /api/tasks/{task}/complete
     */
    public function complete(Request $request, Task $task): TaskResource|JsonResponse
    {
        // 403チェック：プロジェクトメンバーか
        $project = $task->project;
        $isMember = $project->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember) {
            return response()->json([
                'message' => 'このプロジェクトにアクセスする権限がありません'
            ], 403);
        }

        // 409チェック：doingからのみ完了可能
        if ($task->status !== 'doing') {
            return response()->json([
                'message' => '作業中のタスクのみ完了できます'
            ], 409);
        }

        $task->status = 'done';
        $task->save();

        return new TaskResource($task);
    }
}
```

---

## 📊 第 6 章：エラーハンドリング全体像

**🐘 ガネーシャ：** 「Lesson6 全体をまとめるで！」

```
┌─────────────────────────────────────────────────────────────┐
│              エラーハンドリング 完全マップ                   │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【Laravelに任せる】                                        │
│  ├── 404 → Route Model Binding / findOrFail()              │
│  ├── 422 → FormRequest                                      │
│  ├── 401 → auth:sanctum ミドルウェア                        │
│  └── 500 → 自動キャッチ                                     │
│                                                             │
│  【自分で書く】                                              │
│  ├── 403 → 権限チェック                                     │
│  │         if (!$this->checkProjectMember(...))             │
│  │                                                          │
│  └── 409 → ビジネスルールチェック                           │
│            if ($task->status === 'done')                    │
│            if ($task->status !== 'todo')                    │
│            if ($ownerCount <= 1)                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### チェックの順序

```php
public function update(UpdateTaskRequest $request, Task $task)
{
    // ① 404 → Route Model Binding で自動チェック（この時点で存在確認済み）

    // ② 422 → FormRequest で自動チェック（この時点で入力値確認済み）

    // ③ 403 → 権限チェック（自分で書く）
    $project = $task->project;
    $isMember = $project->users()
        ->where('users.id', $request->user()->id)
        ->exists();

    if (!$isMember) {
        return response()->json(['message' => '権限がありません'], 403);
    }

    // ④ 409 → ビジネスルールチェック（自分で書く）
    if ($task->status === 'done') {
        return response()->json(['message' => '完了済みは編集不可'], 409);
    }

    // ⑤ 正常処理
    $task->update($request->validated());
    return new TaskResource($task);
}
```

**👩‍💻 ユーザー：** 「404 と 422 は先に自動でチェックされて、その後に 403 と 409 を自分でチェックするんですね！」

**🐘 ガネーシャ：** 「その通り！この順序を覚えとくとええで」

---

## 📝 演習課題

### Q1：以下の仕様を実装してください

```
仕様：
・未完了タスク（todo, doing）を持つメンバーは削除できない
・削除しようとしたら 409 Conflict を返す
```

<details>
<summary>📖 模範解答を見る</summary>

```php
// ProjectMemberController.php

public function destroy(Request $request, Project $project, $userId): JsonResponse
{
    // 未完了タスクがあるかチェック
    $hasIncompleteTasks = $project->tasks()
        ->where('created_by', $userId)
        ->whereIn('status', ['todo', 'doing'])
        ->exists();

    if ($hasIncompleteTasks) {
        return response()->json([
            'message' => '未完了のタスクがあるメンバーは削除できません'
        ], 409);
    }

    $project->users()->detach($userId);
    return response()->json(['message' => 'メンバーを削除しました']);
}
```

**💡 ポイント:**

-   `$project->tasks()` でプロジェクトのタスクを取得
-   `where('created_by', $userId)` で削除対象ユーザーが作成したタスクに絞る
-   `whereIn('status', ['todo', 'doing'])` で未完了タスクに絞る
-   未完了タスクがあれば 409 Conflict を返す

</details>

---

### Q2：以下の状況で、どのステータスコードを返すべきですか？

```
1. 一般メンバーがプロジェクトを削除しようとした
2. 完了済みタスクを開始しようとした
3. 既にメンバーのユーザーを再度追加しようとした
4. 他人のプロジェクトのタスクを見ようとした
5. 最後のオーナーのロールを変更しようとした
```

<details>
<summary>📖 答えを見る</summary>

```
1. 403 Forbidden（権限がない → オーナーなら削除できる）
2. 409 Conflict（状態遷移ルール違反 → 誰がやってもダメ）
3. 409 Conflict（重複 → 誰がやってもダメ）
4. 403 Forbidden（権限がない → メンバーなら見れる）
5. 409 Conflict（ビジネスルール違反 → 誰がやってもダメ）
```

</details>

---

### Q3：以下のコードの問題点を指摘してください

```php
public function complete(Task $task)
{
    $task->status = 'done';
    $task->save();
    return response()->json($task);
}
```

<details>
<summary>📖 答えを見る</summary>

**問題点：**

1. **403 チェックがない** → プロジェクトメンバー以外もアクセスできてしまう
2. **409 チェックがない** → todo から直接 done にできてしまう（状態遷移ルール違反）

**修正版：**

```php
public function complete(Request $request, Task $task)
{
    // 403チェック
    $project = $task->project;
    $isMember = $project->users()
        ->where('users.id', $request->user()->id)
        ->exists();

    if (!$isMember) {
        return response()->json([
            'message' => 'このプロジェクトにアクセスする権限がありません'
        ], 403);
    }

    // 409チェック
    if ($task->status !== 'doing') {
        return response()->json([
            'message' => '作業中のタスクのみ完了できます'
        ], 409);
    }

    $task->status = 'done';
    $task->save();
    return new TaskResource($task);
}
```

</details>

---

## 🐘 ガネーシャの最後のメッセージ

**🐘 ガネーシャ：** 「今日はよう頑張ったな！」

```
┌─────────────────────────────────────────────────────────────┐
│                      今日の学び                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ 409 Conflict = ビジネスルール違反                       │
│     → 状態チェック、数量チェック、重複チェック              │
│     → 「誰がやっても同じ結果（ダメ）」                      │
│                                                             │
│  2️⃣ 403 Forbidden = 権限不足                                │
│     → メンバーチェック、ロールチェック                      │
│     → 「人によって結果が変わる」                            │
│                                                             │
│  3️⃣ チェックの順序                                          │
│     → 404（自動）→ 422（自動）→ 403 → 409 → 正常処理       │
│                                                             │
│  4️⃣ users()リレーションとpivotの活用                        │
│     → $project->users()->where('users.id', auth()->id())   │
│     → $user->pivot->role で権限チェック                     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘 ガネーシャ：** 「ワシの教え子のナイチンゲールちゃんも言うとったわ。『エラーハンドリングは患者（ユーザー）への思いやりや』って」

**👩‍💻 ユーザー：** 「ナイチンゲールがプログラミング...？」

**🐘 ガネーシャ：** 「...まぁええやんけ！大事なのは『ユーザーに何がダメだったか伝える』ことや。適切なステータスコードと分かりやすいメッセージ。これがエラーハンドリングの本質やで」

**👩‍💻 ユーザー：** 「はい！ありがとうございました！」

**🐘 ガネーシャ：** 「よっしゃ！さすガネーシャや！🐘✨」

---

## 📚 Lesson6 完結！

**🐘 ガネーシャ：** 「これでエラーハンドリングの基本は完璧や！」

### Lesson6 シリーズまとめ

| Lesson | 内容                                       |
| ------ | ------------------------------------------ |
| 6-1    | ステータスコードとは何か                   |
| 6-2    | Laravel が自動でやってくれること           |
| 6-3    | ビジネスルールのエラーハンドリング（今回） |

### 次に学ぶといいこと

-   **Middleware** を使った権限チェックの共通化
-   **FormRequest の authorize()** を使った権限チェック
-   **カスタム例外クラス** の作り方（発展編）

**🐘 ガネーシャ：** 「ほな、また次の授業で会おな〜！」
