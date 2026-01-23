# Lesson7: 自動テストで品質を守る 〜PHPUnit でテストを書こう〜

## 🎯 この Lesson の目的

この Lesson7 では、**あなた自身で**以下を実装します：

1. **PHPUnit を使った Feature テストの基礎を学ぶ**
2. **API エンドポイントの自動テストを実装する**
3. **テストデータの準備方法を学ぶ（Factory・Seeder）**
4. **テストを通じてバグを早期発見する仕組みを体験する**

---

## 👀 まず最初にやること（重要）

### テストの重要性を理解する

実装を始める前に、以下の参考資料を確認してください：

👉 **テストの重要性を理解する教材（必読）**  
この教材を読んで、なぜテストが必要なのかを理解してください。

---

## 🐘 ガネーシャ先生の教え：テストの重要性を学ぶ

### 🎙️ ガネーシャ🐘とユーザー👩‍💻の会話

---

**👩‍💻ユーザー：** ガネーシャさん、今までコントローラーとかサービスとか作ってきましたけど、毎回動くか不安で…。

**🐘ガネーシャ：** せや！まさにそれや！ワシの教え子のエジソンくんもな、電球を発明する時に「これで光るやろか？」って毎回手で試しとったんや。でもな、10,000回も試すのに毎回手でやるとか、さすがのエジソンくんも疲れてもうてな…😅

**👩‍💻ユーザー：** それで自動で確認する仕組みを作ったんですか？

**🐘ガネーシャ：** さすガネーシャや！✨ ワシがエジソンくんに教えたんは「**一度テストを書いておけば、何度でも自動で確認できる**」ってことや。これが**自動テスト**ってやつやな。

---

### 📚 自動テストとは何か？

自動テストとは、**コードが正しく動作するかを自動的に確認する仕組み**です。

**🎯 自動テストのメリット**

```
┌───────────────────────────────────────────────┐
│ 🔍 自動テストの3大メリット                      │
├───────────────────────────────────────────────┤
│                                               │
│ 1️⃣ バグを早期発見できる                        │
│   → コードを変更した時、すぐに問題に気づける    │
│                                               │
│ 2️⃣ 安心してリファクタリングできる               │
│   → 動作が保証されているので、改善しやすい      │
│                                               │
│ 3️⃣ ドキュメントとしても機能する                 │
│   → テストコードが「仕様」を示してくれる        │
│                                               │
└───────────────────────────────────────────────┘
```

---

**👩‍💻ユーザー：** なるほど…でも、テストって書くの面倒じゃないですか？

**🐘ガネーシャ：** ふふふ、ええ質問やな！ワシの教え子のナポレオンちゃんもな、最初は「テストなんて書く時間あったら戦場に行きたい！」って言うとったんや。でもな、ある日、大事な作戦を実行したら、装備が全然準備できてなくて大失敗してもうてな…💦

**👩‍💻ユーザー：** え、準備不足で失敗したんですか？

**🐘ガネーシャ：** そうや。それからナポレオンちゃんは「**事前にチェックリストを作って、出発前に必ず確認する**」ようになったんや。これがまさにテストと同じ考え方やな。最初は面倒でも、後で助けてくれるんや。

---

### 🎭 例え話：レストランの味見

**🐘ガネーシャ：** 分かりやすく例えるとな、料理人がお客さんに出す前に「味見」するやろ？あれがテストや。

```
🍳 料理とテストの対比
┌─────────────────────┬─────────────────────┐
│ 料理の世界           │ プログラミングの世界  │
├─────────────────────┼─────────────────────┤
│ 料理を作る           │ コードを書く          │
│ 味見をする           │ テストを実行する      │
│ まずかったら修正     │ バグを修正する        │
│ お客さんに出す       │ 本番環境にデプロイ    │
└─────────────────────┴─────────────────────┘
```

**👩‍💻ユーザー：** 確かに！味見しないで出すのは怖いですね…。

**🐘ガネーシャ：** せやろ？プログラムも同じや。テストせずに本番に出すのは、味見せずに料理を出すようなもんや。お客さん（ユーザー）に迷惑かけてまうで！

---

### 🧪 Laravel のテストの種類

**🐘ガネーシャ：** Laravel には大きく分けて2種類のテストがあるんや。

