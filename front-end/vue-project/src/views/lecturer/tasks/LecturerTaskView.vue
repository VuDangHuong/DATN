<!-- src/views/lecturer/LecturerTaskView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-stone-800">Công việc nhóm</h2>
        <p class="text-sm text-stone-500 mt-1">Theo dõi nhiệm vụ các thành viên trong nhóm</p>
      </div>
    </div>

    <!-- Chọn lớp + nhóm -->
    <div
      class="bg-white rounded-xl border border-stone-200 p-4 mb-5 flex flex-wrap gap-3 items-center"
    >
      <!-- Chọn lớp -->
      <div class="flex items-center gap-2">
        <label class="text-xs font-medium text-stone-500 whitespace-nowrap">Lớp học:</label>
        <select
          v-model="selectedClassId"
          @change="onClassChange"
          class="px-3 py-1.5 border border-stone-200 rounded-lg text-sm text-stone-700 outline-none focus:ring-2 focus:ring-teal-500"
        >
          <option value="">-- Chọn lớp --</option>
          <option v-for="c in lecturerStore.classes" :key="c.id" :value="c.id">
            {{ c.code }} - {{ c.name }}
          </option>
        </select>
      </div>

      <!-- Chọn nhóm -->
      <div v-if="selectedClassId" class="flex items-center gap-2">
        <label class="text-xs font-medium text-stone-500 whitespace-nowrap">Nhóm:</label>
        <select
          v-model="selectedGroupId"
          @change="onGroupChange"
          class="px-3 py-1.5 border border-stone-200 rounded-lg text-sm text-stone-700 outline-none focus:ring-2 focus:ring-teal-500"
          :disabled="loadingGroups"
        >
          <option value="">-- Chọn nhóm --</option>
          <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
        </select>
        <div
          v-if="loadingGroups"
          class="w-4 h-4 border-2 border-stone-300 border-t-stone-600 rounded-full animate-spin"
        />
      </div>

      <!-- Filter thành viên -->
      <div v-if="selectedGroupId && members.length" class="flex items-center gap-2">
        <label class="text-xs font-medium text-stone-500 whitespace-nowrap">Thành viên:</label>
        <select
          v-model="filterMemberId"
          @change="applyFilters"
          class="px-3 py-1.5 border border-stone-200 rounded-lg text-sm text-stone-700 outline-none focus:ring-2 focus:ring-teal-500"
        >
          <option value="">Tất cả thành viên</option>
          <option v-for="m in members" :key="m.id" :value="m.id">
            {{ m.name }} {{ m.role === 'leader' ? '(Trưởng nhóm)' : '' }}
          </option>
        </select>
      </div>

      <!-- Filter trạng thái -->
      <div v-if="selectedGroupId" class="flex gap-1 bg-stone-100 rounded-lg p-1 ml-auto">
        <button
          v-for="f in statusFilters"
          :key="f.value"
          @click="handleFilterClick(f.value)"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition"
          :class="
            filterStatus === f.value
              ? 'bg-white text-stone-800 shadow-sm'
              : 'text-stone-500 hover:text-stone-700'
          "
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- Chưa chọn nhóm -->
    <div
      v-if="!selectedGroupId"
      class="bg-white rounded-xl border border-stone-200 p-12 text-center"
    >
      <svg
        class="w-12 h-12 mx-auto text-stone-300 mb-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
      <p class="text-stone-400 font-medium">Chọn lớp và nhóm để xem công việc</p>
    </div>

    <!-- Loading tasks -->
    <div v-else-if="loadingTasks" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div
          v-for="(stat, key) in statItems"
          :key="key"
          class="bg-white rounded-xl border border-stone-200 p-4 text-center"
        >
          <p class="text-2xl font-bold" :class="stat.color">{{ stats[key] || 0 }}</p>
          <p class="text-xs text-stone-400 mt-1">{{ stat.label }}</p>
        </div>
      </div>

      <!-- Info thành viên đang xem -->
      <div
        v-if="filterMemberId"
        class="mb-4 flex items-center gap-2 px-4 py-2.5 bg-teal-50 border border-teal-200 rounded-xl"
      >
        <div
          class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700"
        >
          {{ selectedMember?.name?.charAt(0) }}
        </div>
        <div>
          <span class="text-sm font-semibold text-teal-800">{{ selectedMember?.name }}</span>
          <span
            v-if="selectedMember?.role === 'leader'"
            class="ml-2 px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded"
            >Trưởng nhóm</span
          >
          <span class="text-xs text-teal-600 ml-2">{{ filteredTasks.length }} công việc</span>
        </div>
        <button
          @click="clearMemberFilter"
          class="ml-auto text-teal-500 hover:text-teal-700 text-xs"
        >
          ✕ Bỏ lọc
        </button>
      </div>

      <!-- Empty -->
      <div
        v-if="!filteredTasks.length"
        class="bg-white rounded-xl border border-stone-200 p-12 text-center"
      >
        <p class="text-stone-400">Không có công việc nào</p>
      </div>

      <!-- Kanban Board (view-only) -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          v-for="col in columns"
          :key="col.status"
          class="bg-stone-50 rounded-xl border border-stone-200 p-4"
        >
          <!-- Column header -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-stone-700">{{ col.label }}</span>
              <span class="px-2 py-0.5 text-xs font-bold rounded-full" :class="col.badgeClass">
                {{ getColumnTasks(col.status).length }}
              </span>
            </div>
            <div class="w-2 h-2 rounded-full" :class="col.dotClass" />
          </div>

          <!-- Tasks -->
          <div class="space-y-3">
            <div
              v-if="!getColumnTasks(col.status).length"
              class="text-center py-6 text-xs text-stone-400 border-2 border-dashed border-stone-200 rounded-xl"
            >
              Không có công việc
            </div>

            <div
              v-for="task in getColumnTasks(col.status)"
              :key="task.id"
              class="bg-white rounded-xl border border-stone-200 p-4 cursor-pointer hover:shadow-sm hover:border-teal-300 transition"
              @click="openTaskDetail(task)"
            >
              <!-- Priority badge -->
              <div class="flex items-center justify-between mb-2">
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase"
                  :class="priorityClass(task.priority)"
                >
                  {{ priorityLabel(task.priority) }}
                </span>
                <span
                  v-if="task.is_overdue"
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700"
                >
                  Trễ hạn
                </span>
              </div>

              <!-- Title -->
              <p class="text-sm font-semibold text-stone-800 mb-2 leading-snug">{{ task.title }}</p>

              <!-- Assignee + deadline -->
              <div class="flex items-center justify-between mt-3">
                <div class="flex items-center gap-1.5">
                  <div
                    class="w-5 h-5 rounded-full bg-teal-100 flex items-center justify-center text-[10px] font-bold text-teal-700"
                  >
                    {{ task.assignee?.name?.charAt(0) ?? '?' }}
                  </div>
                  <span class="text-xs text-stone-500">{{
                    task.assignee?.name ?? 'Chưa giao'
                  }}</span>
                </div>
                <span v-if="task.deadline" class="text-[10px] text-stone-400">
                  {{ formatDate(task.deadline) }}
                </span>
              </div>

              <!-- Progress bar nếu có weight -->
              <div v-if="task.weight" class="mt-2 flex items-center gap-1.5">
                <div class="flex-1 h-1 bg-stone-100 rounded-full">
                  <div
                    class="h-1 rounded-full transition-all"
                    :class="
                      task.status === 'done'
                        ? 'bg-emerald-500'
                        : task.status === 'doing'
                          ? 'bg-blue-500'
                          : 'bg-stone-300'
                    "
                    :style="{
                      width:
                        task.status === 'done' ? '100%' : task.status === 'doing' ? '50%' : '0%',
                    }"
                  />
                </div>
                <span class="text-[10px] text-stone-400">W{{ task.weight }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Trễ hạn column riêng nếu có -->
      <div v-if="getColumnTasks('late').length" class="mt-4">
        <div class="bg-red-50 rounded-xl border border-red-200 p-4">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-sm font-semibold text-red-700">⚠️ Trễ hạn</span>
            <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700">
              {{ getColumnTasks('late').length }}
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div
              v-for="task in getColumnTasks('late')"
              :key="task.id"
              class="bg-white rounded-xl border border-red-200 p-3 cursor-pointer hover:shadow-sm transition"
              @click="openTaskDetail(task)"
            >
              <p class="text-sm font-semibold text-stone-800 mb-1">{{ task.title }}</p>
              <div class="flex items-center justify-between">
                <span class="text-xs text-stone-500">{{ task.assignee?.name ?? 'Chưa giao' }}</span>
                <span class="text-[10px] text-red-600 font-medium">{{
                  formatDate(task.deadline)
                }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal: Chi tiết task (view-only, không có edit/delete) -->
    <Teleport to="body">
      <div
        v-if="showDetailModal && currentTask"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showDetailModal = false"
        />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
        >
          <!-- Header -->
          <div class="p-6 border-b border-stone-100 flex items-start justify-between flex-shrink-0">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase"
                  :class="priorityClass(currentTask.priority)"
                >
                  {{ priorityLabel(currentTask.priority) }}
                </span>
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                  :class="statusClass(currentTask.status)"
                >
                  {{ statusLabel(currentTask.status) }}
                </span>
              </div>
              <h3 class="text-lg font-bold text-stone-800">{{ currentTask.title }}</h3>
            </div>
            <button @click="showDetailModal = false" class="p-1.5 hover:bg-stone-100 rounded-lg">
              <svg
                class="w-5 h-5 text-stone-400"
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

          <!-- Body -->
          <div class="flex-1 overflow-y-auto p-6 space-y-5">
            <p
              v-if="currentTask.description"
              class="text-sm text-stone-600 leading-relaxed bg-stone-50 rounded-xl p-4"
            >
              {{ currentTask.description }}
            </p>

            <!-- Info grid -->
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Giao cho</p>
                <div class="flex items-center gap-2">
                  <div
                    class="w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center text-[10px] font-bold text-teal-700"
                  >
                    {{ currentTask.assignee?.name?.charAt(0) ?? '?' }}
                  </div>
                  <p class="font-medium text-stone-700">
                    {{ currentTask.assignee?.name || 'Chưa giao' }}
                  </p>
                </div>
              </div>
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Người tạo</p>
                <p class="font-medium text-stone-700">{{ currentTask.creator?.name }}</p>
              </div>
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Bắt đầu</p>
                <p class="font-medium text-stone-700">
                  {{ formatDate(currentTask.start_date) || '—' }}
                </p>
              </div>
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Deadline</p>
                <p
                  class="font-medium"
                  :class="currentTask.is_overdue ? 'text-red-600' : 'text-stone-700'"
                >
                  {{ formatDate(currentTask.deadline) }}
                </p>
              </div>
              <div v-if="currentTask.weight" class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Trọng số</p>
                <p class="font-medium text-stone-700">{{ currentTask.weight }}/10</p>
              </div>
            </div>

            <!-- Activities -->
            <div v-if="currentTask.activities?.length">
              <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
                Lịch sử hoạt động
              </p>
              <div class="space-y-2 max-h-40 overflow-y-auto">
                <div
                  v-for="a in currentTask.activities"
                  :key="a.id"
                  class="flex items-start gap-2 text-xs"
                >
                  <div class="w-1.5 h-1.5 rounded-full bg-teal-400 mt-1.5 flex-shrink-0" />
                  <div>
                    <span class="font-medium text-stone-600">{{ a.user?.name }}</span>
                    <span class="text-stone-400">
                      · {{ activityLabel(a.action) }} · {{ formatTime(a.created_at) }}</span
                    >
                  </div>
                </div>
              </div>
            </div>

            <!-- Comments (read-only) -->
            <div v-if="currentTask.comments?.length">
              <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
                Bình luận ({{ currentTask.comments.length }})
              </p>
              <div class="space-y-3 max-h-60 overflow-y-auto">
                <div v-for="comment in currentTask.comments" :key="comment.id" class="flex gap-3">
                  <div
                    class="w-7 h-7 rounded-full bg-stone-200 flex items-center justify-center text-xs font-bold text-stone-600 flex-shrink-0"
                  >
                    {{ comment.user?.name?.charAt(0) }}
                  </div>
                  <div class="flex-1 bg-stone-50 rounded-xl p-3">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-xs font-semibold text-stone-700">{{
                        comment.user?.name
                      }}</span>
                      <span class="text-[10px] text-stone-400">{{
                        formatTime(comment.created_at)
                      }}</span>
                    </div>
                    <p class="text-sm text-stone-600">{{ comment.content }}</p>
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
import { ref, computed, onMounted, watch } from 'vue'
import axiosClient from '@/api/axiosClient'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { useToastStore } from '@/stores/toast'

