<!-- src/components/lecturer/dashboard/BarChart.vue -->
<template>
  <div class="space-y-2">
    <div v-if="!data.length" class="text-center py-8 text-sm text-stone-400">Chưa có dữ liệu</div>
    <div v-for="item in data" :key="item.label" class="flex items-center gap-2">
      <span class="text-base text-stone-600 w-20 truncate flex-shrink-0">{{ item.label }}</span>
      <div class="flex-1 h-6 bg-stone-100 rounded-md overflow-hidden relative">
        <div
          class="h-full transition-all duration-500 rounded-md"
          :style="{
            width: maxValue > 0 ? (item.value / maxValue) * 100 + '%' : '0%',
            background: 'linear-gradient(to right, #14b8a6, #0d9488)',
          }"
        />
        <span
          class="absolute inset-0 flex items-center px-2 text-base font-semibold"
          :class="item.value > maxValue * 0.3 ? 'text-white' : 'text-stone-700'"
        >
          {{ item.value }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: { type: Array, default: () => [] },
})

const maxValue = computed(() => {
  return Math.max(...props.data.map((d) => d.value), 1)
})
</script>
