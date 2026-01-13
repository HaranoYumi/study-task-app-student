# Lesson6-3: ビジネスルールエラー テストガイド 🧪

## 📚 このガイドについて

`docs/error_handling/lesson6_3_business_rule_errors.md`で学んだビジネスルールエラー（403・409）を実際にPostmanでテストするためのガイドです。

---

## 👥 テストユーザー一覧

| ID | 名前 | メールアドレス | パスワード | 役割 | 説明 |
|----|------|--------------|-----------|------|------|
| 1 | 山田太郎 | `owner@example.com` | `password` | **オーナー** | ECサイトプロジェクトのオーナー。全権限あり |
| 2 | 佐藤花子 | `admin@example.com` | `password` | **管理者** | ECサイトプロジェクトの管理者。メンバー追加可能 |
| 3 | 鈴木一郎 | `member@example.com` | `password` | **一般メンバー** | ECサイトプロジェクトの一般メンバー。メンバー追加不可 |
| 4 | 田中美咲 | `outsider@example.com` | `password` | **非メンバー** | どのプロジェクトにも所属していない |

---

## 🗂️ テストデータ一覧

### プロジェクト

| ID | プロジェクト名 | メンバー |
|----|--------------|---------|
| 1 | ECサイトリニューアルプロジェクト | ユーザー1（オーナー）、ユーザー2（管理者）、ユーザー3（一般メンバー） |
| 2 | 新規モバイルアプリ開発 | ユーザー1（オーナー）、ユーザー3（一般メンバー） |

### タスク（プロジェクト1のタスク）

| ID | タイトル | ステータス | 作成者 | 用途 |
|----|---------|-----------|--------|------|
| 1 | 開発環境のセットアップ | **done** | オーナー | 完了済みタスクの編集を禁止（409） |
| 2 | データベース設計 | **done** | 管理者 | 完了済みタスクを開始しようとする（409） |
| 3 | 認証機能の実装 | **doing** | 管理者 | 作業中タスクを開始しようとする（409） |
| 4 | APIドキュメントの作成 | **todo** | 一般メンバー | 未着手タスクをいきなり完了（409） |
| 5 | UIコンポーネントの開発 | **doing** | 一般メンバー | 正常に完了できる |
| 6 | 商品一覧ページの実装 | **todo** | オーナー | 正常に開始できる |
| 7 | カート機能の実装 | **todo** | 管理者 | 通常編集・削除用 |

---

## 🧪 テストケース一覧

### 📍 事前準備

1. **データベースをリセット**
```bash
php artisan migrate:fresh --seed
```

2. **サーバー起動**
```bash
php artisan serve
```

3. **認証トークンを取得**（各ユーザーでログイン）

---

## 🔴 409 Conflict のテスト

### Test 1: 完了済みタスクは編集できない

**ログインユーザー:** オーナー（`owner@example.com`）

```
PUT http://localhost:8000/api/tasks/1
Authorization: Bearer {owner_token}
Content-Type: application/json

{
  "title": "タイトルを変更したい"
}

→ 期待される結果: 409 Conflict
{
  "message": "完了済みのタスクは編集できません"
}
```

**🎯 ポイント:** 
- タスク1は`status: done`なので編集できない
- オーナーでもダメ（誰がやってもダメ）

---

### Test 2: 完了済みタスクを開始しようとする

**ログインユーザー:** 管理者（`admin@example.com`）

```
POST http://localhost:8000/api/tasks/2/start
Authorization: Bearer {admin_token}

→ 期待される結果: 409 Conflict
{
  "message": "未着手のタスクのみ開始できます"
}
```

**🎯 ポイント:** 
- タスク2は`status: done`なので開始できない
- 状態遷移ルール違反（done → doing は不可）

---

### Test 3: 作業中タスクを開始しようとする

**ログインユーザー:** 管理者（`admin@example.com`）

```
POST http://localhost:8000/api/tasks/3/start
Authorization: Bearer {admin_token}

→ 期待される結果: 409 Conflict
{
  "message": "未着手のタスクのみ開始できます"
}
```

**🎯 ポイント:** 
- タスク3は`status: doing`なので再度開始できない
- すでに開始済み

---

### Test 4: 未着手タスクをいきなり完了しようとする

**ログインユーザー:** 一般メンバー（`member@example.com`）

```
POST http://localhost:8000/api/tasks/4/complete
Authorization: Bearer {member_token}

→ 期待される結果: 409 Conflict
{
  "message": "作業中のタスクのみ完了できます"
}
```

**🎯 ポイント:** 
- タスク4は`status: todo`なのでいきなり完了できない
- 状態遷移ルール違反（todo → done は不可）
- 必ず todo → doing → done の順

---

### Test 5: 正常に完了できる（比較用）

**ログインユーザー:** 一般メンバー（`member@example.com`）

```
POST http://localhost:8000/api/tasks/5/complete
Authorization: Bearer {member_token}

→ 期待される結果: 200 OK
{
  "data": {
    "id": 5,
    "status": "done",
    ...
  }
}
```

