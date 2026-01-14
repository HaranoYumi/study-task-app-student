# Lesson6-10: フロントエンドのエラーハンドリング統一 🐘

## 〜ガネーシャと学ぶ！バックエンドとフロントエンドの連携〜

---

## 🌿 ブランチ切り替えと準備

課題に取り組む前に、リモートの全てのブランチを取得してから、Lesson 用のブランチに切り替えてください：

```bash
# リモートの全てのブランチ情報を取得
git fetch origin

# Lesson用のブランチに切り替え
git checkout lesson6-10

# リモートの最新状態に更新
git pull origin lesson6-10
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

## 🎭 プロローグ：バックエンドを統一したのに上手くいかない...

**👩‍💻 ユーザー：** 「うわあああ〜〜〜ん！😭」

**🐘 ガネーシャ：** 「おいおい、なんや騒がしいな。どないしたんや？」

**👩‍💻 ユーザー：** 「ガネーシャさん！聞いてくださいよ！Lesson 6〜8 でバックエンドのエラーハンドリングを統一したじゃないですか！」

**🐘 ガネーシャ：** 「おお、せやな。ApiExceptionHandler でエラーレスポンスを統一して、カスタム例外も作ったやろ」

**👩‍💻 ユーザー：** 「それなのに...それなのに...なんか上手くいかないんです！エラーメッセージがちゃんと表示されなかったり、なんかバラバラで...」

**🐘 ガネーシャ：** 「ふむ...ちょっとコード見せてみ」

**👩‍💻 ユーザー：** 「はい...」

---

### 😱 問題発覚：コンソールを見たガネーシャ

**🐘 ガネーシャ：** 「...おい」

**👩‍💻 ユーザー：** 「は、はい？」

**🐘 ガネーシャ：** 「これ、本番環境やんな？」

**👩‍💻 ユーザー：** 「そうですけど...」

**🐘 ガネーシャ：** 「**なんでコンソールにエラーの詳細がダダ漏れになっとるんや！！**」

```
Console（本番環境なのに...）:
Failed to fetch project: AxiosError: Request failed with status code 404
    at XMLHttpRequest...
Failed to create task: AxiosError: Request failed with status code 422
    at XMLHttpRequest...
メンバー削除エラー: AxiosError: Request failed with status code 409
    at XMLHttpRequest...
```

**👩‍💻 ユーザー：** 「え？コンソールって...ユーザーさんには見えないですよね？」

**🐘 ガネーシャ：** 「アホか！デベロッパーツール開いたら誰でも見えるわ！悪意のあるユーザーやったら、お前のシステムの内部構造丸わかりやぞ！**セキュリティリスク**や！」

**👩‍💻 ユーザー：** 「ひいっ！！」

---

### 🔍 さらに問題発覚：コードを見たガネーシャ

**🐘 ガネーシャ：** 「ほんで、このコードは何や...」

```javascript
// Show.vue（プロジェクト詳細ページ）

// 各コンポーネントで自前の error を定義...
const error = ref(null);
const memberError = ref(null);
const taskError = ref(null);

// プロジェクト取得
const fetchProject = async () => {
    try {
        loading.value = true;
        error.value = null; // 毎回手動でクリア
        const response = await axios.get(`/api/projects/${projectId}`);
        project.value = response.data.data;
    } catch (err) {
        console.error("Failed to fetch project:", err);
        error.value =
            err.response?.data?.message ||
            "プロジェクトの読み込みに失敗しました";
    } finally {
        loading.value = false;
    }
};

// タスク作成
const createTask = async () => {
    try {
        creatingTask.value = true;
        taskError.value = null; // また手動でクリア
        const response = await axios.post(
            `/api/projects/${projectId}/tasks`,
            newTask.value
        );
        tasks.value.unshift(response.data.data);
    } catch (err) {
        console.error("Failed to create task:", err); // 英語？日本語？
        taskError.value =
            err.response?.data?.message || "タスクの作成に失敗しました";
    } finally {
        creatingTask.value = false;
    }
};