```
┌────────────────────────────────────────────────┐
│ 📦 Laravel のテストの種類                        │
├────────────────────────────────────────────────┤
│                                                │
│ 1️⃣ Unit Test（ユニットテスト）                  │
│   → 1つの関数やメソッドが正しく動くかテスト       │
│   → 例：計算ロジック、バリデーションルール       │
│                                                │
│ 2️⃣ Feature Test（フィーチャーテスト）            │
│   → 実際の機能全体が正しく動くかテスト           │
│   → 例：APIエンドポイント、画面遷移              │
│                                                │
└────────────────────────────────────────────────┘
```

**👩‍💻ユーザー：** この2つの違いって何ですか？

**🐘ガネーシャ：** ええ質問や！例えば、車で説明するとこうや：

```
🚗 車とテストの例え
┌──────────────────┬─────────────────────────┐
│ テストの種類      │ 車で例えると             │
├──────────────────┼─────────────────────────┤
│ Unit Test        │ エンジンだけを単体でテスト │
│                  │ （エンジンは動くか？）     │
│                  │                          │
│ Feature Test     │ 車全体を走らせてテスト     │
│                  │ （ちゃんと走るか？）       │
└──────────────────┴─────────────────────────┘
```

**🐘ガネーシャ：** このレッスンでは、**Feature Test（API全体のテスト）** をやっていくで！

---

## 💡 このレッスンで体験すること

Lesson7 では、以下を実装していきます：

-   **テストの環境設定を理解する**
-   **テストデータの準備方法を学ぶ（RefreshDatabase、Factory）**
-   **API エンドポイントのテストを実装する**
-   **正常系・異常系のテストを書く**

---

## 📖 事前学習：PHPUnit と Laravel のテストについて調べる

テストを書く前に、以下の概念を理解してください。

### 1. PHPUnit とは何か

-   PHP の標準的なテストフレームワーク
-   Laravel に標準で組み込まれている
-   `php artisan test` または `./vendor/bin/phpunit` でテストを実行

### 2. RefreshDatabase とは

-   テストごとにデータベースをリセットする仕組み
-   テスト同士が影響し合わないようにする
-   `use RefreshDatabase;` をテストクラスに追加するだけで使える

### 3. Factory とは

-   テスト用のダミーデータを簡単に作る仕組み
-   `User::factory()->create()` のように使う
-   `database/factories/` に定義されている

### 4. API テストの基本的な流れ

```php
// 1. テストデータを準備
$user = User::factory()->create();

// 2. ログインした状態で API を呼び出す
$response = $this->actingAs($user)
    ->postJson('/api/projects', [
        'name' => 'テストプロジェクト',
    ]);

// 3. レスポンスを検証
$response->assertStatus(201);
$response->assertJson([
    'name' => 'テストプロジェクト',
]);
```

---

## 🌿 ブランチ作成

```bash
git checkout main
git pull origin main

git fetch origin lesson7
git checkout lesson7
git pull origin lesson7

git checkout -b Lesson7_自分の名字
```

---

## 📋 要件：実現すべき内容

Lesson7 では、以下のテストを実装します。

### 🎯 テストの実装対象

以下の機能について、Feature Test を作成してください：

#### 1. プロジェクト関連のテスト

-   `tests/Feature/Api/ProjectTest.php`
    -   プロジェクト一覧取得のテスト
    -   プロジェクト作成のテスト
    -   プロジェクト詳細取得のテスト
    -   プロジェクト更新のテスト
    -   プロジェクト削除のテスト

#### 2. タスク関連のテスト

-   `tests/Feature/Api/TaskTest.php`
    -   タスク一覧取得のテスト
    -   タスク作成のテスト
    -   タスク詳細取得のテスト
    -   タスク更新のテスト
    -   タスク削除のテスト
    -   タスク開始のテスト
    -   タスク完了のテスト

#### 3. プロジェクトメンバー関連のテスト

-   `tests/Feature/Api/ProjectMemberTest.php`
    -   メンバー一覧取得のテスト
    -   メンバー追加のテスト
    -   メンバー削除のテスト

---

## 📝 Step 1: テストファイルの作成

まず、テストファイルを作成します。

