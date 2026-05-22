<!-- src/components/admin/charts/LineChart.vue -->
<template>
  <div class="relative" style="height: 220px">
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
  data: { type: Object, default: () => ({ labels: [], datasets: [] }) },
})

const canvasRef = ref(null)
let chart = null

const isEmpty = computed(() => {
  const datasets = props.data?.datasets ?? []
  if (!datasets.length) return true
  return datasets.every((d) => (d.data ?? []).every((v) => !v))
})

function render() {
  if (!canvasRef.value) return
  if (chart) {
    chart.destroy()
    chart = null
  }
  if (isEmpty.value) return

  chart = new Chart(canvasRef.value, {
    type: 'line',
    data: {
      labels: props.data.labels ?? [],
      datasets: (props.data.datasets ?? []).map((ds) => ({
        label: ds.label,
        data: ds.data,
        borderColor: ds.color ?? '#6366f1',
        backgroundColor: (ds.color ?? '#6366f1') + '20',
        tension: 0.3,
        fill: true,
        borderWidth: 2,
        pointRadius: 3,
      })),
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
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