const lecturerStore = useLecturerStore()
const toast = useToastStore()

// ── State ──────────────────────────────────────────────
const selectedClassId = ref(lecturerStore.selectedClassId || '')
const selectedGroupId = ref('')
const filterMemberId = ref('')
const filterStatus = ref('')

const groups = ref([])
const members = ref([])
const allTasks = ref([])
const stats = ref({ total: 0, todo: 0, doing: 0, done: 0, late: 0 })
const currentTask = ref(null)

const loadingGroups = ref(false)
const loadingTasks = ref(false)
const showDetailModal = ref(false)

// ── Config ─────────────────────────────────────────────
const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'todo', label: 'Cần làm' },
  { value: 'doing', label: 'Đang làm' },
  { value: 'done', label: 'Hoàn thành' },
  { value: 'late', label: 'Trễ hạn' },
]

const columns = [
  {
    status: 'todo',
    label: 'Cần làm',
    badgeClass: 'bg-stone-100 text-stone-600',
    dotClass: 'bg-stone-400',
  },
  {
    status: 'doing',
    label: 'Đang làm',
    badgeClass: 'bg-blue-100 text-blue-700',
    dotClass: 'bg-blue-500',
  },
  {
    status: 'done',
    label: 'Hoàn thành',
    badgeClass: 'bg-emerald-100 text-emerald-700',
    dotClass: 'bg-emerald-500',
  },
]

