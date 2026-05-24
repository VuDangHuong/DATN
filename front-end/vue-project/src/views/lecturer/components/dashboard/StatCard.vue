<!-- src/components/lecturer/dashboard/StatCard.vue -->
<template>
  <component
    :is="link ? 'router-link' : 'div'"
    :to="link || undefined"
    class="bg-white rounded-2xl border border-stone-200 p-4 transition"
    :class="link ? 'hover:border-teal-300 hover:shadow-sm cursor-pointer' : ''"
  >
    <div class="flex items-start justify-between mb-2">
      <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl" :class="iconBg">
        <SvgIcon :name="icon" class="w-5 h-5" />
      </div>
      <!-- <svg
        v-if="link"
        class="w-4 h-4 text-stone-300"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg> -->
    </div>
    <p class="text-2xl font-bold text-stone-800">{{ formatNumber(value) }}</p>
    <p class="text-base text-stone-500 mt-0.5">{{ label }}</p>
  </component>
</template>

<script setup>
import SvgIcon from '@/components/icons/SVG.vue'
import { computed } from 'vue'

const props = defineProps({
  icon: { type: String, required: true },
  label: { type: String, required: true },
  value: { type: [Number, String], default: 0 },
  color: { type: String, default: 'teal' },
  link: { type: String, default: null },
})

const iconBg = computed(() => {
  return (
    {
      teal: 'bg-teal-100',
      blue: 'bg-blue-100',
      purple: 'bg-purple-100',
      amber: 'bg-amber-100',
      indigo: 'bg-indigo-100',
      emerald: 'bg-emerald-100',
      red: 'bg-red-100',
    }[props.color] || 'bg-stone-100'
  )
})

function formatNumber(n) {
  if (n === null || n === undefined) return '0'
  return new Intl.NumberFormat('vi-VN').format(n)
}
</script>
