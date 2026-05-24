<!-- src/components/lecturer/sign/PendingDeactivationBanner.vue -->
<!--
  Component hiển thị banner ở trang SignProfileView khi GV có request pending.
  Cách dùng:
    <PendingDeactivationBanner :request="pendingRequest"/>
-->
<template>
  <div v-if="request" class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5">
    <div class="flex items-start gap-3">
      <div class="w-10 h-10 rounded-xl bg-amber-200 flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      </div>
      <div class="flex-1 min-w-0">
        <h4 class="text-sm font-bold text-amber-900">Yêu cầu vô hiệu hóa đang chờ duyệt</h4>
        <p class="text-base text-amber-800 mt-1"><strong>Lý do:</strong> {{ request.reason }}</p>
        <p class="text-base text-amber-700 mt-2">
          ⚠️ Bạn không thể ký tài liệu trong thời gian chờ duyệt. Gửi lúc
          {{ formatDate(request.created_at) }}.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  request: { type: Object, default: null },
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
