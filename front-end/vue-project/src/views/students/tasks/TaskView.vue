<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Quản lý công việc</h2>
        <p class="text-slate-500 mt-0.5 text-sm">Kanban board · Nhóm {{ groupName }}</p>
      </div>
      <button
        v-if="isLeader && groupId"
        @click="openCreateModal"
        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition-colors flex items-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Tạo công việc
      </button>
    </div>

    <!-- No group -->
    <div v-if="!groupId" class="bg-white rounded-2xl border p-12 text-center">
      <p class="text-slate-500">Bạn chưa có nhóm. Hãy tạo hoặc tham gia nhóm trước.</p>
    </div>

    <template v-else>
      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div
          v-for="(stat, key) in statItems"
          :key="key"
          class="bg-white rounded-xl border border-slate-200 p-4 text-center"
        >
          <p class="text-2xl font-bold" :class="stat.color">{{ stats[key] || 0 }}</p>
          <p class="text-xs text-slate-500 mt-1">{{ stat.label }}</p>
        </div>
      </div>

      <!-- ✅ Dùng KanbanBoard — truyền tasks reactive từ store -->
      <KanbanboardView
        :tasks="tasks"
        :is-leader="isLeader"
        :current-user-id="user.id"
        @click-task="openTaskDetail"
        @change-status="handleChangeStatus"
        @delete-task="handleDeleteTask"
        @task-moved="handleTaskMoved"
      />
    </template>

    <!-- Modal: Tạo / Sửa Task -->
    <Teleport to="body">
      <div v-if="showTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showTaskModal = false" />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
        >
          <h3 class="text-lg font-bold text-slate-800 mb-5">
            {{ editingTask ? 'Chỉnh sửa công việc' : 'Tạo công việc mới' }}
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Tiêu đề *</label>
              <input
                v-model="taskForm.title"
                type="text"
                class="input-field"
                placeholder="Nhập tiêu đề..."
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Mô tả</label>
              <textarea
                v-model="taskForm.description"
                rows="3"
                class="input-field"
                placeholder="Mô tả chi tiết..."
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Giao cho</label>
                <select v-model="taskForm.assignee_id" class="input-field">
                  <option value="">-- Chọn --</option>
                  <option v-for="m in members" :key="m.id" :value="m.id">
                    {{ m.name }} ({{ m.code }})
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Ưu tiên</label>
                <select v-model="taskForm.priority" class="input-field">
                  <option value="low">Thấp</option>
                  <option value="medium">Trung bình</option>
                  <option value="high">Cao</option>
                  <option value="urgent">Khẩn cấp</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Ngày bắt đầu</label>
                <input v-model="taskForm.start_date" type="datetime-local" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Deadline *</label>
                <input v-model="taskForm.deadline" type="datetime-local" class="input-field" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Trọng số (1-10)</label>
              <input
                v-model.number="taskForm.weight"
                type="number"
                min="1"
                max="10"
                class="input-field"
              />
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button
              @click="showTaskModal = false"
              class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
              Hủy
            </button>
            <button
              @click="handleSaveTask"
              :disabled="!taskForm.title || !taskForm.deadline || taskLoading"
              class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ taskLoading ? 'Đang lưu...' : editingTask ? 'Cập nhật' : 'Tạo' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Chi tiết Task -->
    <Teleport to="body">
      <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showDetailModal = false"
        />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
        >
          <div v-if="currentTask" class="flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-slate-100 flex-shrink-0">
              <div class="flex items-start justify-between">
                <div>
                  <span
                    :class="priorityClass(currentTask.priority)"
                    class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                    >{{ currentTask.priority }}</span
                  >
                  <h3 class="text-lg font-bold text-slate-800 mt-2">{{ currentTask.title }}</h3>
                </div>
                <button
                  @click="showDetailModal = false"
                  class="p-1.5 hover:bg-slate-100 rounded-lg"
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
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
              <p v-if="currentTask.description" class="text-sm text-slate-600 mt-3 leading-relaxed">
                {{ currentTask.description }}
              </p>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-6">
              <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="p-3 bg-slate-50 rounded-xl">
                  <p class="text-slate-400 text-xs mb-1">Trạng thái</p>
                  <span
                    :class="statusClass(currentTask.status)"
                    class="px-2 py-0.5 rounded-full text-xs font-bold"
                    >{{ statusLabel(currentTask.status) }}</span
                  >
                </div>
                <div class="p-3 bg-slate-50 rounded-xl">
                  <p class="text-slate-400 text-xs mb-1">Giao cho</p>
                  <p class="font-medium text-slate-700">
                    {{ currentTask.assignee?.name || 'Chưa giao' }}
                  </p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl">
                  <p class="text-slate-400 text-xs mb-1">Người tạo</p>
                  <p class="font-medium text-slate-700">{{ currentTask.creator?.name }}</p>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl">
                  <p class="text-slate-400 text-xs mb-1">Deadline</p>
                  <p
                    class="font-medium"
                    :class="currentTask.is_overdue ? 'text-red-600' : 'text-slate-700'"
                  >
                    {{ formatDate(currentTask.deadline) }}
                  </p>
                </div>
              </div>

              <div class="flex gap-2">
                <button
                  v-if="isLeader"
                  @click="openEditModal(currentTask)"
                  class="flex-1 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200"
                >
                  ✏️ Sửa
                </button>
                <select
                  v-if="canChangeStatus(currentTask)"
                  :value="currentTask.status"
                  @change="handleChangeStatus(currentTask.id, $event.target.value)"
                  class="flex-1 py-2 border border-slate-200 rounded-xl text-sm font-medium text-center cursor-pointer"
                >
                  <option value="todo">📋 Cần làm</option>
                  <option value="doing">🔄 Đang làm</option>
                  <option value="done">✅ Hoàn thành</option>
                </select>
              </div>

              <!-- Comments -->
              <div>
                <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
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
                      d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                    />
                  </svg>
                  Bình luận ({{ currentTask.comments?.length || 0 }})
                </h4>
                <div class="space-y-3 max-h-60 overflow-y-auto mb-4">
                  <div
                    v-if="!currentTask.comments?.length"
                    class="text-center py-6 text-sm text-slate-400"
                  >
                    Chưa có bình luận.
                  </div>
                  <div
                    v-for="comment in currentTask.comments"
                    :key="comment.id"
                    class="flex gap-3 group"
                  >
                    <div
                      class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white"
                      :class="
                        comment.user?.id === user.id
                          ? 'bg-gradient-to-br from-indigo-500 to-purple-600'
                          : 'bg-gradient-to-br from-slate-400 to-slate-500'
                      "
                    >
                      {{ comment.user?.name?.charAt(0) || '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-xs font-semibold text-slate-700">{{
                          comment.user?.name
                        }}</span>
                        <span class="text-[10px] text-slate-400">{{
                          formatTime(comment.created_at)
                        }}</span>
                        <div
                          class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity flex gap-1"
                        >
                          <button
                            v-if="comment.user?.id === user.id"
                            @click="startEditComment(comment)"
                            class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-indigo-600"
                          >
                            <svg
                              class="w-3.5 h-3.5"
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
                          </button>
                          <button
                            v-if="comment.user?.id === user.id || isLeader"
                            @click="handleDeleteComment(comment.id)"
                            class="p-1 rounded hover:bg-red-50 text-slate-400 hover:text-red-500"
                          >
                            <svg
                              class="w-3.5 h-3.5"
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
                          </button>
                        </div>
                      </div>
                      <div v-if="editingCommentId === comment.id" class="flex gap-2">
                        <input
                          v-model="editCommentContent"
                          class="flex-1 px-3 py-1.5 border border-indigo-300 rounded-lg text-sm"
                          @keyup.enter="handleUpdateComment"
                          @keyup.escape="cancelEditComment"
                        />
                        <button
                          @click="handleUpdateComment"
                          class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold"
                        >
                          Lưu
                        </button>
                        <button
                          @click="cancelEditComment"
                          class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs text-slate-500"
                        >
                          Hủy
                        </button>
                      </div>
                      <p v-else class="text-sm text-slate-600 leading-relaxed">
                        {{ comment.content }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="flex gap-2 items-end">
                  <textarea
                    v-model="newComment"
                    @keydown.enter.exact.prevent="handleAddComment"
                    placeholder="Viết bình luận... (Enter để gửi)"
                    rows="2"
                    class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-slate-400"
                  />
                  <button
                    @click="handleAddComment"
                    :disabled="!newComment.trim() || commentSending"
                    class="px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 flex-shrink-0"
                  >
                    <svg
                      v-if="!commentSending"
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                      />
                    </svg>
                    <div
                      v-else
                      class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"
                    />
                  </button>
                </div>
              </div>

              <!-- Activities -->
              <div v-if="currentTask.activities?.length">
                <h4 class="text-sm font-bold text-slate-700 mb-3">Lịch sử hoạt động</h4>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                  <div
                    v-for="a in currentTask.activities"
                    :key="a.id"
                    class="flex items-start gap-2 text-xs"
                  >
                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-400 mt-1.5 flex-shrink-0" />
                    <div>
                      <span class="font-medium text-slate-600">{{ a.user?.name }}</span>
                      <span class="text-slate-400">
                        · {{ activityLabel(a.action) }} · {{ formatTime(a.created_at) }}</span
                      >
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { useTaskStore } from '@/stores/students/taskStore'
import { useGroupStore } from '@/stores/students/groupStore'
import KanbanboardView from './KanbanboardView.vue'
const route = useRoute()
const dashboardStore = useDashboardStore()
const taskStore = useTaskStore()
const groupStore = useGroupStore()

// ✅ Một dòng storeToRefs duy nhất — lấy tasks reactive
const { stats, currentTask, loading: taskLoading, tasks } = storeToRefs(taskStore)

const user = JSON.parse(localStorage.getItem('user') || '{}')

const showTaskModal = ref(false)
const showDetailModal = ref(false)
const editingTask = ref(null)
const newComment = ref('')
const commentSending = ref(false)
const editingCommentId = ref(null)
const editCommentContent = ref('')

const taskForm = ref({
  title: '',
  description: '',
  assignee_id: '',
  priority: 'medium',
  start_date: '',
  deadline: '',
  weight: 1,
})

// ── Computed ──────────────────────────────────────────────
const groupId = computed(() => {
  const fromRoute = route.query.group_id
  if (fromRoute) return Number(fromRoute)
  return dashboardStore.myGroup?.id || null
})

const groupName = computed(() => dashboardStore.myGroup?.name || '')
const isLeader = computed(() => dashboardStore.myGroup?.leader?.id === user.id)
const members = computed(
  () => groupStore.currentGroup?.members || dashboardStore.myGroup?.members || [],
)

const statItems = {
  total: { label: 'Tổng', color: 'text-slate-700' },
  todo: { label: 'Cần làm', color: 'text-slate-500' },
  doing: { label: 'Đang làm', color: 'text-blue-600' },
  done: { label: 'Hoàn thành', color: 'text-emerald-600' },
  late: { label: 'Trễ hạn', color: 'text-red-600' },
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  if (!dashboardStore.classes.length) {
    await dashboardStore.fetchMyClasses()
  }
})