// メンバー削除
const deleteMember = async (userId) => {
    try {
        memberError.value = null; // またまた手動でクリア
        await axios.delete(`/api/projects/${projectId}/members/${userId}`);
        members.value = members.value.filter((m) => m.id !== userId);
    } catch (err) {
        console.error("メンバー削除エラー:", err); // あれ、こっちは日本語？
        memberError.value =
            err.response?.data?.message || "メンバーの削除に失敗しました";
    }
};
```

**🐘 ガネーシャ：** 「...お前、せっかくバックエンド統一したのに、フロントエンドが**バラバラ地獄**やないか」

**👩‍💻 ユーザー：** 「えっ...でも、バックエンドさえちゃんとしてれば大丈夫かなって...」

**🐘 ガネーシャ：** 「**バックエンドだけじゃダメなんや！！**」

**👩‍💻 ユーザー：** 「ええええ！バックエンドだけじゃダメなんですかあ〜〜〜！？😭」

---

### 🐘 ガネーシャの説教タイム

**🐘 ガネーシャ：** 「いいか、よう聞けや。**バックエンドとフロントエンドは車の両輪**なんや。片方だけ良くても、車はまっすぐ走らへんやろ？」

**👩‍💻 ユーザー：** 「た、確かに...」

**🐘 ガネーシャ：** 「お前のバックエンドは、ちゃんと統一されたエラーレスポンスを返しとる」

```json
{
    "success": false,
    "message": "指定されたデータが見つかりません",
    "request_id": "req_6965762eb033e6.53867346"
}
```

**🐘 ガネーシャ：** 「`success` でエラーかどうか分かる、`message` で何が起きたか分かる、`request_id` でログ追跡もできる。**完璧なレスポンス形式**や」

**👩‍💻 ユーザー：** 「はい、頑張って作りました！」

**🐘 ガネーシャ：** 「**なのに！！**フロントエンドがこの形式をちゃんと活用できてへんやないか！」

---

### 🔍 問題点を整理

**🐘 ガネーシャ：** 「問題点を整理したるわ」

```
┌─────────────────────────────────────────────────────────────┐
│                    現状の問題点                              │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ❌ 問題1：同じコードの繰り返し（DRY原則違反）               │
│     ├── err.response?.data?.message || "..." が何度も       │
│     ├── error.value = null が何度も                         │
│     └── console.error(...) が何度も                         │
│                                                             │
│  ❌ 問題2：エラー変数がバラバラ                              │
│     ├── error（プロジェクト用）                             │
│     ├── taskError（タスク用）                               │
│     ├── memberError（メンバー用）                           │
│     └── 新機能追加のたびに増えていく...                     │
│                                                             │
│  ❌ 問題3：console.error の出力形式がバラバラ               │
│     ├── "Failed to fetch project:"（英語）                 │
│     ├── "メンバー削除エラー:"（日本語）                     │
│     └── 統一されていない                                    │
│                                                             │
│  ❌ 問題4：本番環境でも console.error が出る 🚨重大🚨       │
│     ├── ユーザーがデベロッパーツールで見える                │
│     ├── システムの内部構造が漏洩                            │
│     └── セキュリティリスク！                                │
│                                                             │
│  ❌ 問題5：バリデーションエラーの扱いがない                  │
│     └── errors プロパティを取得していない                   │
│                                                             │
│  ❌ 問題6：バックエンドの統一が活かされていない              │
│     ├── success フラグを使っていない                        │
│     ├── request_id を取得していない                         │
│     └── せっかくの統一形式が台無し                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「うう...問題だらけ...」

**🐘 ガネーシャ：** 「大丈夫や。ワシが助けたるから、一緒に直していこうや」

**👩‍💻 ユーザー：** 「ガネーシャさん...！😭✨」

**🐘 ガネーシャ：** 「ワシの教え子のヘンリー・フォードくんがな、『品質は一貫性から生まれる』って言うとったわ。フロントエンドも統一するで！」

---

## 📖 第 1 章：設計思想を理解しよう

### 🎯 バックエンドとフロントエンドの役割分担

**🐘 ガネーシャ：** 「まず、大事な考え方を教えるで」

