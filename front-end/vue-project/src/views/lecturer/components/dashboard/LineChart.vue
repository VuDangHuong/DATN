<!-- src/components/lecturer/dashboard/LineChart.vue -->
<template>
  <div class="w-full">
    <!-- Legend -->
    <div class="flex items-center gap-4 mb-3">
      <div v-for="ds in data.datasets" :key="ds.name" class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-sm" :style="{ background: ds.color }" />
        <span class="text-xs text-stone-600">{{ ds.name }}</span>
      </div>
    </div>

    <!-- SVG Chart -->
    <div class="relative">
      <svg :viewBox="`0 0 ${width} ${height}`" class="w-full h-48">
        <!-- Y-axis grid lines -->
        <g>
          <line
            v-for="i in 5"
            :key="i"
            :x1="padding.left"
            :x2="width - padding.right"
            :y1="getY((maxValue * (i - 1)) / 4)"
            :y2="getY((maxValue * (i - 1)) / 4)"
            stroke="#f1f5f9"
            stroke-width="1"
          />
          <text
            v-for="i in 5"
            :key="`y-${i}`"
            :x="padding.left - 8"
            :y="getY((maxValue * (i - 1)) / 4) + 4"
            text-anchor="end"
            font-size="10"
            fill="#94a3b8"
          >
            {{ Math.round((maxValue * (i - 1)) / 4) }}
          </text>
        </g>

        <!-- X-axis labels -->
        <text
          v-for="(label, i) in data.labels"
          :key="`x-${i}`"
          :x="getX(i)"
          :y="height - padding.bottom + 16"
          text-anchor="middle"
          font-size="10"
          fill="#64748b"
        >
          {{ label }}
        </text>

        <!-- Lines for each dataset -->
        <g v-for="ds in data.datasets" :key="ds.name">
          <!-- Area fill -->
          <path :d="buildAreaPath(ds.data)" :fill="ds.color" fill-opacity="0.1" />
          <!-- Line -->
          <path
            :d="buildLinePath(ds.data)"
            :stroke="ds.color"
            stroke-width="2"
            fill="none"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
          <!-- Dots -->
          <circle
            v-for="(val, idx) in ds.data"
            :key="idx"
            :cx="getX(idx)"
            :cy="getY(val)"
            r="3"
            :fill="ds.color"
          />
          <!-- Tooltip area -->
          <g v-for="(val, idx) in ds.data" :key="`hover-${idx}`">
            <circle
              :cx="getX(idx)"
              :cy="getY(val)"
              r="10"
              fill="transparent"
              class="cursor-pointer peer"
            >
              <title>{{ ds.name }}: {{ val }}</title>
            </circle>
          </g>
        </g>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: { type: Object, required: true },
})

const width = 600
const height = 220
const padding = { top: 10, right: 10, bottom: 30, left: 30 }

const chartWidth = computed(() => width - padding.left - padding.right)
const chartHeight = computed(() => height - padding.top - padding.bottom)

const maxValue = computed(() => {
  const allValues = props.data.datasets.flatMap((ds) => ds.data)
  const max = Math.max(...allValues, 1)
  return Math.max(4, Math.ceil(max * 1.2)) // 20% headroom
})

function getX(index) {
  const len = props.data.labels.length
  if (len <= 1) return padding.left + chartWidth.value / 2
  return padding.left + (index / (len - 1)) * chartWidth.value
}

function getY(value) {
  return padding.top + chartHeight.value - (value / maxValue.value) * chartHeight.value
}

function buildLinePath(values) {
  return values
    .map((v, i) => {
      const cmd = i === 0 ? 'M' : 'L'
      return `${cmd} ${getX(i)} ${getY(v)}`
    })
    .join(' ')
}

function buildAreaPath(values) {
  const line = buildLinePath(values)
  const last = values.length - 1
  return `${line} L ${getX(last)} ${getY(0)} L ${getX(0)} ${getY(0)} Z`
}
</script>
