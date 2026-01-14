<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ApiError from "@/Components/ApiError.vue";
import Pagination from "@/Components/Pagination.vue";
import { useApiError } from "@/composables/useApiError";

const router = useRouter();
const users = ref([]);
const pagination = ref({});
const loading = ref(true);
const searchQuery = ref("");
const sortBy = ref("name");
const currentPage = ref(1);
const perPage = ref(15);

// エラーハンドリング用のComposable
const { error, requestId, statusCode, handleError, clearError } = useApiError();

// デバウンス用タイマー
let searchTimeout = null;

const fetchUsers = async (page = 1) => {
  try {
    loading.value = true;
    clearError();
    
    const params = {
      page: page,
      per_page: perPage.value,
      sort_by: sortBy.value === "newest" ? "created_at" : sortBy.value === "oldest" ? "created_at" : sortBy.value,
      sort_order: sortBy.value === "newest" ? "desc" : "asc",
    };
    
    if (searchQuery.value) {
      params.search = searchQuery.value;
    }
    
    const response = await axios.get("/api/users", { params });
    users.value = response.data.data;
    pagination.value = response.data.meta;
    currentPage.value = page;
  } catch (err) {
    handleError(err, "ユーザーの読み込みに失敗しました");
  } finally {
    loading.value = false;
  }
};

const handlePageChange = (page) => {
  fetchUsers(page);
};

const clearFilters = () => {
  searchQuery.value = "";
  sortBy.value = "name";
  fetchUsers(1);
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

// 検索のデバウンス
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchUsers(1);
  }, 500);
});

// ソート変更時
watch(sortBy, () => {
  fetchUsers(1);
});

onMounted(() => {
  fetchUsers();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-12 px-4">
      <div class="max-w-7xl mx-auto">
        <!-- ヘッダーセクション -->
        <div class="mb-8">
          <div>
            <h1
              class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-3"
            >
              ユーザー一覧
            </h1>
            <p class="text-slate-600 text-lg">
              システムに登録されているユーザーを閲覧できます
            </p>
          </div>
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
                  placeholder="名前またはメールアドレスで検索..."
                  class="w-full pl-10 pr-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
                />
              </div>
            </div>

            <!-- ソート -->
            <div class="flex gap-3">
              <select
                v-model="sortBy"
                class="px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
              >
                <option value="name">名前順</option>
                <option value="email">メールアドレス順</option>
                <option value="newest">新しい順</option>
                <option value="oldest">古い順</option>
              </select>
            </div>
          </div>

          <!-- 検索結果数表示 -->
          <div v-if="searchQuery" class="mt-4 flex items-center justify-between">
            <p class="text-sm text-slate-600">
              <span class="font-semibold text-blue-600">{{
                pagination.total || 0
              }}</span>
              件のユーザーが見つかりました
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
          fallback-message="ユーザーの読み込みに失敗しました"
        />

        <!-- ユーザー一覧テーブル -->
        <div
          v-else-if="users.length > 0"
          class="backdrop-blur-lg bg-white/70 rounded-2xl shadow-xl border border-white/50 overflow-hidden"
        >
          <div class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr
                  class="bg-gradient-to-r from-slate-50/80 to-blue-50/80 border-b border-slate-200/50"
                >
                  <th
                    class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider"
                  >
                    ユーザー
                  </th>
                  <th
                    class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider"
                  >
                    メールアドレス
                  </th>
                  <th
                    class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider"
                  >
                    登録日
                  </th>
                  <th
                    class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider"
                  >
                    認証状態
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200/50">
                <tr
                  v-for="user in users"
                  :key="user.id"
                  class="hover:bg-blue-50/30 transition-colors"
                >
                  <!-- ユーザー情報 -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                      <div
                        class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold"
                      >
                        {{ user.name?.charAt(0) || "?" }}
                      </div>
                      <div>
                        <div class="text-sm font-semibold text-slate-800">
                          {{ user.name }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- メールアドレス -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                    {{ user.email }}
                  </td>

                  <!-- 登録日 -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                    {{ formatDate(user.created_at) }}
                  </td>

                  <!-- 認証状態 -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      v-if="user.email_verified_at"
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold backdrop-blur-lg bg-emerald-500/20 text-emerald-700 border border-emerald-300/50"
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
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                      </svg>
                      認証済み
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold backdrop-blur-lg bg-amber-500/20 text-amber-700 border border-amber-300/50"
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
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                      </svg>
                      未認証
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ページネーション -->
          <Pagination
            v-if="pagination && pagination.last_page"
            :pagination="pagination"
            @page-changed="handlePageChange"
          />
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
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">
              {{ searchQuery || pagination.total === 0 ? "該当するユーザーがありません" : "ユーザーがありません" }}
            </h3>
            <p class="text-slate-600">
              {{ searchQuery ? "検索条件を変更してください" : "まだユーザーが登録されていません" }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

