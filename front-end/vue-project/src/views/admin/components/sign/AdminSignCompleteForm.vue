<!-- src/components/admin/sign/AdminSignCompleteForm.vue -->
<template>
  <div class="border border-emerald-200 rounded-xl p-4 bg-emerald-50">
    <p class="text-sm font-semibold text-emerald-700 mb-1">Phát hành tài liệu đã ký</p>
    <p class="text-xs text-emerald-600 mb-3">GV đã ký xong. Xác nhận để sinh viên có thể tải về.</p>
    <button
      @click="store.completeRequest()"
      :disabled="store.completing"
      class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50 transition flex items-center gap-2"
    >
      <div
        v-if="store.completing"
        class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
      />
      {{ store.completing ? 'Đang xử lý...' : '✅ Phát hành cho sinh viên' }}
    </button>
  </div>
</template>

<script setup>
import { useAdminSignStore } from '@/stores/admin/sign/adminSignStore'
const store = useAdminSignStore()
</script>
