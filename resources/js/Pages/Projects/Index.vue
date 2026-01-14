<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ApiError from "@/Components/ApiError.vue";
import { useApiError } from "@/composables/useApiError";

const router = useRouter();
const projects = ref([]);
const loading = ref(true);
const searchQuery = ref("");
const filterStatus = ref("all");
const sortBy = ref("name");

// エラーハンドリング用のComposable
const { error, requestId, statusCode, handleError, clearError } = useApiError();

const fetchProjects = async () => {
  try {
    loading.value = true;
    clearError();
    const response = await axios.get("/api/projects");
    projects.value = response.data.data;
  } catch (err) {
    handleError(err, "プロジェクトの読み込みに失敗しました");
  } finally {
    loading.value = false;
  }
};

const filteredProjects = computed(() => {
  let result = [...projects.value];

  // 検索フィルター
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter((project) =>
      project.name.toLowerCase().includes(query)
    );
  }

  // ステータスフィルター
  if (filterStatus.value !== "all") {
    result = result.filter((project) => {
      if (filterStatus.value === "active") return !project.is_archived;
      if (filterStatus.value === "archived") return project.is_archived;
      return true;
    });
  }

  // ソート
  result.sort((a, b) => {
    switch (sortBy.value) {
      case "name":
        return a.name.localeCompare(b.name, "ja");
      case "newest":
        return new Date(b.created_at) - new Date(a.created_at);
      case "oldest":
        return new Date(a.created_at) - new Date(b.created_at);
      case "members":
        return (b.memberships?.length || 0) - (a.memberships?.length || 0);
      default:
        return 0;
    }
  });

  return result;
});

const clearFilters = () => {
  searchQuery.value = "";
  filterStatus.value = "all";
  sortBy.value = "name";
};

const formatDate = (dateString) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  return date.toLocaleDateString("ja-JP", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

const goToProject = (projectId) => {
  router.push(`/projects/${projectId}`);
};

onMounted(() => {
  fetchProjects();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-12 px-4">
      <div class="max-w-7xl mx-auto">
        <!-- ヘッダーセクション -->
        <div class="mb-8 flex items-start justify-between gap-4">
          <div>
            <h1
              class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-3"
            >
              プロジェクト一覧
            </h1>
            <p class="text-slate-600 text-lg">
              参加中のプロジェクトを管理・閲覧できます
            </p>
          </div>

          <!-- 新規作成ボタン -->
          <router-link
            :to="{ name: 'project.create' }"
            class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300 flex-shrink-0"
          >
            <svg
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
            新規作成
          </router-link>
        </div>

        <!-- 検索＆フィルターセクション -->
        <div
          class="mb-8 backdrop-blur-lg bg-white/70 rounded-2xl shadow-lg border border-white/50 p-6"
        >
          <div class="flex flex-col sm:flex-row gap-4">
            <!-- 検索バー -->
            <div class="flex-1">
              <div class="relative">
                <div
                  class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                >
                  <svg
                    class="w-5 h-5 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                  </svg>
                </div>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="プロジェクト名で検索..."
                  class="w-full pl-10 pr-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
                />
              </div>
            </div>

            <!-- フィルター -->
            <div class="flex gap-3">
              <select
                v-model="filterStatus"
                class="px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
              >
                <option value="all">全て</option>
                <option value="active">アクティブ</option>
                <option value="archived">アーカイブ済み</option>
              </select>

              <select
                v-model="sortBy"
                class="px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
              >
                <option value="name">名前順</option>
                <option value="newest">新しい順</option>
                <option value="oldest">古い順</option>
                <option value="members">メンバー数</option>
              </select>
            </div>
          </div>

          <!-- 検索結果数表示 -->
          <div
            v-if="searchQuery || filterStatus !== 'all'"
            class="mt-4 flex items-center justify-between"
          >
            <p class="text-sm text-slate-600">
              <span class="font-semibold text-blue-600">{{
                filteredProjects.length
              }}</span>
              件のプロジェクトが見つかりました
            </p>
            <button
              @click="clearFilters"
              class="text-sm text-slate-600 hover:text-blue-600 transition-colors flex items-center gap-1"
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
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              フィルターをクリア
            </button>
          </div>
        </div>

        <!-- ローディング -->
        <div v-if="loading" class="flex items-center justify-center py-20">
          <div class="relative">
            <div
              class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"
            ></div>
            <p class="mt-4 text-slate-500 text-sm">読み込み中...</p>
          </div>
        </div>

        <!-- エラー -->
        <ApiError
          v-if="error"
          :message="error"
          :request-id="requestId"
          :status-code="statusCode"
          fallback-message="プロジェクトの読み込みに失敗しました"
        />

        <!-- プロジェクト一覧 -->
        <div
          v-else-if="filteredProjects.length > 0"
          class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
          <div
            v-for="project in filteredProjects"
            :key="project.id"
            @click="goToProject(project.id)"
            class="group relative backdrop-blur-lg bg-white/70 hover:bg-white/90 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 cursor-pointer overflow-hidden border border-white/50"
          >
            <!-- グラデーション装飾 -->
            <div
              class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-indigo-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            ></div>

            <div class="relative p-6">
              <!-- アーカイブバッジ -->
              <div v-if="project.is_archived" class="absolute top-4 right-4">
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium backdrop-blur-lg bg-slate-500/20 text-slate-700 border border-slate-300/30"
                >
                  <svg
                    class="w-3 h-3 mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"
                    />
                  </svg>
                  アーカイブ済み
                </span>
              </div>

              <!-- プロジェクト名 -->
              <h2
                class="text-xl font-bold text-slate-800 mb-4 pr-20 group-hover:text-blue-600 transition-colors duration-300"
              >
                {{ project.name }}
              </h2>

              <!-- メンバー数 -->
              <div class="flex items-center gap-2 text-slate-600 mb-4">
                <div
                  class="p-2 rounded-lg backdrop-blur-lg bg-blue-500/10 border border-blue-300/30"
                >
                  <svg
                    class="w-5 h-5 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                  </svg>
                </div>
                <span class="font-medium"
                  >{{ project.users?.length || 0 }} 人のメンバー</span
                >
              </div>

              <!-- 作成日 -->
              <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
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
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                <span>{{ formatDate(project.created_at) }}</span>
              </div>

              <!-- 矢印アイコン -->
              <div class="flex items-center justify-end">
                <div
                  class="p-2 rounded-full backdrop-blur-lg bg-blue-500/0 group-hover:bg-blue-500/20 border border-blue-300/0 group-hover:border-blue-300/50 transition-all duration-300"
                >
                  <svg
                    class="w-5 h-5 text-blue-600 transform group-hover:translate-x-1 transition-transform duration-300"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 7l5 5m0 0l-5 5m5-5H6"
                    />
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 空の状態 -->
        <div v-else class="text-center py-20">
          <div
            class="backdrop-blur-lg bg-white/60 rounded-3xl p-12 border border-white/50 shadow-xl max-w-md mx-auto"
          >
            <div
              class="w-24 h-24 mx-auto mb-6 rounded-full backdrop-blur-lg bg-slate-500/10 border border-slate-300/30 flex items-center justify-center"
            >
              <svg
                class="w-12 h-12 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">
              {{
                searchQuery || filterStatus !== "all"
                  ? "該当するプロジェクトがありません"
                  : "プロジェクトがありません"
              }}
            </h3>
            <p class="text-slate-600">
              {{
                searchQuery || filterStatus !== "all"
                  ? "検索条件を変更してください"
                  : "まだどのプロジェクトにも参加していません"
              }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