**🎯 ポイント:** 
- タスク5は`status: doing`なので正常に完了できる
- 正しい状態遷移（doing → done）

---

### Test 6: 正常に開始できる（比較用）

**ログインユーザー:** オーナー（`owner@example.com`）

```
POST http://localhost:8000/api/tasks/6/start
Authorization: Bearer {owner_token}

→ 期待される結果: 200 OK
{
  "data": {
    "id": 6,
    "status": "doing",
    ...
  }
}
```

**🎯 ポイント:** 
- タスク6は`status: todo`なので正常に開始できる
- 正しい状態遷移（todo → doing）

---

## 🚫 403 Forbidden のテスト

### Test 7: 非メンバーがタスクを見ようとする

**ログインユーザー:** 非メンバー（`outsider@example.com`）

```
GET http://localhost:8000/api/tasks/1
Authorization: Bearer {outsider_token}

→ 期待される結果: 403 Forbidden
{
  "message": "このプロジェクトにアクセスする権限がありません"
}
```

**🎯 ポイント:** 
- 田中美咲はプロジェクト1のメンバーではない
- プロジェクトメンバーのみアクセス可能

---

### Test 8: 非メンバーがタスクを編集しようとする

**ログインユーザー:** 非メンバー（`outsider@example.com`）

```
PUT http://localhost:8000/api/tasks/7
Authorization: Bearer {outsider_token}
Content-Type: application/json

{
  "title": "編集したい"
}

→ 期待される結果: 403 Forbidden
{
  "message": "このプロジェクトにアクセスする権限がありません"
}
```

**🎯 ポイント:** 
- メンバーではないので編集できない

---

### Test 9: 一般メンバーがメンバーを追加しようとする

**ログインユーザー:** 一般メンバー（`member@example.com`）

```
POST http://localhost:8000/api/projects/1/members
Authorization: Bearer {member_token}
Content-Type: application/json

{
  "user_id": 4
}

→ 期待される結果: 403 Forbidden
{
  "message": "メンバーを追加する権限がありません（オーナーまたは管理者のみ）"
}
```

**🎯 ポイント:** 
- 鈴木一郎は一般メンバー（`project_member`）
- メンバー追加は`project_owner`または`project_admin`のみ可能

---

### Test 10: 管理者がメンバーを追加する（正常系）

**ログインユーザー:** 管理者（`admin@example.com`）

```
POST http://localhost:8000/api/projects/1/members
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "user_id": 4
}

→ 期待される結果: 201 Created
{
  "data": {
    "id": 4,
    "name": "田中美咲",
    "email": "outsider@example.com",
    "role": "project_member",
    ...
  }
}
```

**🎯 ポイント:** 
- 佐藤花子は管理者（`project_admin`）なのでメンバー追加可能

---

## 📊 チェックの順序（重要）

Laravelは以下の順序でチェックします：

```
1. 【自動】404 → Route Model Binding（タスクが存在するか）
2. 【自動】422 → FormRequest（入力値が正しいか）
3. 【手動】403 → 権限チェック（この人に権限があるか）
4. 【手動】409 → ビジネスルールチェック（状態的にOKか）
5. 正常処理
```

**例:** タスク1（完了済み）を編集しようとした場合

```
✅ 404チェック: タスク1は存在する → OK
✅ 422チェック: 入力値は正しい → OK
✅ 403チェック: プロジェクトメンバーである → OK
❌ 409チェック: status=doneなので編集不可 → 409エラー
```

---

## 🎯 403 vs 409 の判断基準

| エラー | 判断基準 | 例 |
|--------|---------|-----|
| **403 Forbidden** | 「誰が」やろうとしているかが問題<br>別の人（権限ある人）なら同じ操作ができる | 一般メンバーがメンバー追加<br>→ オーナーなら追加できる |
| **409 Conflict** | 「何を」やろうとしているかが問題<br>誰がやっても同じ結果（ダメ） | 完了済みタスクを編集<br>→ オーナーでもダメ |

---

## 🔄 データをリセットする方法

テスト中にデータを変更した場合、以下のコマンドでリセットできます：

```bash
php artisan migrate:fresh --seed
```

**⚠️ 注意:** 全てのデータが初期化されます。

---

## 🐘 ガネーシャからのアドバイス

**🐘 ガネーシャ：**
> 「お前な、テストする時はな、まず『正常系』を試してから『異常系』を試すんやで」

**手順:**
1. オーナーでログイン → 正常に動作することを確認
2. 一般メンバーでログイン → 403が返ることを確認
3. 非メンバーでログイン → 403が返ることを確認
4. 完了済みタスクを編集 → 409が返ることを確認

**🐘 ガネーシャ：**
> 「順番にやっていけば、どのエラーがどの状況で出るか、よう分かるで！」

**🐘 ガネーシャ：**
> 「さすガネーシャや！🐘✨」

---

Happy Testing! 🚀