```
┌─────────────────────────────────────────────────────────────┐
│                    役割分担                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【バックエンド（Laravel）の責務】                           │
│  ├── 統一されたエラーレスポンス形式を返す                   │
│  │   {                                                      │
│  │     "success": false,                                    │
│  │     "message": "エラーメッセージ",                       │
│  │     "request_id": "req_xxx"                              │
│  │   }                                                      │
│  ├── 環境に応じた適切なメッセージを返す                     │
│  │   ├── 本番: "サーバーエラーが発生しました"              │
│  │   └── 開発: "Call to undefined method..."（詳細）       │
│  └── バリデーションエラーを構造化して返す                   │
│                                                             │
│  【フロントエンド（Vue）の責務】                             │
│  ├── バックエンドが返すメッセージを信頼して表示             │
│  ├── 万が一の時だけフォールバックメッセージを使う           │
│  ├── 環境に応じてログ出力を制御（本番では出さない！）       │
│  ├── request_id を保存（サポート問い合わせ用）              │
│  └── バリデーションエラーをフォームに表示                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「フロントエンドは基本的にバックエンドを信頼するってことですか？」

**🐘 ガネーシャ：** 「せや！**バックエンドがちゃんと仕事してるなら、フロントエンドは余計なことせんでええ**んや。Lesson 10 で ApiExceptionHandler を作ったやろ？あれが環境に応じたメッセージを返してくれるから、フロントエンドはそれをそのまま表示すればええんや」

---

### 📊 エラーレスポンスの構造（復習）

**🐘 ガネーシャ：** 「お前が作ったバックエンドのレスポンス構造を復習しとこか」

```javascript
// 通常のエラー（403, 404, 409, 500 など）
{
    "success": false,
    "message": "指定されたデータが見つかりません",
    "request_id": "req_6965762eb033e6.53867346"
}

// バリデーションエラー（422）
{
    "success": false,
    "message": "The title field is required.",
    "request_id": "req_6965762eb033e6.53867346",
    "errors": {
        "title": ["タイトルは必須です"],
        "description": ["説明は1000文字以内で入力してください"]
    }
}
```

**🐘 ガネーシャ：** 「見てみ。全部同じ形式やろ？」

| プロパティ   | 説明                                          |
| ------------ | --------------------------------------------- |
| `success`    | 成功/失敗を示すフラグ（エラー時は `false`）   |
| `message`    | ユーザーに表示するメッセージ                  |
| `request_id` | ログ追跡用の ID（サポート問い合わせ時に便利） |
| `errors`     | バリデーションエラーの詳細（422 の時のみ）    |

**👩‍💻 ユーザー：** 「バックエンドが統一されてるから、フロントエンドも同じように扱えるんですね！」

**🐘 ガネーシャ：** 「その通り！さすガネーシャの教え子や！」

---

## 📖 第 2 章：共通化の仕組みを作ろう

### 🛠️ 2 つのファイルを作成

**🐘 ガネーシャ：** 「フロントエンドのエラーハンドリングは、2 つのファイルで構成するで」

```
resources/js/
├── composables/
│   └── useApiError.js    ← 状態管理（Composable）【新規作成】
└── utils/
    └── apiError.js       ← ユーティリティ関数【新規作成】
```

| ファイル         | 役割                                        | 使う場面                           |
| ---------------- | ------------------------------------------- | ---------------------------------- |
| `useApiError.js` | 状態を持つ（ref）、Vue コンポーネントで使う | コンポーネント内でエラー状態を管理 |
| `apiError.js`    | 純粋な関数、状態を持たない                  | エラーメッセージの抽出だけしたい時 |

**👩‍💻 ユーザー：** 「なんで 2 つに分けるんですか？1 つでもよくないですか？」

**🐘 ガネーシャ：** 「ええ質問や！**関心の分離**っちゅうやつや」

```
┌─────────────────────────────────────────────────────────────┐
│                    なぜ分けるか？                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  useApiError.js（Composable）                               │
│  ├── Vue の ref を使う（状態管理）                          │
│  ├── コンポーネントのライフサイクルに依存                   │
│  └── テストする時は Vue の環境が必要                        │
│                                                             │
│  apiError.js（ユーティリティ）                              │
│  ├── 純粋な関数（入力→出力のみ）                           │
│  ├── Vue に依存しない                                       │
│  └── 単体テストが簡単                                       │
│                                                             │
│  メリット：                                                  │
│  ├── apiError.js だけ使いたい時も使える                     │
│  ├── テストがしやすい                                       │
│  └── 責務が明確                                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 📝 Step 1：apiError.js を作成

**🐘 ガネーシャ：** 「まずはユーティリティ関数から作るで」

