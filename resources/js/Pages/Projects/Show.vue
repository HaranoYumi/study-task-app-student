<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "vue-toastification";
import axios from "axios";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ApiError from "@/Components/ApiError.vue";
import { useApiError } from "@/composables/useApiError";

const toast = useToast();

const route = useRoute();
const router = useRouter();
const projectId = route.params.id;

const project = ref(null);
const tasks = ref([]);
const members = ref([]);
const loading = ref(true);
const activeTab = ref("tasks");
const creatingTask = ref(false);

// エラーハンドリング用のComposable
const { error, requestId, statusCode, handleError, clearError } = useApiError();
const memberError = ref(null);

// メンバー追加用の状態
const addingMember = ref(false);
const showAddMemberForm = ref(false);
const allUsers = ref([]);
const newMember = ref({
  user_id: "",
  role: "project_member",
});

const taskSearchQuery = ref("");
const taskStatusFilter = ref("all");
const taskSortBy = ref("newest");

const newTask = ref({
  title: "",
  description: "",
});

const canDeleteMember = computed(() => {
  return true;
});

// メンバー追加可能なユーザー（既存メンバーを除外）
const availableUsers = computed(() => {
  const memberUserIds = members.value.map((m) => m.id || m.user_id);
  return allUsers.value.filter((user) => !memberUserIds.includes(user.id));
});

const filteredTasks = computed(() => {
  let result = [...tasks.value];

  // 検索フィルター
  if (taskSearchQuery.value) {
    const query = taskSearchQuery.value.toLowerCase();
    result = result.filter(
      (task) =>
        task.title.toLowerCase().includes(query) ||
        (task.description && task.description.toLowerCase().includes(query))
    );
  }

  // ステータスフィルター
  if (taskStatusFilter.value !== "all") {
    result = result.filter((task) => task.status === taskStatusFilter.value);
  }

  // ソート
  result.sort((a, b) => {
    switch (taskSortBy.value) {
      case "newest":
        return new Date(b.created_at) - new Date(a.created_at);
      case "oldest":
        return new Date(a.created_at) - new Date(b.created_at);
      case "title":
        return a.title.localeCompare(b.title, "ja");
      default:
        return 0;
    }
  });

  return result;
});

const clearTaskFilters = () => {
  taskSearchQuery.value = "";
  taskStatusFilter.value = "all";
};

