/**
 * Lesson6-2用：シンプルなエラーハンドリング（Before版）
 * Laravelのデフォルトエラーハンドリングを学ぶための教材コード
 *
 * ❌ 冗長：複雑な条件分岐を使わず、シンプルにエラーメッセージを取得
 */

/**
 * APIエラーからメッセージを抽出する
 */
export function extractErrorMessage(
    error,
    defaultMessage = "エラーが発生しました"
) {
    // サーバーからレスポンスがない場合
    if (!error.response) {
        return "サーバーに接続できませんでした";
    }

    // バックエンドからのメッセージをそのまま表示
    return error.response.data?.message || defaultMessage;
}

/**
 * バリデーションエラーを抽出する
 */
export function extractValidationErrors(error) {
    return error.response?.data?.errors || null;
}
