<!-- src/components/KanbanColumn.vue -->
<template>
  <div class="bg-slate-50 rounded-2xl border border-slate-200 flex flex-col">
    <!-- Column header -->
    <div class="p-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="text-base">{{ icon }}</span>
        <h3 class="text-sm font-bold text-slate-700">{{ title }}</h3>
        <span
          class="px-2 py-0.5 bg-white border border-slate-200 rounded-full text-xs font-bold text-slate-500"
        >
          {{ tasks.length }}
        </span>
      </div>
      <div class="w-2 h-2 rounded-full" :class="color" />
    </div>

    <!-- Tasks list -->
    <div class="flex-1 px-3 pb-3 space-y-2 overflow-y-auto max-h-[60vh]">
      <div
        v-for="task in tasks"
        :key="task.id"
        @click="$emit('clickTask', task)"
        class="bg-white rounded-xl border border-slate-200 p-3.5 cursor-pointer hover:shadow-md hover:border-indigo-200 transition-all duration-200 group"
      >
        <!-- Priority + Overdue badges -->
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

        <!-- Title -->
        <h4
          class="text-sm font-semibold text-slate-700 mb-2 line-clamp-2 group-hover:text-indigo-700 transition-colors"
        >
          {{ task.title }}
        </h4>

        <!-- Description preview -->
        <p v-if="task.description" class="text-xs text-slate-400 mb-3 line-clamp-2">
          {{ task.description }}
        </p>

        <!-- Assignee + Deadline -->
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

        <!-- Actions (chỉ hiện khi hover) -->
        <div
          class="mt-2 pt-2 border-t border-slate-100 flex gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
        >
          <!-- Status buttons -->
          <template v-if="canChangeStatus(task)">
            <button
              v-if="task.status === 'todo'"
              @click.stop="$emit('changeStatus', task.id, 'doing')"
              class="action-btn bg-blue-50 text-blue-600 hover:bg-blue-100"
            >
              ▶ Bắt đầu
            </button>
            <button
              v-if="task.status === 'doing'"
              @click.stop="$emit('changeStatus', task.id, 'done')"
              class="action-btn bg-emerald-50 text-emerald-600 hover:bg-emerald-100"
            >
              ✓ Hoàn thành
            </button>
            <button
              v-if="task.status === 'done' || task.status === 'late'"
              @click.stop="$emit('changeStatus', task.id, 'doing')"
              class="action-btn bg-amber-50 text-amber-600 hover:bg-amber-100"
            >
              ↩ Mở lại
            </button>
          </template>

          <!-- Delete (leader only) -->
          <button
            v-if="isLeader"
            @click.stop="$emit('deleteTask', task.id)"
            class="action-btn bg-red-50 text-red-500 hover:bg-red-100 ml-auto"
          >
            🗑
          </button>
        </div>
      </div>

      <!-- Empty column -->
      <div v-if="tasks.length === 0" class="py-8 text-center">
        <p class="text-xs text-slate-400">Không có task</p>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: String,
  color: String,
  icon: String,
  tasks: { type: Array, default: () => [] },
  isLeader: Boolean,
  currentUserId: Number,
})

defineEmits(['changeStatus', 'clickTask', 'deleteTask'])

function canChangeStatus(task) {
  return task.assignee?.id === props.currentUserId || props.isLeader
}

function priorityClass(p) {
  const map = {
    urgent: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-blue-100 text-blue-700',
    low: 'bg-slate-100 text-slate-500',
  }
  return map[p] || ''
}

function formatDeadline(d) {
  if (!d) return ''
  const date = new Date(d)
  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}
</script>

<style scoped>
.action-btn {
  @apply px-2.5 py-1 rounded-lg text-[10px] font-bold transition-colors;
}
</style>
