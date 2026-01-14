<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ApiError from "@/Components/ApiError.vue";
import { useApiError } from "@/composables/useApiError";

const route = useRoute();
const router = useRouter();
const projectId = route.params.id;

const project = ref(null);
const loading = ref(true);
const updating = ref(false);
const deleting = ref(false);

// エラーハンドリング用のComposable
const { error, validationErrors, requestId, statusCode, handleError, clearError } = useApiError();

const form = ref({
  name: "",
  is_archived: false,
});

const fetchProject = async () => {
  try {
    loading.value = true;
    clearError();
    const response = await axios.get(`/api/projects/${projectId}`);
    // ProjectResourceは直接dataを返すか、dataプロパティを持つ
    project.value = response.data.data || response.data;

    // フォームに現在の値を設定
    form.value.name = project.value.name;
    form.value.is_archived = project.value.is_archived;
  } catch (err) {
    handleError(err, "プロジェクトの読み込みに失敗しました");
  } finally {
    loading.value = false;
  }
};

const updateProject = async () => {
  try {
    updating.value = true;
    clearError();

    await axios.put(`/api/projects/${projectId}`, form.value);

    // 更新成功後、プロジェクト詳細へ戻る
    router.push({ name: "project.detail", params: { id: projectId } });
  } catch (err) {
    handleError(err, "プロジェクトの更新に失敗しました");
  } finally {
    updating.value = false;
  }
};

const confirmDelete = () => {
  if (
    confirm(
      `本当に「${project.value.name}」を削除しますか？\n\nこの操作は取り消すことができません。\n全てのタスクとメンバーシップも削除されます。`
    )
  ) {
    deleteProject();
  }
};

const deleteProject = async () => {
  try {
    deleting.value = true;
    clearError();

    await axios.delete(`/api/projects/${projectId}`);

    // 削除成功後、プロジェクト一覧へ
    router.push({ name: "projects" });
  } catch (err) {
    handleError(err, "プロジェクトの削除に失敗しました");
    deleting.value = false;
  }
};

onMounted(() => {
  fetchProject();
});
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
          <router-link
            v-if="project"
            :to="{ name: 'project.detail', params: { id: projectId } }"
            class="text-slate-600 hover:text-blue-600 transition-colors"
          >
            {{ project.name }}
          </router-link>
          <span v-else class="text-slate-400">読み込み中...</span>
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
          <span class="text-slate-800 font-medium">編集</span>
        </nav>

        <!-- ローディング -->
        <div v-if="loading" class="flex items-center justify-center py-20">
          <div class="relative">
            <div
              class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"
            ></div>
            <p class="mt-4 text-slate-500 text-sm">読み込み中...</p>
          </div>
        </div>

        <template v-else-if="project">
          <!-- ヘッダー -->
          <div class="mb-8">
            <h1
              class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-2"
            >
              プロジェクトを編集
            </h1>
            <p class="text-slate-600">プロジェクト情報を更新してください</p>
          </div>

          <!-- エラー表示 -->
          <ApiError
            v-if="error"
            :message="error"
            :request-id="requestId"
            :status-code="statusCode"
            fallback-message="プロジェクトの更新に失敗しました"
            class="mb-6"
          />

          <!-- フォーム -->
          <div
            class="backdrop-blur-lg bg-white/70 rounded-2xl shadow-xl border border-white/50 p-8"
          >
            <form @submit.prevent="updateProject" class="space-y-6">
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
                  placeholder="例:ECサイトリニューアル"
                />
                <p
                  v-if="validationErrors.name"
                  class="mt-2 text-sm text-red-600"
                >
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
                  アーカイブ済み
                </label>
              </div>

              <!-- ボタン -->
              <div class="flex items-center justify-between pt-4">
                <div class="flex items-center gap-4">
                  <button
                    type="submit"
                    :disabled="updating"
                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
                  >
                    <svg
                      v-if="!updating"
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                      />
                    </svg>
                    <div
                      v-else
                      class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"
                    ></div>
                    {{ updating ? "更新中..." : "更新" }}
                  </button>

                  <button
                    type="button"
                    @click="
                      $router.push({
                        name: 'project.detail',
                        params: { id: projectId },
                      })
                    "
                    class="px-6 py-3 backdrop-blur-lg bg-white/80 text-slate-700 font-semibold rounded-xl hover:bg-white transition-all duration-300 border border-slate-200"
                  >
                    キャンセル
                  </button>
                </div>

                <!-- 削除ボタン -->
                <button
                  type="button"
                  @click="confirmDelete"
                  :disabled="deleting"
                  class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-400 to-rose-500 text-white font-semibold rounded-xl hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300"
                >
                  <svg
                    v-if="!deleting"
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                  <div
                    v-else
                    class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"
                  ></div>
                  {{ deleting ? "削除中..." : "削除" }}
                </button>
              </div>
            </form>
          </div>

          <!-- 警告 -->
          <div
            class="mt-6 backdrop-blur-lg bg-amber-500/10 border border-amber-300/30 rounded-2xl p-6"
          >
            <div class="flex gap-3">
              <svg
                class="w-6 h-6 text-amber-600 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
              </svg>
              <div class="text-sm text-slate-700">
                <p class="font-semibold mb-2">⚠️ プロジェクト削除について</p>
                <ul class="space-y-1 list-disc list-inside">
                  <li>
                    プロジェクトを削除すると、全てのタスクとメンバーシップも削除されます
                  </li>
                  <li>この操作は取り消すことができません</li>
                  <li>削除はオーナーのみ実行できます</li>
                </ul>
              </div>
            </div>
          </div>
        </template>

        <!-- エラー（プロジェクトが見つからない、またはその他のエラー） -->
        <ApiError
          v-else
          :message="error"
          :request-id="requestId"
          :status-code="statusCode"
          fallback-message="プロジェクトが見つかりませんでした"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>
