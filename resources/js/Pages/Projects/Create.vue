<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ApiError from "@/Components/ApiError.vue";
import { useApiError } from "@/composables/useApiError";

const router = useRouter();
const toast = useToast();

const form = ref({
  name: "",
  is_archived: false,
});

const creating = ref(false);

// エラーハンドリング用のComposable
const { error, validationErrors, requestId, statusCode, handleError, clearError } = useApiError();

const createProject = async () => {
  try {
    creating.value = true;
    clearError();

    const response = await axios.post("/api/projects", form.value);

    toast.success(response.data.message || "プロジェクトを作成しました");

    // トーストを表示させてからページ遷移
    setTimeout(() => {
      router.push({
        name: "project.detail",
        params: { id: response.data.data.id },
      });
    }, 500);
  } catch (err) {
    handleError(err, "プロジェクトの作成に失敗しました");
    toast.error(error.value);
  } finally {
    creating.value = false;
  }
};
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-12 px-4">
      <div class="max-w-3xl mx-auto">
        <!-- パンくずリスト -->
        <nav class="mb-8 flex items-center gap-2 text-sm">
          <router-link
            :to="{ name: 'projects' }"
            class="flex items-center gap-1 text-slate-600 hover:text-blue-600 transition-colors"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
              />
            </svg>
            プロジェクト一覧
          </router-link>
          <svg
            class="w-4 h-4 text-slate-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
          <span class="text-slate-800 font-medium">新規作成</span>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
          <h1
            class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-2"
          >
            新しいプロジェクトを作成
          </h1>
          <p class="text-slate-600">プロジェクト情報を入力してください</p>
        </div>

        <!-- エラー表示 -->
        <ApiError
          v-if="error"
          :message="error"
          :request-id="requestId"
          :status-code="statusCode"
          fallback-message="プロジェクトの作成に失敗しました"
          class="mb-6"
        />

        <!-- フォーム -->
        <div
          class="backdrop-blur-lg bg-white/70 rounded-2xl shadow-xl border border-white/50 p-8"
        >
          <form @submit.prevent="createProject" class="space-y-6">
            <!-- プロジェクト名 -->
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">
                プロジェクト名 <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
                placeholder="例：ECサイトリニューアル"
              />
              <p v-if="validationErrors.name" class="mt-2 text-sm text-red-600">
                {{ validationErrors.name[0] }}
              </p>
            </div>

            <!-- アーカイブ -->
            <div class="flex items-center gap-3">
              <input
                v-model="form.is_archived"
                type="checkbox"
                id="is_archived"
                class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500 focus:ring-2"
              />
              <label
                for="is_archived"
                class="text-sm font-medium text-slate-700"
              >
                アーカイブ済みとして作成
              </label>
            </div>

            <!-- ボタン -->
            <div class="flex items-center gap-4 pt-4">
              <button
                type="submit"
                :disabled="creating"
                class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
              >
                <svg
                  v-if="!creating"
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                <div
                  v-else
                  class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"
                ></div>
                {{ creating ? "作成中..." : "プロジェクトを作成" }}
              </button>

              <button
                type="button"
                @click="$router.push({ name: 'projects' })"
                class="px-6 py-3 backdrop-blur-lg bg-white/80 text-slate-700 font-semibold rounded-xl hover:bg-white transition-all duration-300 border border-slate-200"
              >
                キャンセル
              </button>
            </div>
          </form>
        </div>

        <!-- ヒント -->
        <div
          class="mt-6 backdrop-blur-lg bg-blue-500/10 border border-blue-300/30 rounded-2xl p-6"
        >
          <div class="flex gap-3">
            <svg
              class="w-6 h-6 text-blue-600 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <div class="text-sm text-slate-700">
              <p class="font-semibold mb-2">プロジェクト作成後について</p>
              <ul class="space-y-1 list-disc list-inside">
                <li>作成者は自動的にオーナーとして登録されます</li>
                <li>プロジェクト詳細画面からタスクを作成できます</li>
                <li>メンバーを追加するには別途機能が必要です</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