```javascript
// resources/js/utils/apiError.js

/**
 * APIエラーからメッセージを抽出する
 *
 * 【基本方針】
 * バックエンドが統一された形式でメッセージを返すため、
 * フロントエンドは基本的にそれを信頼して表示します。
 * デフォルトメッセージは「万が一」のフォールバックです。
 *
 * 【バックエンドのエラーレスポンス形式】
 * {
 *   "success": false,
 *   "message": "エラーメッセージ",
 *   "request_id": "req_xxx"
 * }
 */
export function extractErrorMessage(
    error,
    defaultMessage = "エラーが発生しました"
) {
    // ────────────────────────────────────────────────────
    // ケース1：ネットワークエラー（responseがない場合）
    // ────────────────────────────────────────────────────
    // インターネット接続切断、DNSエラー、タイムアウトなど
    // この場合、バックエンドからのレスポンスは存在しない
    if (!error.response) {
        if (error.message && error.message.toLowerCase().includes("network")) {
            return "ネットワークエラーが発生しました。インターネット接続を確認してください。";
        }
        return error.message || "サーバーに接続できませんでした。";
    }

    const response = error.response;
    const data = response.data;

    // ────────────────────────────────────────────────────
    // ケース2：バックエンドからメッセージがある（通常のケース）
    // ────────────────────────────────────────────────────
    // これが一番多いパターン！
    // バックエンドの ApiExceptionHandler が返すメッセージをそのまま使う
    if (data?.message) {
        return data.message; // ← バックエンドを信頼！
    }

    // ────────────────────────────────────────────────────
    // ケース3：バリデーションエラー（errorsプロパティがある場合）
    // ────────────────────────────────────────────────────
    // message がなくて errors だけある場合のフォールバック
    if (data?.errors) {
        const firstError = Object.values(data.errors)[0];
        if (Array.isArray(firstError) && firstError.length > 0) {
            return firstError[0];
        }
        if (typeof firstError === "string") {
            return firstError;
        }
    }

    // ────────────────────────────────────────────────────
    // ケース4：HTTPステータスコードに基づくフォールバック
    // ────────────────────────────────────────────────────
    // 通常は到達しないが、バックエンドがメッセージを返さなかった場合の保険
    const statusMessages = {
        400: "入力内容に誤りがあります",
        401: "ログインが必要です",
        403: "この操作を行う権限がありません",
        404: "指定されたデータが見つかりません",
        422: "入力内容を確認してください",
        429: "アクセスが集中しています。しばらく待ってから再度お試しください",
        500: "サーバーエラーが発生しました",
        503: "ただいまメンテナンス中です",
    };

    return statusMessages[response.status] || defaultMessage;
}

/**
 * APIエラーからバリデーションエラーを抽出する
 */
export function extractValidationErrors(error) {
    if (!error.response?.data?.errors) {
        return null;
    }
    return error.response.data.errors;
}
```

---

### 📝 処理フローを図で理解

**🐘 ガネーシャ：** 「文字だけやと分かりにくいから、図で見てみよか」

```
extractErrorMessage の処理フロー

┌─────────────────┐
│  エラー発生！    │
└────────┬────────┘
         ▼
┌─────────────────────┐
│ error.response      │
│ がある？            │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   Yes       No
    │         │
    ▼         ▼
┌────────┐  ┌────────────────────┐
│次へ    │  │ネットワークエラー   │
└────┬───┘  │「接続を確認」       │
     │      └────────────────────┘
     ▼
┌─────────────────────┐
│ data.message        │
│ がある？            │
└────────┬────────────┘
         │
    ┌────┴────┐
    │         │
   Yes       No
    │         │
    ▼         ▼
┌────────────┐  ┌─────────────────┐
│それを返す   │  │ステータスコード  │
│（バックエンド│  │に応じた         │
│を信頼）    │  │フォールバック    │
└────────────┘  └─────────────────┘
```

**👩‍💻 ユーザー：** 「ほとんどの場合は『バックエンドを信頼』のルートを通るんですね！」

**🐘 ガネーシャ：** 「せや！フォールバックは**保険**みたいなもんや。使われへんけど、あると安心やろ？」

---

### 📝 Step 2：useApiError.js を作成

**🐘 ガネーシャ：** 「次は Composable を作るで。これが Vue コンポーネントから使う本体や」

