<!-- src/components/lecturer/dashboard/DonutChart.vue -->
<template>
  <div class="flex items-center gap-4">
    <!-- SVG Donut -->
    <div class="relative flex-shrink-0">
      <svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
        <!-- Background circle -->
        <circle
          :cx="cx"
          :cy="cy"
          :r="radius"
          fill="none"
          stroke="#f1f5f9"
          :stroke-width="strokeWidth"
        />

        <!-- Segments -->
        <circle
          v-for="(seg, idx) in segments"
          :key="idx"
          :cx="cx"
          :cy="cy"
          :r="radius"
          fill="none"
          :stroke="seg.color"
          :stroke-width="strokeWidth"
          :stroke-dasharray="`${seg.length} ${circumference}`"
          :stroke-dashoffset="seg.offset"
          stroke-linecap="round"
          :transform="`rotate(-90 ${cx} ${cy})`"
          class="transition-all"
        />
      </svg>
      <!-- Center text -->
      <div class="absolute inset-0 flex flex-col items-center justify-center">
        <p class="text-2xl font-bold text-stone-800">{{ total }}</p>
        <p class="text-[10px] text-stone-400">Tổng</p>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex-1 space-y-2 min-w-0">
      <div v-for="seg in data" :key="seg.label" class="flex items-center gap-2">
        <span class="w-3 h-3 rounded-sm flex-shrink-0" :style="{ background: seg.color }" />
        <span class="text-xs text-stone-600 flex-1 truncate">{{ seg.label }}</span>
        <span class="text-xs font-semibold text-stone-700">{{ seg.value }}</span>
        <span class="text-[10px] text-stone-400 w-10 text-right">
          {{ total > 0 ? Math.round((seg.value / total) * 100) : 0 }}%
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: { type: Array, default: () => [] },
  size: { type: Number, default: 140 },
})

const cx = computed(() => props.size / 2)
const cy = computed(() => props.size / 2)
const radius = computed(() => props.size / 2 - 12)
const strokeWidth = 16
const circumference = computed(() => 2 * Math.PI * radius.value)

const total = computed(() => props.data.reduce((sum, s) => sum + (s.value || 0), 0))

const segments = computed(() => {
  if (total.value === 0) return []

  let cumulative = 0
  return props.data
    .filter((s) => s.value > 0)
    .map((s) => {
      const fraction = s.value / total.value
      const length = fraction * circumference.value
      const offset = -cumulative
      cumulative += length
      return {
        ...s,
        length,
        offset,
      }
    })
})
</script>