const statItems = {
  total: { label: 'Tổng', color: 'text-stone-700' },
  todo: { label: 'Cần làm', color: 'text-stone-500' },
  doing: { label: 'Đang làm', color: 'text-blue-600' },
  done: { label: 'Hoàn thành', color: 'text-emerald-600' },
  late: { label: 'Trễ hạn', color: 'text-red-600' },
}

// ── Computed ───────────────────────────────────────────
const selectedMember = computed(() =>
  members.value.find((m) => m.id === Number(filterMemberId.value)),
)

const filteredTasks = computed(() => {
  let list = allTasks.value
  if (filterMemberId.value) {
    list = list.filter((t) => t.assignee?.id === Number(filterMemberId.value))
  }
  if (filterStatus.value) {
    list = list.filter((t) => t.status === filterStatus.value)
  }
  return list
})

const handleFilterClick = (value) => {
  filterStatus.value = value
  applyFilters()
}
const clearMemberFilter = () => {
  filterMemberId.value = ''
  applyFilters()
}
// ── Lifecycle ──────────────────────────────────────────
onMounted(() => {
  if (selectedClassId.value) loadGroups(selectedClassId.value)
})

// Sync với lecturerStore khi GV đổi lớp từ topbar
watch(
  () => lecturerStore.selectedClassId,
  (id) => {
    if (id && id !== selectedClassId.value) {
      selectedClassId.value = id
      onClassChange()
    }
  },
)

