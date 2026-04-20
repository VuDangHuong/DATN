<template>
  <div>
    <!-- Search bar -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
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
          v-if="search && totalFound > 0"
          class="px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-xl"
        >
          Tìm thấy {{ totalFound }} nhiệm vụ
        </span>
        <span
          v-else-if="search && totalFound === 0"
          class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-medium rounded-xl"
        >
          Không tìm thấy kết quả
        </span>
      </transition>
    </div>

    <!-- Columns -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <!-- ✅ Đúng -->
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
  isLeader: { type: Boolean, default: false },
  currentUserId: { type: Number },
})

const emit = defineEmits(['clickTask', 'changeStatus', 'deleteTask', 'taskMoved'])

const search = ref('')

const columns = [
  { status: 'todo', title: 'Cần làm', color: 'bg-slate-500', icon: '📋' },
  { status: 'doing', title: 'Đang làm', color: 'bg-blue-500', icon: '🔄' },
  { status: 'done', title: 'Hoàn thành', color: 'bg-emerald-500', icon: '✅' },
  { status: 'late', title: 'Trễ hạn', color: 'bg-red-500', icon: '⚠️' },
]

// Nhóm tasks theo status — reactive vì computed
const tasksByStatus = computed(() => {
  const map = { todo: [], doing: [], done: [], late: [] }
  for (const t of props.tasks) {
    if (map[t.status]) map[t.status].push(t)
  }
  return map
})

// Đếm kết quả search trên tất cả tasks
const totalFound = computed(() => {
  if (!search.value.trim()) return 0
  const q = search.value.toLowerCase()
  return props.tasks.filter(
    (t) =>
      t.title?.toLowerCase().includes(q) ||
      t.description?.toLowerCase().includes(q) ||
      t.assignee?.name?.toLowerCase().includes(q),
  ).length
})
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
