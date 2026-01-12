# Lesson6-9：理解度チェック 🐘

## 〜このコード、何が間違ってる？〜

---

## 🎭 プロローグ

**🐘 ガネーシャ：** 「お前、ここまでよく頑張ったな」

**👩‍💻 ユーザー：** 「はい！エラー処理、だいぶ分かってきました！」

**🐘 ガネーシャ：** 「ほんまか？ほな、ちょっと腕試しや。実際の現場でよくあるミスを見せるから、何が間違っとるか当ててみ」

**👩‍💻 ユーザー：** 「望むところです！」

**🐘 ガネーシャ：** 「自信満々やな...ほな、いくで」

---

## 📝 Q1：403 が返らない！

**🐘 ガネーシャ：** 「後輩がこんなコードを書いてきたんや。『403 Forbidden を返したいのに、500 になっちゃいます』って言うとる」

### コード

```php
// UseCase
class UpdateProjectUseCase
{
    public function execute(Project $project, array $data, User $user): Project
    {
        $this->authorize($project, $user);

        $project->update($data);
        return $project->fresh();
    }

    private function authorize(Project $project, User $user): void
    {
        $membership = $project->memberships()
            ->where('user_id', $user->id)
            ->first();

        if (!$membership || !in_array($membership->role, ['project_owner', 'project_admin'])) {
            // ここで throw してる
            throw new \Exception('このプロジェクトを編集する権限がありません');
        }
    }
}
```

```php
// Controller（プロジェクト更新後にログを残したいので try-catch）
class ProjectController extends ApiController
{
    public function update(UpdateProjectRequest $request, Project $project)
    {
        try {
            $project = $this->updateProjectUseCase->execute(
                $project,
                $request->validated(),
                auth()->user()
            );
        } catch (AuthorizationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        // 成功したらログを残す
        $this->logger->info("プロジェクト更新: {$project->name}");

        return $this->response->success($project, 'プロジェクトを更新しました');
    }
}
```

---

### 🤔 質問

**🐘 ガネーシャ：** 「なんで 403 が返らへんのや？」

<details>
<summary>📖 ヒントを見る</summary>

throw してる例外と、catch してる例外を見比べてみ。

</details>

<details>
<summary>📖 答えを見る</summary>

### ❌ 間違っている箇所

```php
// UseCase の private メソッド
throw new \Exception('このプロジェクトを編集する権限がありません');
//         ↑ ただの Exception を throw してる！
```

```php
// Controller
catch (AuthorizationException $e)
//     ↑ AuthorizationException を待ってる！
```

**throw と catch が揃ってない！**

-   UseCase: `\Exception` を throw
-   Controller: `AuthorizationException` を catch

`Exception` は `AuthorizationException` じゃないから、catch されない。
結果、ApiExceptionHandler に流れて 500 になる。

### ✅ 正しいコード

```php
// UseCase の private メソッド
use Illuminate\Auth\Access\AuthorizationException;

private function authorize(Project $project, User $user): void
{
    // ...
    if (!$membership || !in_array($membership->role, ['project_owner', 'project_admin'])) {
        // ✅ AuthorizationException で throw！
        throw new AuthorizationException('このプロジェクトを編集する権限がありません');
    }
}
```

### 💡 教訓

**throw と catch は必ず揃える！**

private メソッドでも、適切な例外クラスを使おう。

</details>

---

## 📝 Q2：409 が返らない！

**🐘 ガネーシャ：** 「今度は別の後輩や。『ConflictException を throw したのに、500 になります』って」

### コード

```php
// UseCase
class StartTaskUseCase
{
    public function execute(Task $task): Task
    {
        $this->validateStatus($task);

        $task->update(['status' => 'doing']);
        return $task->fresh();
    }

    private function validateStatus(Task $task): void
    {
        if ($task->status !== 'todo') {
            throw new ConflictException('未着手のタスクのみ開始できます');
        }
    }
}
```

```php
// Controller
class TaskController extends ApiController
{
    public function start(Task $task)
    {
        try {
            $task = $this->startTaskUseCase->execute($task);
            return $this->response->success($task, 'タスクを開始しました');

        } catch (Exception $e) {
            return $this->response->serverError('サーバーエラーが発生しました');
        }
    }
}
```

