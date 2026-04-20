<template>
  <div class="bg-slate-50 rounded-2xl border border-slate-200 flex flex-col">
    <div class="p-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="text-base">{{ icon }}</span>
        <h3 class="text-sm font-bold text-slate-700">{{ title }}</h3>
        <span
          class="px-2 py-0.5 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-500"
        >
          {{ localTasks.length }}
        </span>
      </div>
      <div class="w-2 h-2 rounded-full" :class="color" />
    </div>

    <div class="flex-1 px-3 pb-3 overflow-y-auto max-h-[60vh]">
      <!-- ✅ v-for thường + ref cho sortable -->
      <div ref="listRef" class="space-y-2 min-h-[60px]">
        <div
          v-for="task in localTasks"
          :key="task.id"
          :data-id="task.id"
          :class="{ hidden: !matchSearch(task) }"
          @click="emit('clickTask', task)"
          class="bg-white rounded-xl border border-slate-200 p-3.5 cursor-pointer hover:shadow-md hover:border-indigo-200 transition-all duration-200 group select-none"
        >
          <div class="flex items-center gap-1.5 mb-2">
            <span
              :class="priorityClass(task.priority)"
              class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
            >
              {{ task.priority }}
            </span>
            <span
              v-if="task.is_overdue"
              class="px-2 py-0.5 bg-red-50 text-red-600 rounded-full text-[10px] font-bold"
            >
              ⏰ Quá hạn
            </span>
          </div>
          <h4
            class="text-sm font-semibold text-slate-700 mb-2 line-clamp-2 group-hover:text-indigo-700"
            v-html="highlightText(task.title)"
          />
          <p v-if="task.description" class="text-xs text-slate-400 mb-3 line-clamp-2">
            {{ task.description }}
          </p>
          <div class="flex items-center justify-between">
            <div v-if="task.assignee" class="flex items-center gap-1.5">
              <div
                class="w-5 h-5 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 flex items-center justify-center text-[9px] font-bold text-white"
              >
                {{ task.assignee.name?.charAt(0) }}
              </div>
              <span class="text-xs text-slate-500 truncate max-w-[80px]">{{
                task.assignee.name
              }}</span>
            </div>
            <div v-else class="text-xs text-slate-400 italic">Chưa giao</div>
            <span
              class="text-[10px] text-slate-400"
              :class="{ 'text-red-500 font-semibold': task.is_overdue }"
            >
              {{ formatDeadline(task.deadline) }}
            </span>
          </div>
          <div
            class="mt-2 pt-2 border-t border-slate-100 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <template v-if="canChangeStatus(task)">
              <button
                v-if="task.status === 'todo'"
                @click.stop="emit('changeStatus', task.id, 'doing')"
                class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"
              >
                ▶ Bắt đầu
              </button>
              <button
                v-if="task.status === 'doing'"
                @click.stop="emit('changeStatus', task.id, 'done')"
                class="action-btn bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
              >
                ✓ Hoàn thành
              </button>
              <button
                v-if="task.status === 'done' || task.status === 'late'"
                @click.stop="emit('changeStatus', task.id, 'doing')"
                class="action-btn bg-amber-50 text-amber-600 hover:bg-amber-100"
              >
                ↩ Mở lại
              </button>
            </template>
            <button
              v-if="isLeader"
              @click.stop="emit('deleteTask', task.id)"
              class="action-btn bg-red-50 text-red-500 hover:bg-red-100 ml-auto"
            >
              🗑
            </button>
          </div>
        </div>
      </div>

      <div v-if="localTasks.length === 0" class="py-8 text-center">
        <p class="text-xs text-slate-400">Không có task</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import Sortable from 'sortablejs'

const props = defineProps({
  title: { type: String },
  color: { type: String },
  icon: { type: String },
  status: { type: String },
  tasks: { type: Array, default: () => [] },
  isLeader: { type: Boolean, default: false },
  currentUserId: { type: Number },
  search: { type: String, default: '' },
})

const emit = defineEmits(['changeStatus', 'clickTask', 'deleteTask', 'taskMoved'])

const localTasks = ref([])
const listRef = ref(null)
let sortable = null

watch(
  () => props.tasks,
  (val) => {
    localTasks.value = val ? [...val] : []
  },
  { immediate: true, deep: true },
)

// Khởi tạo Sortable sau khi DOM ready
onMounted(() => {
  if (!listRef.value) return
  sortable = Sortable.create(listRef.value, {
    group: 'tasks',
    animation: 200,
    ghostClass: 'drag-ghost',
    chosenClass: 'drag-chosen',
    onAdd(event) {
      // Kéo từ column khác sang
      const taskId = Number(event.item.dataset.id)
      emit('taskMoved', { taskId, newStatus: props.status })
    },
  })
})

onUnmounted(() => {
  sortable?.destroy()
})

function matchSearch(task) {
  if (!props.search?.trim()) return true
  const q = props.search.toLowerCase()
  return (
    task.title?.toLowerCase().includes(q) ||
    task.description?.toLowerCase().includes(q) ||
    task.assignee?.name?.toLowerCase().includes(q)
  )
}

function highlightText(text) {
  if (!props.search?.trim() || !text) return text
  const escaped = props.search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  return text.replace(
    new RegExp(`(${escaped})`, 'gi'),
    '<mark class="bg-yellow-200 text-yellow-900 rounded px-0.5">$1</mark>',
  )
}

function canChangeStatus(task) {
  return task.assignee?.id === props.currentUserId || props.isLeader
}

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-700',
      high: 'bg-orange-100 text-orange-700',
      medium: 'bg-blue-100 text-blue-700',
      low: 'bg-slate-100 text-slate-500',
    }[p] || ''
  )
}

function formatDeadline(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}
</script>

<style scoped>
.action-btn {
  @apply px-2.5 py-1 rounded-lg text-[10px] font-bold transition-colors;
}
.drag-ghost {
  @apply opacity-40 bg-indigo-50 border-2 border-dashed border-indigo-300 rounded-xl;
}
.drag-chosen {
  @apply ring-2 ring-indigo-400 ring-offset-1;
}
</style>
