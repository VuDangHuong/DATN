<!-- src/components/lecturer/sign/LecturerSignRejectForm.vue -->
<template>
  <div class="border border-red-200 rounded-xl p-4">
    <p class="text-sm font-semibold text-red-700 mb-2">Từ chối ký tài liệu</p>
    <textarea
      v-model="reason"
      rows="2"
      placeholder="Lý do từ chối (bắt buộc)..."
      class="w-full px-3 py-2 border border-stone-200 rounded-lg text-sm resize-none focus:ring-2 focus:ring-red-500 outline-none mb-2"
    />
    <button
      @click="handleReject"
      :disabled="!reason.trim() || store.rejecting"
      class="px-4 py-2 bg-red-600 text-white rounded-lg text-xs font-medium hover:bg-red-700 disabled:opacity-50 transition flex items-center gap-2"
    >
      <div
        v-if="store.rejecting"
        class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"
      />
      {{ store.rejecting ? 'Đang xử lý...' : 'Từ chối ký' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useLecturerSignStore } from '@/stores/lecturer/lecturerSignStore'

const store = useLecturerSignStore()
const reason = ref('')

async function handleReject() {
  const ok = await store.rejectRequest(reason.value)
  if (ok) reason.value = ''
}
</script>
