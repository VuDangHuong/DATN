<!-- src/components/lecturer/task/GroupProgressCard.vue -->
<template>
  <div
    @click="$emit('click', group)"
    class="bg-white rounded-2xl border border-stone-200 p-5 hover:shadow-lg hover:border-teal-300 transition cursor-pointer group"
  >
    <!-- Header: Icon + Name + Lock status -->
    <div class="flex items-start gap-3 mb-4">
      <div
        class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-400 to-cyan-500 flex items-center justify-center flex-shrink-0"
      >
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <p class="text-sm font-bold text-stone-800 group-hover:text-teal-700 transition truncate">
            {{ group.name }}
          </p>
          <span
            v-if="group.is_locked"
            class="px-1.5 py-0.5 bg-red-100 text-red-700 text-[9px] font-bold rounded uppercase flex items-center gap-0.5"
          >
            🔒 Khóa
          </span>
        </div>
        <p v-if="group.invitation_code" class="text-xs text-stone-400 font-mono mt-0.5">
          {{ group.invitation_code }}
        </p>
      </div>
    </div>

    <!-- Leader info -->
    <div v-if="group.leader" class="flex items-center gap-2 mb-3 p-2 bg-amber-50 rounded-lg">
      <div
        class="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
      >
        {{ group.leader.name?.charAt(0) }}
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-medium text-stone-700 truncate">👑 {{ group.leader.name }}</p>
        <p class="text-[10px] text-stone-400">Trưởng nhóm</p>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="mb-3">
      <div class="flex items-center justify-between mb-1.5">
        <span class="text-xs text-stone-500">Tiến độ công việc</span>
        <span class="text-sm font-bold" :class="progressColor">{{ percentage }}%</span>
      </div>
      <div class="h-2 bg-stone-100 rounded-full overflow-hidden">
        <div
          class="h-full rounded-full transition-all duration-500"
          :class="progressBarClass"
          :style="{ width: `${percentage}%` }"
        />
      </div>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-4 gap-2 text-center">
      <div class="bg-stone-50 rounded-lg p-2">
        <p class="text-sm font-bold text-stone-700">{{ stats.total }}</p>
        <p class="text-[10px] text-stone-400">Tổng</p>
      </div>
      <div class="bg-stone-50 rounded-lg p-2">
        <p class="text-sm font-bold text-blue-600">{{ stats.doing }}</p>
        <p class="text-[10px] text-stone-400">Đang làm</p>
      </div>
      <div class="bg-stone-50 rounded-lg p-2">
        <p class="text-sm font-bold text-emerald-600">{{ stats.done }}</p>
        <p class="text-[10px] text-stone-400">Xong</p>
      </div>
      <div class="bg-stone-50 rounded-lg p-2">
        <p class="text-sm font-bold" :class="stats.late > 0 ? 'text-red-600' : 'text-stone-400'">
          {{ stats.late }}
        </p>
        <p class="text-[10px] text-stone-400">Trễ</p>
      </div>
    </div>

    <!-- Member count + CTA -->
    <div class="mt-3 pt-3 border-t border-stone-100 flex items-center justify-between">
      <div class="flex items-center gap-1.5 text-xs text-stone-500">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
        <span>{{ memberCount }} thành viên</span>
      </div>
      <svg
        class="w-4 h-4 text-stone-300 group-hover:text-teal-500 transition"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  group: { type: Object, required: true },
  stats: { type: Object, default: () => ({ total: 0, todo: 0, doing: 0, done: 0, late: 0 }) },
})

defineEmits(['click'])

const memberCount = computed(() => props.group.member_count ?? props.group.members?.length ?? 0)

const percentage = computed(() => {
  if (!props.stats.total) return 0
  return Math.round((props.stats.done / props.stats.total) * 100)
})

const progressColor = computed(() => {
  const p = percentage.value
  if (p >= 80) return 'text-emerald-600'
  if (p >= 50) return 'text-blue-600'
  if (p >= 30) return 'text-amber-600'
  return 'text-stone-500'
})

const progressBarClass = computed(() => {
  const p = percentage.value
  if (p >= 80) return 'bg-gradient-to-r from-emerald-400 to-emerald-600'
  if (p >= 50) return 'bg-gradient-to-r from-blue-400 to-blue-600'
  if (p >= 30) return 'bg-gradient-to-r from-amber-400 to-amber-600'
  return 'bg-stone-300'
})
</script>