```javascript
// resources/js/composables/useApiError.js

import { ref } from "vue";
import { extractErrorMessage, extractValidationErrors } from "@/utils/apiError";

/**
 * APIエラーハンドリング用のComposable
 *
 * 使い方：
 * const { error, validationErrors, handleError, clearError } = useApiError();
 */
export function useApiError() {
    // ──────────────────────────────────────
    // 状態管理
    // ──────────────────────────────────────
    const error = ref(null); // エラーメッセージ
    const validationErrors = ref({}); // バリデーションエラー
    const requestId = ref(null); // リクエストID（ログ追跡用）
    const statusCode = ref(null); // ステータスコード

    // ──────────────────────────────────────
    // エラーを処理するメイン関数
    // ──────────────────────────────────────
    const handleError = (err, defaultMessage = "エラーが発生しました") => {
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        // 【重要】開発環境のみ詳細ログを出力
        // 本番では出力しない（セキュリティ対策）
        // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if (import.meta.env.VITE_APP_DEBUG === "true") {
            console.group("🚨 API Error");
            console.error("Error:", err);
            if (err.response) {
                console.error("Status:", err.response.status);
                console.error("Data:", err.response.data);
                console.error("URL:", err.config?.url);
            } else {
                console.error("Network Error:", err.message);
            }
            console.groupEnd();
        }
        // 本番環境では console に何も出力しない！
        // エラー監視は Sentry などのツールで行う

        // エラーメッセージを抽出（ユーティリティ関数を使用）
        error.value = extractErrorMessage(err, defaultMessage);

        // リクエストIDとステータスコードを保存
        if (err.response?.data) {
            requestId.value = err.response.data.request_id || null;
            statusCode.value = err.response.status || null;
        } else {
            requestId.value = null;
            statusCode.value = null;
        }

        // バリデーションエラーを抽出
        const validation = extractValidationErrors(err);
        validationErrors.value = validation || {};
    };

    // ──────────────────────────────────────
    // エラーをクリアする
    // ──────────────────────────────────────
    const clearError = () => {
        error.value = null;
        validationErrors.value = {};
        requestId.value = null;
        statusCode.value = null;
    };

    // ──────────────────────────────────────
    // 返却
    // ──────────────────────────────────────
    return {
        error,
        validationErrors,
        requestId,
        statusCode,
        handleError,
        clearError,
    };
}
```

---

### 🔑 VITE_APP_DEBUG による環境切り替え

**👩‍💻 ユーザー：** 「`import.meta.env.VITE_APP_DEBUG` って何ですか？」

**🐘 ガネーシャ：** 「ええ質問や！これは **環境変数** っちゅうやつで、開発環境と本番環境で動作を切り替えるために使うんや。**さっき指摘した問題を解決する鍵**や！」

```bash
# .env ファイル

# 開発環境
VITE_APP_DEBUG=true

# 本番環境
VITE_APP_DEBUG=false
```

```
┌─────────────────────────────────────────────────────────────┐
│                    環境による違い                            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【開発環境】 VITE_APP_DEBUG=true                           │
│  ├── console.group("🚨 API Error") が出力される             │
│  ├── エラーの詳細が Console に表示される                    │
│  └── デバッグしやすい 👍                                    │
│                                                             │
│  【本番環境】 VITE_APP_DEBUG=false                          │
│  ├── console には何も出力されない                           │
│  ├── ユーザーにはエラーメッセージだけ表示                   │
│  ├── 内部構造が漏洩しない 🔒                                │
│  └── Sentry などのエラー監視ツールで管理                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「なるほど！これで本番環境でコンソールに詳細が出なくなるんですね！」

**🐘 ガネーシャ：** 「せや！**セキュリティリスクを解消**しつつ、開発中は便利にデバッグできる。一石二鳥やな！」

---

### 🔑 request_id の活用

**🐘 ガネーシャ：** 「それと、バックエンドが返す `request_id` も保存しとるで」

**👩‍💻 ユーザー：** 「`request_id` って何に使うんですか？」

**🐘 ガネーシャ：** 「ええ質問や！例えばな、ユーザーから『エラーが出ました』って問い合わせがあった時」

```
ユーザー：「タスク作成でエラーになりました」
サポート：「request_id を教えてください」
ユーザー：「req_6965762eb033e6.53867346 です」
サポート：「（ログを検索）...あ、バリデーションエラーですね」
```

**🐘 ガネーシャ：** 「`request_id` があれば、**サーバーログと突き合わせて原因特定**できるんや。これがないと『いつ、どのリクエストでエラーが起きたか』が分からへん」

---

## 📖 第 3 章：Before / After で比較

### ❌ Before：手動でバラバラに書いている

```javascript
// Show.vue（プロジェクト詳細ページ）

// 問題1：エラー変数が複数
const error = ref(null);
const memberError = ref(null);
const taskError = ref(null);

