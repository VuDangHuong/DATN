<!-- src/components/admin/charts/BarChart.vue -->
<template>
  <div class="relative" style="height: 200px">
    <canvas ref="canvasRef" />
    <div
      v-if="isEmpty"
      class="absolute inset-0 flex items-center justify-center text-stone-400 text-xs"
    >
      Chưa có dữ liệu
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick, computed } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
  data: { type: Object, default: () => ({ labels: [], values: [], colors: [] }) },
})

const canvasRef = ref(null)
let chart = null

const isEmpty = computed(() => {
  const values = props.data?.values ?? []
  return !values.length || values.every((v) => !v)
})

function render() {
  if (!canvasRef.value) return
  if (chart) {
    chart.destroy()
    chart = null
  }
  if (isEmpty.value) return

  chart = new Chart(canvasRef.value, {
    type: 'bar',
    data: {
      labels: props.data.labels ?? [],
      datasets: [
        {
          data: props.data.values ?? [],
          backgroundColor: props.data.colors ?? [
            '#6366f1',
            '#8b5cf6',
            '#ec4899',
            '#f43f5e',
            '#f97316',
          ],
          borderRadius: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { font: { size: 10 } }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { font: { size: 10 } } },
      },
    },
  })
}

onMounted(async () => {
  await nextTick()
  render()
})
onBeforeUnmount(() => {
  chart?.destroy()
})
watch(
  () => props.data,
  async () => {
    await nextTick()
    render()
  },
  { deep: true },
)
</script>
