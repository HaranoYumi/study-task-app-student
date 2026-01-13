# Lesson6-2: Laravelのデフォルトエラーハンドリング体験 🐘

## 📚 このブランチについて

このブランチ(`lesson6-2`)は、`docs/error_handling/lesson6_2_laravel_auto.md`で説明されている「Laravelが自動でやってくれるエラーハンドリング」を実際にPostmanで体験するための教材環境です。

### 🎯 学習目的

- **冗長なコード（Before版）** を実装して、手動でエラーハンドリングする方法を確認
- Laravelのデフォルトエラーハンドリングの挙動を理解
- 次のステップで「適切なコード（After版）」にリファクタリングする準備

---

## ⚙️ 現在の設定

### 1️⃣ バックエンド：デフォルトのエラーハンドリング

`bootstrap/app.php`のカスタム例外ハンドラーをコメントアウトしています。

```php
// カスタムAPIハンドラーは無効化
// $apiHandler = new ApiExceptionHandler();
```

### 2️⃣ コントローラー：敢えて冗長なコード

- **TaskController**: Route Model Bindingを使わず、`find()`と手動`if`チェック
- **ProjectController**: FormRequestを使わず、`Validator`で手動バリデーション

### 3️⃣ ルーティング：`{id}`形式

```php
// ❌ 冗長な方法
Route::get('/tasks/{id}', [TaskController::class, 'show']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);
```

---

## 🧪 Postmanでテストする

### 事前準備

1. **サーバー起動**
```bash
php artisan serve
```

2. **認証トークンを取得**（必要に応じて）
```bash
POST http://localhost:8000/api/login
{
  "email": "user@example.com",
  "password": "password"
}
```

返ってきた`token`をPostmanの`Authorization`タブで`Bearer Token`として設定してください。

---

## 📋 テストケース一覧

### 🔴 Test 1: 404 Not Found（手動チェック版）

**目的**: 存在しないデータにアクセスした時の404エラーを確認

#### リクエスト
```
GET http://localhost:8000/api/tasks/99999
Authorization: Bearer {your_token}
```

#### 期待される結果
```json
{
  "message": "タスクが見つかりません"
}
```
**ステータスコード**: `404 Not Found`

#### コード解説
```php
// TaskController.php（現在のコード）
public function show($id): JsonResponse
{
    $task = Task::find($id);  // ← nullが返る
    
    if (!$task) {  // ← 手動でチェック
        return response()->json([
            'message' => 'タスクが見つかりません'
        ], 404);
    }
    
    return response()->json($task);
}
```

**🐘 ガネーシャのコメント**:
> 「お前、毎回このif文書くん？面倒やろ？次のレッスンで楽な方法教えたるわ！」

---

### 🔴 Test 2: 404 Not Found（プロジェクト）

#### リクエスト
```
GET http://localhost:8000/api/projects/99999
Authorization: Bearer {your_token}
```

#### 期待される結果
```json
{
  "message": "プロジェクトが見つかりません"
}
```
**ステータスコード**: `404 Not Found`

---

### 🟡 Test 3: 422 Unprocessable Entity（バリデーションエラー）

**目的**: 不正なデータを送信した時のバリデーションエラーを確認

#### リクエスト
```
POST http://localhost:8000/api/projects
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "name": "",
  "description": "説明"
}
```

#### 期待される結果
```json
{
  "message": "バリデーションエラー",
  "errors": {
    "name": [
      "The name field is required."
    ]
  }
}
```
**ステータスコード**: `422 Unprocessable Entity`

#### コード解説
```php
// ProjectController.php（現在のコード）
public function store(Request $request): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|max:255',
        'description' => 'nullable|string',
    ]);

    if ($validator->fails()) {  // ← 手動でチェック
        return response()->json([
            'message' => 'バリデーションエラー',
            'errors' => $validator->errors()
        ], 422);
    }

    $project = Project::create($request->all());
    return response()->json($project, 201);
}
```

**🐘 ガネーシャのコメント**:
> 「このif文も毎回書くんか？FormRequest使えば一発やで！」

---

### 🟡 Test 4: 422（タスク作成時のバリデーションエラー）

#### リクエスト
```
POST http://localhost:8000/api/projects/1/tasks
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "title": "",
  "description": "説明"
}
```

#### 期待される結果
```json
{
  "message": "バリデーションエラー",
  "errors": {
    "title": [
      "The title field is required."
    ]
  }
}
```
**ステータスコード**: `422 Unprocessable Entity`