// 問題2：同じコードの繰り返し
const fetchProject = async () => {
    try {
        loading.value = true;
        error.value = null; // 手動クリア
        const response = await axios.get(`/api/projects/${projectId}`);
        project.value = response.data.data;
    } catch (err) {
        console.error("Failed to fetch project:", err); // 🚨 本番でも出力される！
        error.value =
            err.response?.data?.message ||
            "プロジェクトの読み込みに失敗しました";
    } finally {
        loading.value = false;
    }
};

const createTask = async () => {
    try {
        creatingTask.value = true;
        taskError.value = null; // また手動クリア
        const response = await axios.post(
            `/api/projects/${projectId}/tasks`,
            newTask.value
        );
        tasks.value.unshift(response.data.data);
    } catch (err) {
        console.error("Failed to create task:", err); // 🚨 英語で統一されてない
        taskError.value =
            err.response?.data?.message || "タスクの作成に失敗しました";
    } finally {
        creatingTask.value = false;
    }
};

const deleteMember = async (userId) => {
    try {
        memberError.value = null; // またまた手動クリア
        await axios.delete(`/api/projects/${projectId}/members/${userId}`);
        members.value = members.value.filter((m) => m.id !== userId);
    } catch (err) {
        console.error("メンバー削除エラー:", err); // 🚨 日本語？統一されてない
        memberError.value =
            err.response?.data?.message || "メンバーの削除に失敗しました";
    }
};
```

**問題点：**

| 問題                           | 説明                                                  |
| ------------------------------ | ----------------------------------------------------- |
| **DRY 原則違反**               | `err.response?.data?.message \|\| "..."` が何度も出現 |
| **変数の乱立**                 | error, memberError, taskError...                      |
| **ログ形式がバラバラ**         | 英語だったり日本語だったり                            |
| **本番でもログが出る**         | 🚨 **セキュリティリスク**                             |
| **バリデーションエラー未対応** | errors を取得していない                               |
| **request_id 未取得**          | ログ追跡ができない                                    |

---

### ✅ After：useApiError で統一

```javascript
// Show.vue（プロジェクト詳細ページ）

import { useApiError } from "@/composables/useApiError";

// 1つの Composable で全て管理！
const { error, validationErrors, requestId, handleError, clearError } =
    useApiError();

// 統一されたエラーハンドリング
const fetchProject = async () => {
    try {
        loading.value = true;
        clearError(); // 共通のクリア関数
        const response = await axios.get(`/api/projects/${projectId}`);
        project.value = response.data.data;
    } catch (err) {
        handleError(err, "プロジェクトの読み込みに失敗しました");
        // ✅ ログ出力は handleError 内で環境に応じて自動制御
        // ✅ request_id も自動で保存される
    } finally {
        loading.value = false;
    }
};

const createTask = async () => {
    try {
        creatingTask.value = true;
        clearError();
        const response = await axios.post(
            `/api/projects/${projectId}/tasks`,
            newTask.value
        );
        tasks.value.unshift(response.data.data);
    } catch (err) {
        handleError(err, "タスクの作成に失敗しました");
        // ✅ バリデーションエラーは validationErrors に自動で入る
    } finally {
        creatingTask.value = false;
    }
};

const deleteMember = async (userId) => {
    try {
        clearError();
        await axios.delete(`/api/projects/${projectId}/members/${userId}`);
        members.value = members.value.filter((m) => m.id !== userId);
    } catch (err) {
        handleError(err, "メンバーの削除に失敗しました");
    }
};
```

**改善点：**

| 改善                   | 説明                                |
| ---------------------- | ----------------------------------- |
| **DRY 原則**           | 同じ処理を 1 箇所に集約             |
| **変数の統一**         | error 1 つで全て管理                |
| **ログ形式の統一**     | handleError 内で統一                |
| **環境に応じたログ**   | 🔒 **開発のみ出力、本番は出さない** |
| **バリデーション対応** | validationErrors で取得可能         |
| **request_id 保存**    | ログ追跡が可能に                    |

---

## 📖 第 4 章：テンプレートでの表示

### 🎯 エラーメッセージを画面に表示

**🐘 ガネーシャ：** 「`error` の値を画面に表示する方法を見てみよか」

```html
<!-- Show.vue のテンプレート -->

<!-- エラー表示エリア -->
<div
    v-if="error"
    class="bg-red-500/10 border border-red-300/50 rounded-2xl p-6"
>
    <div class="flex items-center gap-3">
        <svg
            class="w-6 h-6 text-red-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
        </svg>
        <p class="text-red-800 font-medium">{{ error }}</p>
    </div>
</div>

