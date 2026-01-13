/**
 * APIエラーからメッセージを抽出するユーティリティ関数
 *
 * 【基本方針】
 * バックエンドが環境に応じて適切なメッセージを返すため、
 * フロントエンドは基本的にそれを信頼して表示します。
 * デフォルトメッセージは「万が一」のフォールバックです。
 *
 * @param {Error} error - Axiosエラーオブジェクト
 * @param {string} defaultMessage - フォールバック用のデフォルトメッセージ（通常は使われない）
 * @returns {string} エラーメッセージ
 *
 * @example
 * try {
 *   await axios.get('/api/tasks/1');
 * } catch (err) {
 *   // 第2引数はフォールバック。ほとんどの場合、バックエンドのメッセージが使われる
 *   const message = extractErrorMessage(err, 'タスクの読み込みに失敗しました');
 *   console.error(message);
 * }
 */
export function extractErrorMessage(
    error,
    defaultMessage = "エラーが発生しました"
) {
    // 【ケース1】ネットワークエラー（responseがない場合）
    // インターネット接続切断、DNSエラー、タイムアウトなど
    if (!error.response) {
        // ネットワークエラーは明確なメッセージを返す
        if (error.message && error.message.toLowerCase().includes("network")) {
            return "ネットワークエラーが発生しました。インターネット接続を確認してください。";
        }
        return error.message || "サーバーに接続できませんでした。";
    }

    const response = error.response;
    const data = response.data;

    // 【ケース2】バックエンドからメッセージがある（通常のケース）
    // - 技術的エラー（500）: バックエンドが環境に応じて返す（本番: "サーバーエラー", 開発: 詳細）
    // - ビジネスロジックエラー（403, 409など）: バックエンドが返す
    // - バリデーションエラー（422）: バックエンドが返す
    if (data?.message) {
        return data.message;
    }

    // 【ケース3】バリデーションエラー（errorsプロパティがある場合）
    if (data?.errors) {
        // 最初のエラーメッセージを返す
        const firstError = Object.values(data.errors)[0];
        if (Array.isArray(firstError) && firstError.length > 0) {
            return firstError[0];
        }
        if (typeof firstError === "string") {
            return firstError;
        }
    }

    // 【ケース4】HTTPステータスコードに基づくフォールバック
    // 通常は到達しないが、バックエンドがメッセージを返さなかった場合の安全策
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
 *
 * @param {Error} error - Axiosエラーオブジェクト
 * @returns {Object|null} バリデーションエラーオブジェクト、またはnull
 *
 * @example
 * try {
 *   await axios.post('/api/projects', form);
 * } catch (err) {
 *   const validationErrors = extractValidationErrors(err);
 *   if (validationErrors) {
 *     // バリデーションエラーを処理
 *   }
 * }
 */
export function extractValidationErrors(error) {
    if (!error.response?.data?.errors) {
        return null;
    }

    return error.response.data.errors;
}
