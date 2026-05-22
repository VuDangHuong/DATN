<!-- src/components/lecturer/task/MemberProgressCard.vue -->
<template>
  <div
    @click="$emit('click', member)"
    class="bg-white rounded-2xl border border-stone-200 p-5 hover:shadow-lg hover:border-teal-300 transition cursor-pointer group"
  >
    <!-- Header: Avatar + Name + Leader badge -->
    <div class="flex items-start gap-3 mb-4">
      <!-- Avatar -->
      <div class="relative flex-shrink-0">
        <img
          v-if="member.avatar_url || member.avatar"
          :src="member.avatar_url || member.avatar"
          class="w-12 h-12 rounded-full object-cover border-2"
          :class="isLeader ? 'border-amber-400' : 'border-stone-100'"
          :alt="member.name"
        />
        <div
          v-else
          class="w-12 h-12 rounded-full flex items-center justify-center text-base font-bold text-white border-2"
          :class="
            isLeader
              ? 'bg-gradient-to-br from-amber-400 to-orange-500 border-amber-400'
              : 'bg-gradient-to-br from-teal-400 to-cyan-500 border-stone-100'
          "
        >
          {{ initial }}
        </div>
        <!-- Crown badge for leader -->
        <span
          v-if="isLeader"
          class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-amber-400 flex items-center justify-center text-xs shadow"
          title="Trưởng nhóm"
        >
          <SvgIcon name="crown" class="w-3.5 h-3.5 text-amber-800" />
        </span>
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <p class="text-sm font-bold text-stone-800 group-hover:text-teal-700 transition truncate">
            {{ member.name }}
          </p>
          <span
            v-if="isLeader"
            class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[9px] font-bold rounded uppercase"
          >
            Trưởng
          </span>
        </div>
        <p class="text-xs text-stone-400 font-mono mt-0.5">{{ member.code }}</p>
      </div>
    </div>

    <!-- Progress bar -->
    <div class="mb-3">
      <div class="flex items-center justify-between mb-1.5">
        <span class="text-xs text-stone-500">Tiến độ hoàn thành</span>
        <span class="text-sm font-bold" :class="progressColor"> {{ percentage }}% </span>
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

    <!-- CTA hint -->
    <div class="mt-3 pt-3 border-t border-stone-100 flex items-center justify-between">
      <span class="text-[11px] text-stone-400">
        {{ stats.total === 0 ? 'Chưa có công việc' : 'Click để xem chi tiết' }}
      </span>
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
import SvgIcon from '@/components/icons/SVG.vue'
import { computed } from 'vue'

const props = defineProps({
  member: { type: Object, required: true },
  // stats: { total, todo, doing, done, late }
  stats: { type: Object, default: () => ({ total: 0, todo: 0, doing: 0, done: 0, late: 0 }) },
})

defineEmits(['click'])

const isLeader = computed(() => props.member.role === 'leader')

const initial = computed(() => props.member.name?.charAt(0)?.toUpperCase() ?? '?')

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