// ✅ Watch myGroup.id — fetch tasks khi có group
watch(
  () => dashboardStore.myGroup?.id,
  (id) => {
    if (id) {
      taskStore.fetchTasks(id)
      groupStore.fetchGroupDetail(id)
    }
  },
  { immediate: true },
)

// ── Handlers ─────────────────────────────────────────────
function openCreateModal() {
  editingTask.value = null
  taskForm.value = {
    title: '',
    description: '',
    assignee_id: '',
    priority: 'medium',
    start_date: '',
    deadline: '',
    weight: 1,
  }
  showTaskModal.value = true
}

function openEditModal(task) {
  editingTask.value = task
  taskForm.value = {
    title: task.title,
    description: task.description || '',
    assignee_id: task.assignee?.id || '',
    priority: task.priority,
    start_date: task.start_date ? task.start_date.slice(0, 16) : '',
    deadline: task.deadline ? task.deadline.slice(0, 16) : '',
    weight: task.weight || 1,
  }
  showDetailModal.value = false
  showTaskModal.value = true
}

async function openTaskDetail(task) {
  await taskStore.fetchTaskDetail(task.id)
  showDetailModal.value = true
}

async function handleSaveTask() {
  const data = { ...taskForm.value }
  if (!data.assignee_id) delete data.assignee_id
  if (!data.start_date) delete data.start_date
  const result = editingTask.value
    ? await taskStore.updateTask(editingTask.value.id, groupId.value, data)
    : await taskStore.createTask(groupId.value, data)
  if (result.success) showTaskModal.value = false
}

