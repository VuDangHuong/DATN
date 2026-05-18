<!-- src/components/students/task/TaskStats.vue -->
<template>
  <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
    <div
      v-for="(item, key) in items"
      :key="key"
      class="bg-white rounded-xl border border-slate-200 p-4 text-center"
    >
      <p class="text-2xl font-bold" :class="item.color">{{ stats[key] || 0 }}</p>
      <p class="text-xs text-slate-500 mt-1">{{ item.label }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  stats: { type: Object, default: () => ({}) },
})

const items = {
  total: { label: 'Tổng', color: 'text-slate-700' },
  todo: { label: 'Cần làm', color: 'text-slate-500' },
  doing: { label: 'Đang làm', color: 'text-blue-600' },
  pending_review: { label: 'Chờ duyệt', color: 'text-amber-600' },
  done: { label: 'Hoàn thành', color: 'text-emerald-600' },
  late: { label: 'Trễ hạn', color: 'text-red-600' },
}
</script>