const formatDate = (dateString) => {
  if (!dateString) return "";
  const date = new Date(dateString);
  const now = new Date();
  const diff = now - date;
  const days = Math.floor(diff / (1000 * 60 * 60 * 24));

  if (days === 0) return "今日";
  if (days === 1) return "昨日";
  if (days < 7) return `${days}日前`;

  return date.toLocaleDateString("ja-JP", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
};

const fetchProject = async () => {
  try {
    loading.value = true;
    clearError();
    const response = await axios.get(`/api/projects/${projectId}`);
    project.value = response.data.data;
    tasks.value = response.data.data.tasks || [];
    members.value = response.data.data.users || [];
  } catch (err) {
    handleError(err, "プロジェクトの読み込みに失敗しました");
  } finally {
    loading.value = false;
  }
};

const fetchUsers = async () => {
  try {
    const response = await axios.get("/api/users/dropdown");
    allUsers.value = response.data.data || [];
  } catch (err) {
    handleError(err, "ユーザー一覧の読み込みに失敗しました");
  }
};

const createTask = async () => {
  try {
    creatingTask.value = true;
    const response = await axios.post(
      `/api/projects/${projectId}/tasks`,
      newTask.value
    );
    tasks.value.unshift(response.data.data);
    newTask.value = { title: "", description: "" };
    toast.success(response.data.message || "タスクを作成しました");
  } catch (err) {
    handleError(err, "タスクの作成に失敗しました");
    toast.error(error.value);
  } finally {
    creatingTask.value = false;
  }
};

const startTask = async (taskId) => {
  try {
    const response = await axios.post(`/api/tasks/${taskId}/start`);
    const index = tasks.value.findIndex((t) => t.id === taskId);
    if (index !== -1) {
      tasks.value[index] = response.data.data;
    }
    toast.success("タスクを開始しました");
  } catch (err) {
    handleError(err, "タスクの開始に失敗しました");
    toast.error(error.value);
  }
};

const completeTask = async (taskId) => {
  try {
    const response = await axios.post(`/api/tasks/${taskId}/complete`);
    const index = tasks.value.findIndex((t) => t.id === taskId);
    if (index !== -1) {
      tasks.value[index] = response.data.data;
    }
    toast.success("タスクを完了しました");
  } catch (err) {
    handleError(err, "タスクの完了に失敗しました");
    toast.error(error.value);
  }
};

const deleteMember = async (userId) => {
  if (!confirm("本当にこのメンバーを削除しますか？")) {
    return;
  }

  try {
    memberError.value = null;
    const response = await axios.delete(
      `/api/projects/${projectId}/members/${userId}`
    );
    members.value = members.value.filter((m) => (m.id || m.user_id) !== userId);
    toast.success(response.data.message || "メンバーを削除しました");
  } catch (err) {
    handleError(err, "メンバーの削除に失敗しました");
    memberError.value = error.value;
    toast.error(error.value);
  }
};

const addMember = async () => {
  if (!newMember.value.user_id) {
    toast.warning("ユーザーを選択してください");
    return;
  }

  try {
    addingMember.value = true;
    memberError.value = null;
    const response = await axios.post(
      `/api/projects/${projectId}/members`,
      newMember.value
    );
    // APIレスポンスからmembershipオブジェクトを取得し、User形式に変換
    const membership = response.data.membership;
    if (membership && membership.user) {
      members.value.push(membership.user);
    } else if (membership) {
      // フォールバック: membershipオブジェクトから直接ユーザー情報を構築
      members.value.push({
        id: membership.user_id,
        name: membership.user?.name,
        email: membership.user?.email,
        pivot: {
          role: membership.role,
        },
      });
    }
    newMember.value = { user_id: "", role: "project_member" };
    showAddMemberForm.value = false;
    toast.success(response.data.message || "メンバーを追加しました");
  } catch (err) {
    handleError(err, "メンバーの追加に失敗しました");
    memberError.value = error.value;
    toast.error(error.value);
  } finally {
    addingMember.value = false;
  }
};

const goToTask = (taskId, event) => {
  // ボタンのクリックイベントの場合は遷移しない
  if (event.target.closest("button")) {
    return;
  }
  router.push({ name: "task.detail", params: { id: taskId } });
};

onMounted(() => {
  fetchProject();
  fetchUsers();
});
</script>

<template>
  <AuthenticatedLayout>
    <div class="py-12 px-4">
      <div class="max-w-7xl mx-auto">
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
          <span class="text-slate-800 font-medium">{{
            project?.name || "読み込み中..."
          }}</span>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8 flex items-start justify-between gap-4">
          <div class="flex-1">
            <h1
              class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mb-2"
            >
              {{ project?.name || "プロジェクト" }}
            </h1>
            <div
              v-if="project"
              class="flex items-center gap-4 text-sm text-slate-600"
            >
              <div class="flex items-center gap-2">
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
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
                <span>{{ members.length }} 人のメンバー</span>
              </div>
              <div class="flex items-center gap-2">
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
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                  />
                </svg>
                <span>{{ tasks.length }} 件のタスク</span>
              </div>
            </div>
          </div>

          <!-- 編集ボタン -->
          <router-link
            v-if="project"
            :to="{ name: 'project.edit', params: { id: projectId } }"
            class="flex items-center gap-2 px-6 py-3 backdrop-blur-lg bg-white/80 hover:bg-white text-slate-700 font-semibold rounded-xl transition-all duration-300 border border-slate-200 hover:border-blue-400 flex-shrink-0"
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
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            編集
          </router-link>
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
          v-else-if="error"
          :message="error"
          :request-id="requestId"
          :status-code="statusCode"
          fallback-message="プロジェクトの読み込みに失敗しました"
        />

        <!-- タブメニュー -->
        <div v-else class="mb-8">
          <div
            class="backdrop-blur-lg bg-white/60 rounded-2xl shadow-lg border border-white/50 p-2 inline-flex gap-2"
          >
            <button
              @click="activeTab = 'tasks'"
              :class="[
                'px-6 py-3 rounded-xl font-medium transition-all duration-300',
                activeTab === 'tasks'
                  ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg'
                  : 'text-slate-600 hover:text-slate-800 hover:bg-white/50',
              ]"
            >
              <div class="flex items-center gap-2">
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
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                  />
                </svg>
                タスク
                <span class="px-2 py-0.5 rounded-full text-xs bg-white/20">{{
                  filteredTasks.length
                }}</span>
              </div>
            </button>
            <button
              @click="activeTab = 'members'"
              :class="[
                'px-6 py-3 rounded-xl font-medium transition-all duration-300',
                activeTab === 'members'
                  ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-lg'
                  : 'text-slate-600 hover:text-slate-800 hover:bg-white/50',
              ]"
            >
              <div class="flex items-center gap-2">
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
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
                メンバー
                <span class="px-2 py-0.5 rounded-full text-xs bg-white/20">{{
                  members.length
                }}</span>
              </div>
            </button>
          </div>

          <!-- タスクタブ -->
          <div v-show="activeTab === 'tasks'" class="mt-8 space-y-6">
            <!-- フィルター＆ソートセクション -->
            <div
              class="backdrop-blur-lg bg-white/70 rounded-2xl shadow-lg border border-white/50 p-6"
            >
              <div class="flex flex-col sm:flex-row gap-4">
                <!-- 検索 -->
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
                      v-model="taskSearchQuery"
                      type="text"
                      placeholder="タスクを検索..."
                      class="w-full pl-10 pr-4 py-2.5 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-sm"
                    />
                  </div>
                </div>

                <!-- ステータスフィルター -->
                <select
                  v-model="taskStatusFilter"
                  class="px-4 py-2.5 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-sm"
                >
                  <option value="all">全てのステータス</option>
                  <option value="todo">未着手</option>
                  <option value="doing">作業中</option>
                  <option value="done">完了</option>
                </select>

                <!-- ソート -->
                <select
                  v-model="taskSortBy"
                  class="px-4 py-2.5 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none text-sm"
                >
                  <option value="newest">新しい順</option>
                  <option value="oldest">古い順</option>
                  <option value="title">タイトル順</option>
                </select>
              </div>

              <!-- フィルター結果 -->
              <div
                v-if="taskSearchQuery || taskStatusFilter !== 'all'"
                class="mt-4 flex items-center justify-between"
              >
                <p class="text-sm text-slate-600">
                  <span class="font-semibold text-blue-600">{{
                    filteredTasks.length
                  }}</span>
                  件のタスクが見つかりました
                </p>
                <button
                  @click="clearTaskFilters"
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
                  クリア
                </button>
              </div>
            </div>

            <!-- タスク作成フォーム -->
            <div
              class="backdrop-blur-lg bg-white/70 rounded-2xl shadow-xl border border-white/50 p-8"
            >
              <div class="flex items-center gap-3 mb-6">
                <div
                  class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500"
                >
                  <svg
                    class="w-6 h-6 text-white"
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
                </div>
                <h2 class="text-2xl font-bold text-slate-800">
                  新しいタスクを作成
                </h2>
              </div>

              <form @submit.prevent="createTask" class="space-y-5">
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2"
                    >タスク名</label
                  >
                  <input
                    v-model="newTask.title"
                    type="text"
                    required
                    class="w-full px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
                    placeholder="タスク名を入力してください"
                  />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-700 mb-2"
                    >説明（任意）</label
                  >
                  <textarea
                    v-model="newTask.description"
                    rows="3"
                    class="w-full px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none resize-none"
                    placeholder="タスクの詳細を入力してください"
                  ></textarea>
                </div>
                <button
                  type="submit"
                  :disabled="creatingTask"
                  class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 flex items-center gap-2"
                >
                  <svg
                    v-if="!creatingTask"
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
                  {{ creatingTask ? "作成中..." : "タスクを作成" }}
                </button>
              </form>
            </div>

            <!-- タスク一覧 -->
            <div class="space-y-4">
              <div
                v-for="task in filteredTasks"
                :key="task.id"
                class="group backdrop-blur-lg bg-white/70 hover:bg-white/90 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-white/50 overflow-hidden cursor-pointer"
                @click="goToTask(task.id, $event)"
              >
                <div class="p-6">
                  <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                      <h3
                        class="text-lg font-bold text-slate-800 mb-2 group-hover:text-blue-600 transition-colors"
                      >
                        {{ task.title }}
                      </h3>
                      <p
                        v-if="task.description"
                        class="text-slate-600 mb-4 line-clamp-2"
                      >
                        {{ task.description }}
                      </p>

                      <div class="flex flex-wrap items-center gap-3">
                        <!-- 作成者 -->
                        <div
                          class="flex items-center gap-2 text-sm text-slate-600"
                        >
                          <div
                            class="w-6 h-6 rounded-lg bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white text-xs font-bold"
                          >
                            {{ task.created_by_user?.name?.charAt(0) || "?" }}
                          </div>
                          <span>{{
                            task.created_by_user?.name || "不明"
                          }}</span>
                        </div>

                        <!-- ステータスバッジ -->
                        <span
                          :class="[
                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold backdrop-blur-lg border',
                            task.status === 'todo'
                              ? 'bg-slate-500/20 text-slate-700 border-slate-300/50'
                              : task.status === 'doing'
                              ? 'bg-amber-500/20 text-amber-700 border-amber-300/50'
                              : 'bg-emerald-500/20 text-emerald-700 border-emerald-300/50',
                          ]"
                        >
                          <span
                            :class="[
                              'w-2 h-2 rounded-full',
                              task.status === 'todo'
                                ? 'bg-slate-500'
                                : task.status === 'doing'
                                ? 'bg-amber-500 animate-pulse'
                                : 'bg-emerald-500',
                            ]"
                          ></span>
                          {{
                            task.status === "todo"
                              ? "未着手"
                              : task.status === "doing"
                              ? "作業中"
                              : "完了"
                          }}
                        </span>

                        <!-- 作成日時 -->
                        <span class="text-xs text-slate-500">
                          {{ formatDate(task.created_at) }}
                        </span>
                      </div>
                    </div>

                    <!-- アクションボタン -->
                    <div class="flex gap-2 flex-shrink-0">
                      <button
                        v-if="task.status === 'todo'"
                        @click="startTask(task.id)"
                        class="px-4 py-2 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all duration-300 flex items-center gap-2"
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
                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                          />
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                          />
                        </svg>
                        開始
                      </button>
                      <button
                        v-if="task.status === 'doing'"
                        @click="completeTask(task.id)"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-400 to-teal-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all duration-300 flex items-center gap-2"
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
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                          />
                        </svg>
                        完了
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="filteredTasks.length === 0"
                class="text-center py-16 backdrop-blur-lg bg-white/60 rounded-2xl border border-white/50"
              >
                <div
                  class="w-20 h-20 mx-auto mb-4 rounded-full backdrop-blur-lg bg-slate-500/10 border border-slate-300/30 flex items-center justify-center"
                >
                  <svg
                    class="w-10 h-10 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                </div>
                <p class="text-slate-600 font-medium">
                  {{
                    taskSearchQuery || taskStatusFilter !== "all"
                      ? "該当するタスクがありません"
                      : "タスクがまだありません。上のフォームから作成してください。"
                  }}
                </p>
              </div>
            </div>
          </div>

          <!-- メンバータブ -->
          <div v-show="activeTab === 'members'" class="mt-8">
            <div
              v-if="memberError"
              class="backdrop-blur-lg bg-red-500/10 border border-red-300/50 rounded-2xl p-6 shadow-xl mb-6"
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
                <p class="text-red-800 font-medium">{{ memberError }}</p>
              </div>
            </div>

            <!-- メンバー追加フォーム -->
            <div class="mb-6">
              <button
                v-if="!showAddMemberForm"
                @click="showAddMemberForm = true"
                class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg transition-all duration-300 flex items-center gap-2"
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
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                  />
                </svg>
                メンバーを追加
              </button>

              <!-- フォーム -->
              <div
                v-else
                class="backdrop-blur-lg bg-white/70 rounded-2xl shadow-xl border border-white/50 p-8"
              >
                <div class="flex items-center gap-3 mb-6">
                  <div
                    class="p-2 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-500"
                  >
                    <svg
                      class="w-6 h-6 text-white"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                      />
                    </svg>
                  </div>
                  <h2 class="text-2xl font-bold text-slate-800">
                    新しいメンバーを追加
                  </h2>
                </div>

                <form @submit.prevent="addMember" class="space-y-5">
                  <div>
                    <label
                      class="block text-sm font-semibold text-slate-700 mb-2"
                      >ユーザーを選択</label
                    >
                    <select
                      v-model="newMember.user_id"
                      required
                      class="w-full px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
                    >
                      <option value="">ユーザーを選択してください</option>
                      <option
                        v-for="user in availableUsers"
                        :key="user.id"
                        :value="user.id"
                      >
                        {{ user.name }} ({{ user.email }})
                      </option>
                    </select>
                  </div>

                  <div>
                    <label
                      class="block text-sm font-semibold text-slate-700 mb-2"
                      >役割</label
                    >
                    <select
                      v-model="newMember.role"
                      class="w-full px-4 py-3 rounded-xl backdrop-blur-lg bg-white/80 border border-slate-200 focus:border-blue-400 focus:ring-4 focus:ring-blue-100 transition-all outline-none"
                    >
                      <option value="project_member">メンバー</option>
                      <option value="project_admin">管理者</option>
                      <option value="project_owner">オーナー</option>
                    </select>
                  </div>

                  <div class="flex gap-3">
                    <button
                      type="submit"
                      :disabled="addingMember"
                      class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-500 text-white font-semibold rounded-xl hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 flex items-center gap-2"
                    >
                      <svg
                        v-if="!addingMember"
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
                      {{ addingMember ? "追加中..." : "メンバーを追加" }}
                    </button>
                    <button
                      type="button"
                      @click="
                        showAddMemberForm = false;
                        newMember = { user_id: '', role: 'project_member' };
                        memberError = null;
                      "
                      class="px-6 py-3 backdrop-blur-lg bg-white/80 hover:bg-white text-slate-700 font-semibold rounded-xl transition-all duration-300 border border-slate-200 hover:border-slate-300"
                    >
                      キャンセル
                    </button>
                  </div>
                </form>
              </div>
            </div>

            <div
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
                        名前
                      </th>
                      <th
                        class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider"
                      >
                        メールアドレス
                      </th>
                      <th
                        class="px-6 py-4 text-left text-xs font-bold text-slate-700 uppercase tracking-wider"
                      >
                        役割
                      </th>
                      <th
                        class="px-6 py-4 text-right text-xs font-bold text-slate-700 uppercase tracking-wider"
                      >
                        操作
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200/50">
                    <tr
                      v-for="user in members"
                      :key="user.id"
                      class="hover:bg-blue-50/30 transition-colors"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                          <div
                            class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center text-white font-bold"
                          >
                            {{ user.name?.charAt(0) || "?" }}
                          </div>
                          <span class="text-sm font-semibold text-slate-800">{{
                            user.name
                          }}</span>
                        </div>
                      </td>
                      <td
                        class="px-6 py-4 whitespace-nowrap text-sm text-slate-600"
                      >
                        {{ user.email }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span
                          :class="[
                            'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold backdrop-blur-lg border',
                            user.pivot?.role === 'project_owner'
                              ? 'bg-purple-500/20 text-purple-700 border-purple-300/50'
                              : user.pivot?.role === 'project_admin'
                              ? 'bg-blue-500/20 text-blue-700 border-blue-300/50'
                              : 'bg-slate-500/20 text-slate-700 border-slate-300/50',
                          ]"
                        >
                          {{
                            user.pivot?.role === "project_owner"
                              ? "オーナー"
                              : user.pivot?.role === "project_admin"
                              ? "管理者"
                              : "メンバー"
                          }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right">
                        <button
                          v-if="canDeleteMember"
                          @click="deleteMember(user.id)"
                          class="px-4 py-2 bg-gradient-to-r from-red-400 to-rose-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg transition-all duration-300 flex items-center gap-2 ml-auto"
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
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                          </svg>
                          削除
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-if="members.length === 0" class="text-center py-16">
                <div
                  class="w-20 h-20 mx-auto mb-4 rounded-full backdrop-blur-lg bg-slate-500/10 border border-slate-300/30 flex items-center justify-center"
                >
                  <svg
                    class="w-10 h-10 text-slate-400"
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
                <p class="text-slate-600 font-medium">
                  メンバーが見つかりません
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