async function handleChangeStatus(taskId, status) {
  if (!status) return
  await taskStore.changeStatus(taskId, groupId.value, status)
  showDetailModal.value = false
}

async function handleDeleteTask(taskId) {
  if (!confirm('Xóa công việc này?')) return
  await taskStore.deleteTask(taskId, groupId.value)
}

// ✅ Kéo thả sang column khác → đổi status qua API
async function handleTaskMoved({ taskId, newStatus }) {
  await taskStore.changeStatus(taskId, groupId.value, newStatus)
}

function canChangeStatus(task) {
  return task.assignee?.id === user.id || isLeader.value
}

// Comments
async function handleAddComment() {
  if (!newComment.value.trim() || !currentTask.value) return
  commentSending.value = true
  const result = await taskStore.addComment(currentTask.value.id, newComment.value.trim())
  if (result.success) newComment.value = ''
  commentSending.value = false
}

function startEditComment(comment) {
  editingCommentId.value = comment.id
  editCommentContent.value = comment.content
}

function cancelEditComment() {
  editingCommentId.value = null
  editCommentContent.value = ''
}

async function handleUpdateComment() {
  if (!editCommentContent.value.trim() || !editingCommentId.value) return
  const result = await taskStore.updateComment(
    editingCommentId.value,
    editCommentContent.value.trim(),
  )
  if (result.success) cancelEditComment()
}

