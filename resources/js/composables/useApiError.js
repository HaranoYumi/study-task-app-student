import { ref } from "vue";
import { extractErrorMessage, extractValidationErrors } from "@/utils/apiError";

/**
 * APIエラーハンドリング用のComposable
 *
 * エラーメッセージとバリデーションエラーの状態管理を提供します。
 *
 * 【基本方針】
 * バックエンドが環境に応じて適切なメッセージを返すため、
 * フロントエンドは基本的にそれを信頼して表示します。
 * デフォルトメッセージは「万が一」のフォールバックです。
 *
 * @returns {Object} エラーハンドリング用のオブジェクト
 *
 * @example
 * const { error, validationErrors, handleError, clearError } = useApiError();
 *
 * try {
 *   await axios.get('/api/tasks/1');
 * } catch (err) {
 *   // 第2引数はフォールバック。ほとんどの場合、バックエンドのメッセージが使われる
 *   handleError(err, 'タスクの読み込みに失敗しました');
 * }
 */
export function useApiError() {
    const error = ref(null);
    const validationErrors = ref({});
    const requestId = ref(null);
    const statusCode = ref(null);

    /**
     * エラーを処理する
     *
     * @param {Error} err - Axiosエラーオブジェクト
     * @param {string} defaultMessage - デフォルトメッセージ
     */
    const handleError = (err, defaultMessage = "エラーが発生しました") => {
        // .env の APP_DEBUG が true の場合のみコンソールに詳細ログを出力
        // これにより、.env の APP_DEBUG で手動に切り替えて挙動を確認できる
        if (import.meta.env.APP_DEBUG) {
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
        // APP_DEBUG=false の場合はコンソールに何も出力しない（Sentryなどのエラー監視ツールで管理）

        // エラーメッセージを抽出（画面表示用 - 常に固定メッセージ）
        error.value = extractErrorMessage(err, defaultMessage);

        // リクエストIDとステータスコードを取得
        if (err.response?.data) {
            requestId.value = err.response.data.request_id || null;
            statusCode.value = err.response.status || null;
        } else {
            requestId.value = null;
            statusCode.value = null;
        }

        // バリデーションエラーを抽出
        const validation = extractValidationErrors(err);
        if (validation) {
            validationErrors.value = validation;
        } else {
            validationErrors.value = {};
        }
    };

    /**
     * エラーをクリアする
     */
    const clearError = () => {
        error.value = null;
        validationErrors.value = {};
        requestId.value = null;
        statusCode.value = null;
    };

    /**
     * エラーメッセージのみを設定する（手動でエラーを設定したい場合）
     *
     * @param {string} message - エラーメッセージ
     */
    const setError = (message) => {
        error.value = message;
    };

    return {
        error,
        validationErrors,
        requestId,
        statusCode,
        handleError,
        clearError,
        setError,
    };
}
