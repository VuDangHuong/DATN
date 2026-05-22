<!-- src/components/admin/charts/DonutChart.vue -->
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
    type: 'doughnut',
    data: {
      labels: props.data.labels ?? [],
      datasets: [
        {
          data: props.data.values ?? [],
          backgroundColor: props.data.colors ?? ['#6366f1', '#10b981', '#f59e0b', '#ef4444'],
          borderWidth: 2,
          borderColor: '#fff',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '60%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { boxWidth: 10, font: { size: 11 }, padding: 8 },
        },
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