async function handleDeleteComment(commentId) {
  if (!confirm('Xóa bình luận này?')) return
  await taskStore.deleteComment(commentId)
}

// Formatters
function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatTime(d) {
  if (!d) return ''
  const date = new Date(d)
  return (
    date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) +
    ' · ' +
    date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
  )
}

function activityLabel(action) {
  return (
    {
      created: 'đã tạo task',
      status_changed: 'đổi trạng thái',
      updated: 'cập nhật task',
      commented: 'đã bình luận',
      comment_updated: 'sửa bình luận',
      comment_deleted: 'xóa bình luận',
    }[action] || action
  )
}

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-700',
      high: 'bg-orange-100 text-orange-700',
      medium: 'bg-blue-100 text-blue-700',
      low: 'bg-slate-100 text-slate-600',
    }[p] || ''
  )
}

function statusClass(s) {
  return (
    {
      todo: 'bg-slate-100 text-slate-600',
      doing: 'bg-blue-100 text-blue-700',
      done: 'bg-emerald-100 text-emerald-700',
      late: 'bg-red-100 text-red-700',
    }[s] || ''
  )
}

function statusLabel(s) {
  return { todo: 'Cần làm', doing: 'Đang làm', done: 'Hoàn thành', late: 'Trễ hạn' }[s] || s
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent;
}
</style>
