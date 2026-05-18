<template>
  <div>
    <div class="space-y-3 mb-6">
      <!-- Search bar -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-48">
          <svg
            class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"
            />
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Tìm kiếm nhiệm vụ..."
            class="w-full pl-9 pr-9 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
          <button
            v-if="search"
            @click="search = ''"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <transition name="fade">
          <span
            v-if="(search || selectedAssigneeIds.length) && totalFiltered > 0"
            class="px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-xl"
          >
            Tìm thấy {{ totalFiltered }} nhiệm vụ
          </span>
          <span
            v-else-if="(search || selectedAssigneeIds.length) && totalFiltered === 0"
            class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-xl"
          >
            Không tìm thấy kết quả
          </span>
        </transition>
      </div>

      <!-- Member filter -->
      <div v-if="members.length" class="flex items-center gap-2 flex-wrap">
        <span class="text-xs font-medium text-slate-500 uppercase tracking-wide"> Lọc theo: </span>

        <!-- "Tất cả" -->
        <button
          @click="clearMemberFilter"
          class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium transition border"
          :class="
            selectedAssigneeIds.length === 0
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
          "
        >
          <span class="text-sm">👥</span>
          Tất cả
        </button>

        <!-- Chip cho từng thành viên -->
        <button
          v-for="m in members"
          :key="m.id"
          @click="toggleMember(m.id)"
          class="flex items-center gap-1.5 pl-1 pr-2.5 py-1 rounded-full text-xs font-medium transition border"
          :class="
            selectedAssigneeIds.includes(m.id)
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
          "
        >
          <!-- Avatar -->
          <img
            v-if="m.avatar_url || m.avatar"
            :src="m.avatar_url || m.avatar"
            :alt="m.name"
            class="w-6 h-6 rounded-full object-cover border-2"
            :class="selectedAssigneeIds.includes(m.id) ? 'border-white' : 'border-slate-100'"
          />
          <div
            v-else
            class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold border-2"
            :class="
              selectedAssigneeIds.includes(m.id)
                ? 'bg-white text-indigo-600 border-white'
                : 'bg-slate-100 text-slate-600 border-slate-100'
            "
          >
            {{ getInitial(m.name) }}
          </div>

          <span class="truncate max-w-[120px]">{{ m.name }}</span>

          <!-- Badge số task -->
          <span
            class="text-[10px] font-bold px-1.5 rounded-full ml-0.5"
            :class="
              selectedAssigneeIds.includes(m.id)
                ? 'bg-white/30 text-white'
                : 'bg-slate-100 text-slate-500'
            "
          >
            {{ taskCountByMember(m.id) }}
          </span>
        </button>

        <!-- Task chưa giao (unassigned) -->
        <button
          v-if="unassignedCount > 0"
          @click="toggleMember(null)"
          class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-medium transition border"
          :class="
            selectedAssigneeIds.includes(null)
              ? 'bg-slate-600 text-white border-slate-600'
              : 'bg-white text-slate-500 border-slate-200 border-dashed hover:bg-slate-50'
          "
        >
          <span class="text-sm">❓</span>
          Chưa giao
          <span
            class="text-[10px] font-bold px-1.5 rounded-full ml-0.5"
            :class="
              selectedAssigneeIds.includes(null)
                ? 'bg-white/30 text-white'
                : 'bg-slate-100 text-slate-500'
            "
          >
            {{ unassignedCount }}
          </span>
        </button>
      </div>
    </div>

    <!-- ─── Columns ─── -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
      <KanbanColumn
        v-for="col in columns"
        :key="col.status"
        :title="col.title"
        :color="col.color"
        :icon="col.icon"
        :status="col.status"
        :tasks="tasksByStatus[col.status]"
        :is-leader="isLeader"
        :current-user-id="currentUserId"
        :search="search"
        @click-task="emit('clickTask', $event)"
        @change-status="(taskId, status) => emit('changeStatus', taskId, status)"
        @delete-task="emit('deleteTask', $event)"
        @task-moved="emit('taskMoved', $event)"
      />
    </div>
  </div>
</template>

<script setup>
import KanbanColumn from '@/components/students/KanbanColumn.vue'
import { ref, computed } from 'vue'

const props = defineProps({
  tasks: { type: Array, default: () => [] },
  members: { type: Array, default: () => [] },
  isLeader: { type: Boolean, default: false },
  currentUserId: { type: Number },
})

const emit = defineEmits(['clickTask', 'changeStatus', 'deleteTask', 'taskMoved'])

const search = ref('')
const selectedAssigneeIds = ref([]) //Array các user_id đang được filter

const columns = [
  { status: 'todo', title: 'Cần làm', color: 'bg-slate-500', icon: '📋' },
  { status: 'doing', title: 'Đang làm', color: 'bg-blue-500', icon: '🔄' },
  { status: 'pending_review', title: 'Chờ duyệt', color: 'bg-amber-500', icon: '⏳' },
  { status: 'done', title: 'Hoàn thành', color: 'bg-emerald-500', icon: '✅' },
  { status: 'late', title: 'Trễ hạn', color: 'bg-red-500', icon: '⚠️' },
]

// ─── Filter logic ─────────────────────────────
const filteredTasks = computed(() => {
  let result = props.tasks

  // Filter by member
  if (selectedAssigneeIds.value.length > 0) {
    result = result.filter((t) => {
      // null trong selectedAssigneeIds = filter "chưa giao"
      if (selectedAssigneeIds.value.includes(null) && !t.assignee_id && !t.assignee) {
        return true
      }
      const assigneeId = t.assignee?.id ?? t.assignee_id
      return selectedAssigneeIds.value.includes(assigneeId)
    })
  }

  // Filter by search
  if (search.value.trim()) {
    const q = search.value.toLowerCase()
    result = result.filter(
      (t) =>
        t.title?.toLowerCase().includes(q) ||
        t.description?.toLowerCase().includes(q) ||
        t.assignee?.name?.toLowerCase().includes(q),
    )
  }

  return result
})

// ─── Tasks by status (dùng filteredTasks) ─────
const tasksByStatus = computed(() => {
  const map = {
    todo: [],
    doing: [],
    pending_review: [],
    done: [],
    late: [],
  }
  for (const t of filteredTasks.value) {
    if (map[t.status]) map[t.status].push(t)
  }
  return map
})

const totalFiltered = computed(() => filteredTasks.value.length)

// ─── Helpers ──────────────────────────────────
function toggleMember(memberId) {
  const idx = selectedAssigneeIds.value.indexOf(memberId)
  if (idx >= 0) {
    selectedAssigneeIds.value.splice(idx, 1)
  } else {
    selectedAssigneeIds.value.push(memberId)
  }
}

function clearMemberFilter() {
  selectedAssigneeIds.value = []
}

function taskCountByMember(memberId) {
  return props.tasks.filter((t) => {
    const id = t.assignee?.id ?? t.assignee_id
    return id === memberId
  }).length
}

const unassignedCount = computed(
  () => props.tasks.filter((t) => !t.assignee_id && !t.assignee).length,
)

function getInitial(name) {
  return name?.[0]?.toUpperCase() ?? '?'
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