---

### 🤔 質問

**🐘 ガネーシャ：** 「UseCase は正しく ConflictException を throw しとる。なのに、なんで 500 になるんや？」

<details>
<summary>📖 ヒントを見る</summary>

Controller の catch を見てみ。何をキャッチしとる？

</details>

<details>
<summary>📖 答えを見る</summary>

### ❌ 間違っている箇所

```php
// Controller
catch (Exception $e) {
    return $this->response->serverError('サーバーエラーが発生しました');
}
```

**catch (Exception $e) で全部捕まえちゃってる！**

```
ConflictException extends Exception
                          ↑
        ConflictException は Exception の子クラス
        だから catch (Exception) で捕まる！
```

せっかく UseCase が正しく ConflictException を throw しても、Controller で `Exception` として catch して、500 を返しちゃってる。

### ✅ 正しいコード

**パターン A：Controller で catch しない（推奨）**

```php
// Controller
class TaskController extends ApiController
{
    public function start(Task $task)
    {
        // ✅ try-catch なし！ApiExceptionHandler に任せる
        $task = $this->startTaskUseCase->execute($task);
        return $this->response->success($task, 'タスクを開始しました');
    }
}
```

**パターン B：どうしても catch するなら具体的な例外で**

```php
// Controller
class TaskController extends ApiController
{
    public function start(Task $task)
    {
        try {
            $task = $this->startTaskUseCase->execute($task);
        } catch (ConflictException $e) {
            // ✅ 具体的な例外を catch
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return $this->response->success($task, 'タスクを開始しました');
    }
}
```

### 💡 教訓

**catch (Exception $e) は危険！全部捕まえちゃう！**

-   基本は try-catch を書かない（ApiExceptionHandler に任せる）
-   どうしても必要な時は、具体的な例外クラスを catch する

</details>

---

## 📝 Q3：総合問題

**🐘 ガネーシャ：** 「最後は総合問題や。このコードには複数の問題がある。全部見つけてみ」

### コード

```php
// UseCase
class RemoveMemberUseCase
{
    public function execute(Membership $membership, User $actor): void
    {
        $this->authorize($membership, $actor);
        $this->validateLastOwner($membership);

        $membership->delete();
    }

    private function authorize(Membership $membership, User $actor): void
    {
        $actorMembership = $membership->project->memberships()
            ->where('user_id', $actor->id)
            ->first();

        if (!$actorMembership || $actorMembership->role === 'project_member') {
            // 問題点 ①
            throw new \Exception('メンバーを削除する権限がありません');
        }
    }

    private function validateLastOwner(Membership $membership): void
    {
        if ($membership->role !== 'project_owner') {
            return;
        }

        $ownerCount = $membership->project->memberships()
            ->where('role', 'project_owner')
            ->count();

        if ($ownerCount <= 1) {
            // 問題点 ②
            throw new \RuntimeException('最後のオーナーは削除できません');
        }
    }
}
```

```php
// Controller
class MembershipController extends ApiController
{
    public function destroy(Membership $membership)
    {
        try {
            $this->removeMemberUseCase->execute($membership, auth()->user());

        // 問題点 ③
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->response->serverError('削除に失敗しました');
        }

        return $this->response->success(null, 'メンバーを削除しました');
    }
}
```

---

### 🤔 質問

**🐘 ガネーシャ：** 「3 つの問題点があるで。全部見つけられるか？」

<details>
<summary>📖 ヒント①</summary>

authorize メソッドの throw を見てみ。権限エラーやのに、何を throw しとる？

</details>

<details>
<summary>📖 ヒント②</summary>

validateLastOwner メソッドの throw を見てみ。ビジネスルール違反やのに、何を throw しとる？

</details>

<details>
<summary>📖 ヒント③</summary>

Controller の catch を見てみ。これやと、どうなる？

</details>

<details>
<summary>📖 答えを見る</summary>

### ❌ 問題点 ①：権限エラーなのに \Exception

```php
// authorize メソッド
throw new \Exception('メンバーを削除する権限がありません');
```

権限エラーは **403 Forbidden** を返すべき。
`AuthorizationException` を使うべき。

**修正：**

```php
throw new AuthorizationException('メンバーを削除する権限がありません');
```

---