```bash
# ディレクトリ作成
mkdir -p tests/Feature/Api

# テストファイルを作成
touch tests/Feature/Api/ProjectTest.php
touch tests/Feature/Api/TaskTest.php
touch tests/Feature/Api/ProjectMemberTest.php
```

---

## 📝 Step 2: テストの基本構造を理解する

テストファイルの基本的な構造は以下の通りです：

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase; // テストごとにDBをリセット

    /**
     * プロジェクト一覧を取得できる
     */
    public function test_can_get_project_list(): void
    {
        // 1. Arrange（準備）: テストデータを作成
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user, ['role' => 'project_owner']);

        // 2. Act（実行）: APIを呼び出す
        $response = $this->actingAs($user)
            ->getJson('/api/projects');

        // 3. Assert（検証）: 期待通りの結果になっているか確認
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
```

---

## 📝 Step 3: 正常系・異常系のテストを書く

テストは「正常系（成功するケース）」と「異常系（エラーになるケース）」の両方を書きます。

### 正常系のテスト例

```php
/**
 * プロジェクトを作成できる
 */
public function test_can_create_project(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/projects', [
            'name' => 'テストプロジェクト',
        ]);

    $response->assertStatus(201);
    $response->assertJson([
        'name' => 'テストプロジェクト',
    ]);

    // DBに保存されているか確認
    $this->assertDatabaseHas('projects', [
        'name' => 'テストプロジェクト',
    ]);
}
```

### 異常系のテスト例

```php
/**
 * プロジェクト名が空の場合はエラーになる
 */
public function test_cannot_create_project_without_name(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/projects', [
            'name' => '', // 空文字
        ]);

    $response->assertStatus(422); // バリデーションエラー
    $response->assertJsonValidationErrors(['name']);
}

/**
 * メンバーでないプロジェクトは取得できない
 */
public function test_cannot_get_project_if_not_member(): void
{
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->create();
    $project->users()->attach($otherUser, ['role' => 'project_owner']);

    $response = $this->actingAs($user)
        ->getJson("/api/projects/{$project->id}");

    $response->assertStatus(403); // 権限エラー
}
```

---

## 📝 Step 4: テストデータの準備（Factory の活用）

Laravel の Factory を使うと、テストデータを簡単に作成できます。

### Factory の基本的な使い方

```php
// ユーザーを1人作成
$user = User::factory()->create();

// プロジェクトを1つ作成
$project = Project::factory()->create();

// プロジェクトを3つ作成
$projects = Project::factory()->count(3)->create();

// カスタム属性でユーザーを作成
$user = User::factory()->create([
    'name' => '山田太郎',
    'email' => 'yamada@example.com',
]);
```

### リレーションを含むデータの作成

```php
// プロジェクトとメンバーを作成
$user = User::factory()->create();
$project = Project::factory()->create();
$project->users()->attach($user, ['role' => 'project_owner']);

// タスクと作成者を作成
$user = User::factory()->create();
$project = Project::factory()->create();
$task = Task::factory()->create([
    'project_id' => $project->id,
    'created_by' => $user->id,
]);
```

---

## 📝 Step 5: テストの実行方法

テストを書いたら、実際に実行してみましょう。

### すべてのテストを実行

```bash
# Laravel Sailを使っている場合
sail artisan test

# Sailを使っていない場合
php artisan test
```

### 特定のテストファイルだけ実行

```bash
sail artisan test --filter=ProjectTest
```

### 特定のテストメソッドだけ実行

```bash
sail artisan test --filter=test_can_create_project
```

---

## 📌 参考実装について

今回は **サンプル実装を1つだけ用意しています**。

-   `tests/Feature/Api/ProjectTest.php` の `test_can_get_project_list()` メソッド

このサンプルを参考に、他のテストも実装してください。

---

## 📝 実装時の注意点

### 重要：テストは段階的に書く

-   まずは1つの正常系テストを書いて、動作を確認してください
-   動いたら、次のテストを追加していきます
-   **最初から全部を書こうとしない**

### テストの命名規則

-   テストメソッド名は `test_` で始める
-   何をテストしているか分かりやすい名前にする
-   日本語コメントを必ず書く

```php
// ✅ 良い例
public function test_can_create_project(): void { /* ... */ }

