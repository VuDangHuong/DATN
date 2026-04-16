<!-- src/views/tasks/TaskView.vue -->
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

      <!-- Kanban Board -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <KanbanColumn
          v-for="col in columns"
          :key="col.key"
          :title="col.title"
          :color="col.color"
          :icon="col.icon"
          :tasks="getColumnTasks(col.key)"
          :is-leader="isLeader"
          :current-user-id="user.id"
          @change-status="handleChangeStatus"
          @click-task="openTaskDetail"
          @delete-task="handleDeleteTask"
        />
      </div>
    </template>

    <!-- ── Modal: Tạo / Sửa Task ──────────── -->
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
              :disabled="!taskForm.title || !taskForm.deadline || taskStore.loading"
              class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ taskStore.loading ? 'Đang lưu...' : editingTask ? 'Cập nhật' : 'Tạo' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Modal: Chi tiết Task ────────────── -->
    <Teleport to="body">
      <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showDetailModal = false"
        />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
        >
          <div v-if="currentTask">
            <div class="flex items-start justify-between mb-4">
              <div>
                <span
                  :class="priorityClass(currentTask.priority)"
                  class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                >
                  {{ currentTask.priority }}
                </span>
                <h3 class="text-lg font-bold text-slate-800 mt-2">{{ currentTask.title }}</h3>
              </div>
              <button @click="showDetailModal = false" class="p-1 hover:bg-slate-100 rounded-lg">
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

            <p v-if="currentTask.description" class="text-sm text-slate-600 mb-4 leading-relaxed">
              {{ currentTask.description }}
            </p>

            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-400">Trạng thái</span
                ><span
                  :class="statusClass(currentTask.status)"
                  class="px-2 py-0.5 rounded-full text-xs font-bold"
                  >{{ statusLabel(currentTask.status) }}</span
                >
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Người tạo</span
                ><span class="text-slate-700 font-medium">{{ currentTask.creator?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Giao cho</span
                ><span class="text-slate-700 font-medium">{{
                  currentTask.assignee?.name || 'Chưa giao'
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-400">Deadline</span
                ><span
                  class="text-slate-700 font-medium"
                  :class="{ 'text-red-600': currentTask.is_overdue }"
                  >{{ formatDate(currentTask.deadline) }}</span
                >
              </div>
              <div v-if="currentTask.actual_finish_date" class="flex justify-between">
                <span class="text-slate-400">Hoàn thành</span
                ><span class="text-emerald-600 font-medium">{{
                  formatDate(currentTask.actual_finish_date)
                }}</span>
              </div>
            </div>

            <!-- Activities -->
            <div v-if="currentTask.activities?.length" class="mt-6">
              <h4 class="text-sm font-semibold text-slate-700 mb-3">Lịch sử hoạt động</h4>
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
                      · {{ a.action }} · {{ formatDate(a.created_at) }}</span
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mt-6">
              <button
                v-if="isLeader"
                @click="openEditModal(currentTask)"
                class="flex-1 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200"
              >
                ✏️ Sửa
              </button>
              <select
                v-if="canChangeStatus(currentTask)"
                @change="handleChangeStatus(currentTask.id, $event.target.value)"
                class="flex-1 py-2 border border-slate-200 rounded-xl text-sm font-medium text-center cursor-pointer"
              >
                <option value="" disabled selected>Đổi trạng thái...</option>
                <option value="todo">📋 Todo</option>
                <option value="doing">🔄 Doing</option>
                <option value="done">✅ Done</option>
              </select>
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
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { useTaskStore } from '@/stores/students/taskStore'
import { useGroupStore } from '@/stores/students/groupStore'
import { storeToRefs } from 'pinia'
import KanbanColumn from '@/components/students/KanbanColumn.vue'

const route = useRoute()
const dashboardStore = useDashboardStore()
const taskStore = useTaskStore()
const groupStore = useGroupStore()
const { stats, currentTask, loading: taskLoading } = storeToRefs(taskStore)

const user = JSON.parse(localStorage.getItem('user') || '{}')
const showTaskModal = ref(false)
const showDetailModal = ref(false)
const editingTask = ref(null)

const taskForm = ref({
  title: '',
  description: '',
  assignee_id: '',
  priority: 'medium',
  start_date: '',
  deadline: '',
  weight: 1,
})

const groupId = computed(() => {
  const fromRoute = route.query.group_id
  if (fromRoute) return Number(fromRoute)
  return dashboardStore.myGroup?.id || null
})

const groupName = computed(() => dashboardStore.myGroup?.name || '')
const isLeader = computed(() => dashboardStore.myGroup?.leader?.id === user.id)
const members = computed(
  () => groupStore.currentGroup?.members?.map((m) => m) || dashboardStore.myGroup?.members || [],
)

const columns = [
  { key: 'todo', title: 'Cần làm', color: 'bg-slate-500', icon: '📋' },
  { key: 'doing', title: 'Đang làm', color: 'bg-blue-500', icon: '🔄' },
  { key: 'done', title: 'Hoàn thành', color: 'bg-emerald-500', icon: '✅' },
  { key: 'late', title: 'Trễ hạn', color: 'bg-red-500', icon: '⚠️' },
]

const statItems = {
  total: { label: 'Tổng', color: 'text-slate-700' },
  todo: { label: 'Cần làm', color: 'text-slate-500' },
  doing: { label: 'Đang làm', color: 'text-blue-600' },
  done: { label: 'Hoàn thành', color: 'text-emerald-600' },
  late: { label: 'Trễ hạn', color: 'text-red-600' },
}

function getColumnTasks(status) {
  return (taskStore.tasks || []).filter((t) => t.status === status)
}

// Load tasks khi groupId thay đổi
watch(
  groupId,
  (id) => {
    if (id) {
      taskStore.fetchTasks(id)
      if (dashboardStore.myGroup?.id) groupStore.fetchGroupDetail(dashboardStore.myGroup.id)
    }
  },
  { immediate: true },
)

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

  let result
  if (editingTask.value) {
    result = await taskStore.updateTask(editingTask.value.id, groupId.value, data)
  } else {
    result = await taskStore.createTask(groupId.value, data)
  }

  if (result.success) {
    showTaskModal.value = false
  }
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

function canChangeStatus(task) {
  return task.assignee?.id === user.id || isLeader.value
}

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

function priorityClass(p) {
  const map = {
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-blue-100 text-blue-700',
    low: 'bg-slate-100 text-slate-600',
  }
  return map[p] || ''
}
function statusClass(s) {
  const map = {
    todo: 'bg-slate-100 text-slate-600',
    doing: 'bg-blue-100 text-blue-700',
    done: 'bg-emerald-100 text-emerald-700',
    late: 'bg-red-100 text-red-700',
  }
  return map[s] || ''
}
function statusLabel(s) {
  const map = { todo: 'Cần làm', doing: 'Đang làm', done: 'Hoàn thành', late: 'Trễ hạn' }
  return map[s] || s
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm
         focus:ring-2 focus:ring-indigo-500 focus:border-transparent;
}
</style>