### ❌ 問題点 ②：ビジネスルール違反なのに \RuntimeException

```php
// validateLastOwner メソッド
throw new \RuntimeException('最後のオーナーは削除できません');
```

ビジネスルール違反は **409 Conflict** を返すべき。
`ConflictException` を使うべき。

**修正：**

```php
throw new ConflictException('最後のオーナーは削除できません');
```

---

### ❌ 問題点 ③：catch (Exception $e) で全部 500 にしてる

```php
} catch (Exception $e) {
    Log::error($e->getMessage());
    return $this->response->serverError('削除に失敗しました');
}
```

せっかく正しい例外を throw しても、ここで全部捕まって 500 になる。

**修正：**

```php
// try-catch を削除して、ApiExceptionHandler に任せる
public function destroy(Membership $membership)
{
    $this->removeMemberUseCase->execute($membership, auth()->user());

    return $this->response->success(null, 'メンバーを削除しました');
}
```

---

### ✅ 修正後の全体コード

```php
// UseCase
class RemoveMemberUseCase
{
    public function execute(Membership $membership, User $actor): void
    {
        $this->authorize($membership, $actor);
        $this->validateLastOwner($membership);

        $membership->delete();
    }

    private function authorize(Membership $membership, User $actor): void
    {
        $actorMembership = $membership->project->memberships()
            ->where('user_id', $actor->id)
            ->first();

        if (!$actorMembership || $actorMembership->role === 'project_member') {
            // ✅ AuthorizationException で 403
            throw new AuthorizationException('メンバーを削除する権限がありません');
        }
    }

    private function validateLastOwner(Membership $membership): void
    {
        if ($membership->role !== 'project_owner') {
            return;
        }

        $ownerCount = $membership->project->memberships()
            ->where('role', 'project_owner')
            ->count();

        if ($ownerCount <= 1) {
            // ✅ ConflictException で 409
            throw new ConflictException('最後のオーナーは削除できません');
        }
    }
}
```

```php
// Controller
class MembershipController extends ApiController
{
    public function destroy(Membership $membership)
    {
        // ✅ try-catch なし！ApiExceptionHandler に任せる
        $this->removeMemberUseCase->execute($membership, auth()->user());

        return $this->response->success(null, 'メンバーを削除しました');
    }
}
```

### 💡 教訓

1. **権限エラー** → `AuthorizationException`（403）
2. **ビジネスルール違反** → `ConflictException`（409）
3. **catch (Exception $e) は危険** → 基本は ApiExceptionHandler に任せる

</details>

---

## 🐘 ガネーシャの総評

**🐘 ガネーシャ：** 「どうや、全問正解できたか？」

**👩‍💻 ユーザー：** 「Q3 は難しかったです...3 つ全部は見つけられなかった...」

**🐘 ガネーシャ：** 「ええんやで。こういうミスは現場でもよくあるんや。大事なのは、**パターンを覚えること**や」

---

### 📊 よくあるミスまとめ

| ミスのパターン                                    | 結果                  | 正しい書き方                                     |
| ------------------------------------------------- | --------------------- | ------------------------------------------------ |
| throw が `\Exception` なのに catch が具体的な例外 | catch されず 500      | **throw を具体的な例外に**                       |
| throw が具体的な例外なのに catch が `Exception`   | 全部 catch されて 500 | **catch しない（ApiExceptionHandler に任せる）** |
| 権限エラーを `\Exception` で throw                | 500 になる            | **AuthorizationException**                       |
| ビジネスルール違反を `\Exception` で throw        | 500 になる            | **ConflictException**                            |

---

### 🎯 覚えておくべき原則

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  1️⃣  throw と catch は必ず揃える                           │
│                                                             │
│  2️⃣  private メソッドでも適切な例外を使う                  │
│                                                             │
│  3️⃣  基本は catch しない（ApiExceptionHandler に任せる）   │
│                                                             │
│  4️⃣  どうしても catch するなら具体的な例外で               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**🐘 ガネーシャ：** 「この原則を守っとけば、エラー処理で困ることはないで」

**👩‍💻 ユーザー：** 「はい！しっかり覚えます！」

**🐘 ガネーシャ：** 「よっしゃ、これで本当にエラー処理マスターや！さすガネーシャ！🐘✨」