---

### 🔵 Test 5: 401 Unauthorized（認証エラー）

**目的**: トークンなしでアクセスした時の認証エラーを確認

#### リクエスト
```
GET http://localhost:8000/api/projects
（Authorization ヘッダーなし）
```

#### 期待される結果
```json
{
  "message": "Unauthenticated."
}
```
**ステータスコード**: `401 Unauthorized`

**🐘 ガネーシャのコメント**:
> 「これはミドルウェアが自動でやってくれとる！コントローラーで書く必要なし！」

---

### 🟢 Test 6: 201 Created（正常系：プロジェクト作成）

**目的**: 正常にデータが作成されることを確認

#### リクエスト
```
POST http://localhost:8000/api/projects
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "name": "新しいプロジェクト",
  "description": "テストプロジェクトです"
}
```

#### 期待される結果
```json
{
  "id": 1,
  "name": "新しいプロジェクト",
  "description": "テストプロジェクトです",
  "created_at": "2026-01-14T...",
  "updated_at": "2026-01-14T..."
}
```
**ステータスコード**: `201 Created`

---

### 🟢 Test 7: 200 OK（正常系：タスク詳細取得）

#### リクエスト
```
GET http://localhost:8000/api/tasks/1
Authorization: Bearer {your_token}
```

#### 期待される結果
```json
{
  "id": 1,
  "project_id": 1,
  "title": "タスクのタイトル",
  "description": "タスクの説明",
  "status": "todo",
  "due_date": null,
  "created_at": "2026-01-14T...",
  "updated_at": "2026-01-14T..."
}
```
**ステータスコード**: `200 OK`

---

## 🔍 コードの問題点まとめ

### ❌ 現在のコード（Before版）の問題

| 問題                       | 影響                            |
| -------------------------- | ------------------------------- |
| 同じif文が何度も出現       | コードの重複、保守性が低い      |
| 手動バリデーション         | 毎回Validatorを書く必要がある   |
| Route Model Bindingを不使用| `find()`と`if`を毎回書く        |
| コントローラーが肥大化     | 責務が明確でない                |

### ✅ 次のステップ（After版）で改善すること

| 改善策                  | メリット                        |
| ----------------------- | ------------------------------- |
| Route Model Binding使用 | 404チェックが自動化             |
| FormRequest使用         | バリデーションが分離される      |
| findOrFail()使用        | if文が不要になる                |
| UseCase層の導入         | ビジネスロジックが分離される    |

---

## 📖 関連ドキュメント

- `docs/error_handling/lesson6_2_laravel_auto.md` - 理論編
- `docs/error_handling/lesson6_3_custom_exceptions.md` - カスタム例外編（次回）

---

## 🎯 次のステップ

このブランチでPostmanテストを完了したら：

1. **元のブランチに戻る**
   ```bash
   git checkout main
   ```

2. **改善されたコードを確認**
   - `TaskController.php`の`After`版を確認
   - Route Model Bindingの実装を確認
   - FormRequestの実装を確認

3. **差分を比較**
   ```bash
   git diff main lesson6-2 app/Http/Controllers/Api/TaskController.php
   ```

---

## 🐘 ガネーシャからのメッセージ

**🐘 ガネーシャ：**
> 「お前、このコード見てどう思った？」

**👩‍💻 ユーザー：**
> 「同じようなif文が何度も出てきて、面倒くさそうです...」

**🐘 ガネーシャ：**
> 「せやろ？これが『書かなくていいコード』や。Laravel使うなら、フレームワークに任せられることは任せるんや」

**🐘 ガネーシャ：**
> 「次のレッスンで、この冗長なコードをどうスッキリさせるか教えたるわ！楽しみにしとけよ！」

**🐘 ガネーシャ：**
> 「さすガネーシャや！🐘✨」

---

## 📝 まとめ

✅ Laravelはデフォルトで以下のエラーハンドリングをサポート
- **404 Not Found**: Route Model Binding / findOrFail()で自動化可能
- **422 Unprocessable Entity**: FormRequestで自動化可能
- **401 Unauthorized**: Middlewareで自動化済み

❌ 現在のコードは「わざと冗長」に書いてある
- 学習のため、手動でエラーチェックを実装
- 次のレッスンでリファクタリングして改善予定

🎯 **学習ポイント**
> 「書かなくていいコードを知ることが、良いコードを書く第一歩」

---

Happy Learning! 🚀