// ── Actions ────────────────────────────────────────────
async function onClassChange() {
  selectedGroupId.value = ''
  filterMemberId.value = ''
  filterStatus.value = ''
  groups.value = []
  members.value = []
  allTasks.value = []
  if (selectedClassId.value) await loadGroups(selectedClassId.value)
}

async function onGroupChange() {
  filterMemberId.value = ''
  filterStatus.value = ''
  members.value = []
  allTasks.value = []
  if (selectedGroupId.value) {
    await loadGroupMembers(selectedGroupId.value)
    await loadTasks(selectedGroupId.value)
  }
}

async function loadGroups(classId) {
  openClassId.value = classId
  groups.value = []
  loadingGroups.value = true
  try {
    const { data } = await axiosClient.get(`/lecturer/classes/${classId}/groups`)
    console.log('groups response:', data) // ← xem console
    // Thử tất cả key có thể
    groups.value = data.groups ?? data.data ?? data ?? []
  } catch (e) {
    console.error('loadGroups error:', e.response?.data)
    groups.value = []
  } finally {
    loadingGroups.value = false
  }
}

async function loadGroupMembers(groupId) {
  try {
    const { data } = await axiosClient.get(`/lecturer/groups/${groupId}/members`)
    members.value = data.members ?? data.data ?? data
  } catch {
    members.value = []
  }
}