<!-- メインコンテンツ -->
<div v-else>
    <!-- プロジェクト詳細など -->
</div>
```

**👩‍💻 ユーザー：** 「`v-if="error"` でエラーがある時だけ表示するんですね！」

**🐘 ガネーシャ：** 「せや！error が null の時は何も表示されへん」

---

### 🎯 バリデーションエラーをフォームに表示

**🐘 ガネーシャ：** 「バリデーションエラーは `validationErrors` に入るから、フォームの各フィールドに表示できるで」

```html
<!-- タスク作成フォーム -->
<form @submit.prevent="createTask" class="space-y-5">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            タスク名
        </label>
        <input
            v-model="newTask.title"
            type="text"
            class="w-full px-4 py-3 rounded-xl border"
            :class="validationErrors.title ? 'border-red-500' : 'border-slate-200'"
            placeholder="タスク名を入力してください"
        />
        <!-- バリデーションエラー表示 -->
        <p v-if="validationErrors.title" class="text-red-500 text-sm mt-1">
            {{ validationErrors.title[0] }}
        </p>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            説明（任意）
        </label>
        <textarea
            v-model="newTask.description"
            rows="3"
            class="w-full px-4 py-3 rounded-xl border"
            :class="validationErrors.description ? 'border-red-500' : 'border-slate-200'"
            placeholder="タスクの詳細を入力してください"
        ></textarea>
        <p
            v-if="validationErrors.description"
            class="text-red-500 text-sm mt-1"
        >
            {{ validationErrors.description[0] }}
        </p>
    </div>

    <button type="submit" :disabled="creatingTask">
        {{ creatingTask ? "作成中..." : "タスクを作成" }}
    </button>
</form>
```

**👩‍💻 ユーザー：** 「`validationErrors.title[0]` の `[0]` は何ですか？」

**🐘 ガネーシャ：** 「バリデーションエラーは**配列**で返ってくるんや。1 つのフィールドに複数のエラーがある場合があるからな」

```javascript
// バックエンドから返ってくる形式
{
    "success": false,
    "message": "The title field is required.",
    "request_id": "req_xxx",
    "errors": {
        "title": [
            "タイトルは必須です",           // [0] ← 通常これを表示
            "タイトルは255文字以内です"     // [1]
        ],
        "email": [
            "メールアドレスの形式が正しくありません"
        ]
    }
}
```

---

## 📖 第 5 章：実装の移行手順

**🐘 ガネーシャ：** 「既存のコードを統一する手順をまとめるで」

### Step 1：ファイルを作成

```bash
# ディレクトリ作成
mkdir -p resources/js/utils
mkdir -p resources/js/composables

# ファイル作成
touch resources/js/utils/apiError.js
touch resources/js/composables/useApiError.js
```

### Step 2：apiError.js を実装

```javascript
// resources/js/utils/apiError.js
// 第2章で作成したコードをコピー
```

### Step 3：useApiError.js を実装

```javascript
// resources/js/composables/useApiError.js
// 第2章で作成したコードをコピー
```

### Step 4：.env に環境変数を追加

```bash
# .env
VITE_APP_DEBUG=true  # 開発環境

# .env.production（本番用）
VITE_APP_DEBUG=false  # 本番環境
```

### Step 5：コンポーネントを修正

```javascript
// ❌ Before
const error = ref(null);
const memberError = ref(null);

catch (err) {
    console.error("Failed to create task:", err);
    error.value = err.response?.data?.message || "タスクの作成に失敗しました";
}
```

```javascript
// ✅ After
import { useApiError } from "@/composables/useApiError";

const { error, validationErrors, requestId, handleError, clearError } = useApiError();

catch (err) {
    handleError(err, "タスクの作成に失敗しました");
}
```

---

### 📝 修正チェックリスト

```
┌─────────────────────────────────────────────────────────────┐
│                    修正チェックリスト                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  □ useApiError を import したか                             │
│  □ 自前の error = ref(null) を削除したか                    │
│  □ handleError, clearError を取得したか                     │
│  □ try の最初で clearError() を呼んでいるか                 │
│  □ catch 内で handleError() を呼んでいるか                  │
│  □ console.error を直接書いていないか ← 🚨重要！            │
│  □ err.response?.data?.message を直接書いていないか         │
│  □ .env に VITE_APP_DEBUG を設定したか                      │
│  □ テンプレートの error 表示は動作するか                    │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 📖 第 6 章：まとめ

