<!-- src/components/lecturer/sign/LecturerSignConfirmForm.vue -->
<template>
  <div class="border-2 border-dashed border-teal-200 rounded-xl p-5 bg-teal-50/50">
    <p class="text-sm font-semibold text-teal-800 mb-2 flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
        />
      </svg>
      Xác nhận ký số tài liệu
    </p>
    <p class="text-xs text-teal-700 mb-4 bg-teal-100 rounded-lg px-3 py-2">
      Sau khi xác nhận, hệ thống sẽ tự động tạo phiếu xác nhận ký số PDF và gửi Admin để phát hành
      cho sinh viên.
    </p>

    <!-- Checkbox xác nhận -->
    <label class="flex items-start gap-2.5 cursor-pointer mb-4">
      <input
        type="checkbox"
        v-model="confirmed"
        class="mt-0.5 rounded border-stone-300 text-teal-600"
      />
      <span class="text-xs text-stone-700">
        Tôi xác nhận đã đọc và kiểm tra tài liệu
        <strong>"{{ store.selectedRequest.document_category_label }}"</strong>
        của sinh viên <strong>{{ store.selectedRequest.requester?.name }}</strong>
        và đồng ý ký số xác nhận tài liệu này.
      </span>
    </label>

    <button
      @click="handleSign"
      :disabled="!confirmed || store.signing"
      class="w-full py-3 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 transition flex items-center justify-center gap-2"
    >
      <div
        v-if="store.signing"
        class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
      />
      <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      {{ store.signing ? 'Đang xử lý...' : '✅ Xác nhận ký số' }}
    </button>
  </div>
</template>

<script setup>
import { useLecturerSignStore } from '@/stores/lecturer/lecturerSignStore'
import { ref } from 'vue'

const store = useLecturerSignStore()
const confirmed = ref(false)

async function handleSign() {
  const ok = await store.signRequest()
  if (ok) confirmed.value = false
}
</script>
