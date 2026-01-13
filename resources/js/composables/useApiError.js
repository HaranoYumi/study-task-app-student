import { ref } from "vue";
import { extractErrorMessage, extractValidationErrors } from "@/utils/apiError";

/**
 * Lesson6-2用：シンプルなエラーハンドリング（Before版）
 * Laravelのデフォルトエラーハンドリングを学ぶための教材コード
 *
 * ❌ 冗長：複雑なロジックを使わず、シンプルにエラーを処理
 */
export function useApiError() {
    const error = ref(null);
    const validationErrors = ref({});

    /**
     * エラーを処理する
     */
    const handleError = (err, defaultMessage = "エラーが発生しました") => {
        // ❌ 冗長：常にコンソールに出力（本来は開発環境のみにすべき）
        console.error("API Error:", err);

        // エラーメッセージを取得
        error.value = extractErrorMessage(err, defaultMessage);

        // バリデーションエラーを取得
        const validation = extractValidationErrors(err);
        if (validation) {
            validationErrors.value = validation;
        }
    };

    /**
     * エラーをクリアする
     */
    const clearError = () => {
        error.value = null;
        validationErrors.value = {};
    };

    return {
        error,
        validationErrors,
        handleError,
        clearError,
    };
}