async function loadTasks(groupId) {
  loadingTasks.value = true
  try {
    const { data } = await axiosClient.get(`/lecturer/groups/${groupId}/tasks`)
    allTasks.value = data.tasks ?? data.data?.tasks ?? data.data ?? []
    stats.value = data.stats ?? data.data?.stats ?? calcStats(allTasks.value)
  } catch {
    allTasks.value = []
    toast.error('Không thể tải công việc nhóm')
  } finally {
    loadingTasks.value = false
  }
}

function calcStats(list) {
  return {
    total: list.length,
    todo: list.filter((t) => t.status === 'todo').length,
    doing: list.filter((t) => t.status === 'doing').length,
    done: list.filter((t) => t.status === 'done').length,
    late: list.filter((t) => t.status === 'late').length,
  }
}

function applyFilters() {
  // filteredTasks là computed — tự động reactive
}

function getColumnTasks(status) {
  return filteredTasks.value.filter((t) => t.status === status)
}

async function openTaskDetail(task) {
  try {
    const { data } = await axiosClient.get(`/tasks/${task.id}`)
    currentTask.value = data.task ?? data.data ?? task
  } catch {
    currentTask.value = task
  }
  showDetailModal.value = true
}

// ── Formatters ─────────────────────────────────────────
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

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-700',
      high: 'bg-orange-100 text-orange-700',
      medium: 'bg-blue-100 text-blue-700',
      low: 'bg-stone-100 text-stone-600',
    }[p] ?? 'bg-stone-100 text-stone-600'
  )
}

function priorityLabel(p) {
  return { urgent: 'Khẩn cấp', high: 'Cao', medium: 'Trung bình', low: 'Thấp' }[p] ?? p
}

function statusClass(s) {
  return (
    {
      todo: 'bg-stone-100 text-stone-600',
      doing: 'bg-blue-100 text-blue-700',
      done: 'bg-emerald-100 text-emerald-700',
      late: 'bg-red-100 text-red-700',
    }[s] ?? 'bg-stone-100 text-stone-600'
  )
}

function statusLabel(s) {
  return { todo: 'Cần làm', doing: 'Đang làm', done: 'Hoàn thành', late: 'Trễ hạn' }[s] ?? s
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
    }[action] ?? action
  )
}
</script>