### 📊 バックエンドとフロントエンドの連携図

```
┌─────────────────────────────────────────────────────────────┐
│                    エラー処理の流れ                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【バックエンド】                                            │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ UseCase                                              │   │
│  │  └→ throw ConflictException("完了済みは編集不可")   │   │
│  └───────────────────────────┬─────────────────────────┘   │
│                              ▼                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ ApiExceptionHandler                                  │   │
│  │  └→ {                                               │   │
│  │       "success": false,                             │   │
│  │       "message": "完了済みは編集不可",              │   │
│  │       "request_id": "req_xxx"                       │   │
│  │     } + 409                                         │   │
│  └───────────────────────────┬─────────────────────────┘   │
│                              ▼                              │
│  ════════════════════ HTTP レスポンス ════════════════════  │
│                              ▼                              │
│  【フロントエンド】                                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ axios catch                                          │   │
│  │  └→ handleError(err, "編集に失敗しました")          │   │
│  └───────────────────────────┬─────────────────────────┘   │
│                              ▼                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ useApiError                                          │   │
│  │  ├→ error.value = "完了済みは編集不可"              │   │
│  │  ├→ requestId.value = "req_xxx"                     │   │
│  │  └→ （バックエンドのメッセージをそのまま使用）      │   │
│  └───────────────────────────┬─────────────────────────┘   │
│                              ▼                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ テンプレート                                          │   │
│  │  └→ <p>{{ error }}</p>                              │   │
│  │  └→ ユーザーに「完了済みは編集不可」と表示          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 🐘 ガネーシャの教え

```
┌─────────────────────────────────────────────────────────────┐
│                    ガネーシャの教え                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1️⃣ バックエンドを信頼する                                  │
│     → バックエンドが返すメッセージをそのまま表示            │
│     → フロントエンドは余計な加工をしない                    │
│                                                             │
│  2️⃣ 環境に応じて動作を変える                                │
│     → 開発環境：詳細ログを出力                              │
│     → 本番環境：ログは出さない（セキュリティ！）            │
│                                                             │
│  3️⃣ 一貫性を保つ（DRY原則）                                 │
│     → 同じコードを何度も書かない                            │
│     → 共通の仕組みを作って使い回す                          │
│                                                             │
│  4️⃣ バックエンドとフロントエンドは車の両輪                  │
│     → 片方だけ良くてもダメ                                  │
│     → 両方を統一して初めて完成                              │
│                                                             │
│  5️⃣ request_id を活用する                                   │
│     → サポート問い合わせ時のログ追跡に便利                  │
│     → 本番環境でのデバッグの強い味方                        │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**👩‍💻 ユーザー：** 「ガネーシャさん、ありがとうございました！バックエンドだけじゃダメってこと、よく分かりました！」

**🐘 ガネーシャ：** 「せやせや。これでエラーハンドリングシリーズは完結や！Lesson 6 から Lesson 12 まで、よう頑張ったな！さすガネーシャの教え子や！🐘✨」

---

## 📚 シリーズ完結：エラーハンドリング全体像

| Lesson | タイトル                     | 内容                             |
| ------ | ---------------------------- | -------------------------------- |
| 6-1    | ステータスコードとは         | 200, 404, 409, 500 の意味        |
| 6-2    | Laravel が自動でやること     | Route Model Binding, FormRequest |
| 6-3    | 自分で書くエラーハンドリング | 403, 409 を返す場面              |
| 7      | try-catch の基本             | try-catch の概念                 |
| 8      | UseCase での throw           | Controller から UseCase へ       |
| 9      | トランザクション             | DB の整合性を保つ                |
| 10     | 例外処理の全体設計           | ApiExceptionHandler              |
| 11     | カスタム例外                 | ConflictException                |
| **12** | **フロントエンドの統一**     | **useApiError**                  |

**🐘 ガネーシャ：** 「バックエンドからフロントエンドまで、エラーハンドリングが一本の線で繋がったな！」

**👩‍💻 ユーザー：** 「全体像が見えてきました！両方統一することが大事なんですね！」

**🐘 ガネーシャ：** 「ほな、また会おうな！あんみつ食べたいな〜 🍨」

**👩‍💻 ユーザー：** 「最後までそれかい！」

**🐘 ガネーシャ：** 「はい、Oh, My God!!」

---

```
       🐘
      /||\
     / || \
    🍨    🍨

エラーハンドリングシリーズ完結！
ガネーシャより愛を込めて
```