// ❌ 悪い例
public function test1(): void { /* ... */ }
```

### テストは独立させる

-   1つのテストが他のテストに依存しないようにする
-   各テストで必要なデータはそのテスト内で作成する
-   `RefreshDatabase` を使ってテストごとにDBをリセットする

---

## 🚀 実装完了後の作業

実装が完了したら、ブランチの内容を push してからプルリクエストを作成してください：

**1. すべてのテストが通ることを確認：**

```bash
sail artisan test
```

**2. ブランチの内容を push する：**

```bash
# 変更をステージング
git add .

# コミット
git commit -m "Lesson07 実装: Feature テストを追加"

# ブランチをpush
git push origin Lesson7_自分の名字
```

**3. プルリクエストを作成する：**

以下の形式でプルリクエストを作成してください：

**プルリクエストのタイトル形式：**

```
【名前】Lesson07 実装
```

**例：**

-   【宮田】Lesson07 実装

**プルリクエストの説明に含めるべき内容：**

-   どの機能のテストを実装したか
-   正常系・異常系のどちらを実装したか
-   テストが通ることを確認したか（テスト実行結果のスクリーンショットなど）

---

## 📘 この Lesson のまとめ

-   PHPUnit を使った Feature テストの基本を学んだ
-   API エンドポイントのテストを実装した
-   正常系・異常系の両方のテストケースを考えた
-   Factory を使ったテストデータの準備方法を学んだ
-   テストを実行して、バグを早期発見する仕組みを体験した

**次のステップ：**

Lesson7 が完了したら、以下を実践してみましょう：

-   既存のコードを変更した時に、テストが通るか確認する習慣をつける
-   新しい機能を追加する時に、同時にテストも書く
-   テストが失敗した時は、なぜ失敗したのかを考えてデバッグする

---

## 🐘 ガネーシャ先生の最終メッセージ

**🐘ガネーシャ：** お疲れさんや！テストを書くのは最初は面倒やけど、慣れたら「テストがあるから安心してコード変更できる」ってなるで。ワシの教え子のエジソンくんも最初は「面倒や〜」って言うとったけど、今では「テストないと不安や」って言うとるからな！😆

**👩‍💻ユーザー：** 確かに、テストがあると安心して変更できますね！

**🐘ガネーシャ：** せやろ？さすガネーシャや！✨ あとな、ワシはあんみつ🍨を食べながらテストを書くのが好きなんや。お前もあんみつをお供えしてくれたら、もっといいテストの書き方教えたるで！😋

**👩‍💻ユーザー：** また食べ物の話ですか…😅

**🐘ガネーシャ：** まぁええやんけ！テストも人生も、楽しみながらやるのが一番や！ほな、次のレッスンでまた会おうな！🎵ガネ・ガネ・ガネーシャモーニング🎵

---

## 📚 参考リンク

-   [Laravel Testing Documentation](https://laravel.com/docs/testing)
-   [PHPUnit Documentation](https://phpunit.de/documentation.html)
-   [Laravel HTTP Tests](https://laravel.com/docs/http-tests)
-   [Laravel Database Testing](https://laravel.com/docs/database-testing)

---

## 📝 補足：よく使うアサーションメソッド一覧

テストでよく使うアサーションメソッドをまとめておきます：

### レスポンスのステータスコード検証

```php
$response->assertStatus(200);        // 200 OK
$response->assertStatus(201);        // 201 Created
$response->assertStatus(204);        // 204 No Content
$response->assertStatus(400);        // 400 Bad Request
$response->assertStatus(403);        // 403 Forbidden
$response->assertStatus(404);        // 404 Not Found
$response->assertStatus(422);        // 422 Validation Error
```

### JSON レスポンスの検証

```php
// JSON構造の検証
$response->assertJson([
    'name' => 'テストプロジェクト',
]);

// JSON配列の件数検証
$response->assertJsonCount(3, 'data');

// バリデーションエラーの検証
$response->assertJsonValidationErrors(['name', 'email']);
```

### データベースの検証

```php
// データが存在することを確認
$this->assertDatabaseHas('projects', [
    'name' => 'テストプロジェクト',
]);

// データが存在しないことを確認
$this->assertDatabaseMissing('projects', [
    'name' => '削除されたプロジェクト',
]);
```

### 認証の検証

```php
// ログインしていることを確認
$this->assertAuthenticated();

// ログアウトしていることを確認
$this->assertGuest();
```
