<!-- src/components/lecturer/sign/LecturerSignDetailModal.vue -->
<template>
  <Teleport to="body">
    <div
      v-if="store.selectedRequest"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="handleClose" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
      >
        <!-- Header -->
        <div class="p-6 border-b border-stone-100 flex items-start justify-between flex-shrink-0">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h3 class="text-lg font-bold text-stone-900">Chi tiết yêu cầu ký số</h3>
              <span
                class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                :class="statusBadgeClass(store.selectedRequest.status)"
              >
                {{ store.selectedRequest.status_label }}
              </span>
            </div>
            <p class="text-xs text-stone-400">
              Yêu cầu #{{ store.selectedRequest.id }} ·
              {{ formatDate(store.selectedRequest.created_at) }}
            </p>
          </div>
          <button @click="handleClose" class="p-1.5 hover:bg-stone-100 rounded-lg transition">
            <svg
              class="w-5 h-5 text-stone-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-5">
          <!-- Thông tin sinh viên -->
          <div class="bg-stone-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-3">
              Thông tin sinh viên
            </p>
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700"
              >
                {{ store.selectedRequest.requester?.name?.charAt(0) }}
              </div>
              <div>
                <p class="font-semibold text-stone-900">
                  {{ store.selectedRequest.requester?.name }}
                </p>
                <p class="text-xs text-stone-400 font-mono">
                  {{ store.selectedRequest.requester?.code }} ·
                  {{ store.selectedRequest.requester?.email }}
                </p>
              </div>
            </div>
          </div>

          <!-- Thông tin tài liệu -->
          <div class="bg-stone-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-3">
              Thông tin tài liệu
            </p>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <p class="text-xs text-stone-400">Loại tài liệu</p>
                <p class="font-medium text-violet-700 mt-0.5">
                  {{
                    store.selectedRequest.document_category_label ??
                    store.selectedRequest.document_category
                  }}
                </p>
              </div>
              <div>
                <p class="text-xs text-stone-400">Định dạng</p>
                <p class="font-medium text-stone-700 mt-0.5 uppercase">
                  {{ store.selectedRequest.document_type }}
                </p>
              </div>
              <div>
                <p class="text-xs text-stone-400">Lớp</p>
                <p class="font-medium text-stone-700 mt-0.5">
                  {{ store.selectedRequest.class_model?.name }}
                </p>
              </div>
              <div>
                <p class="text-xs text-stone-400">Thời gian chuyển</p>
                <p class="font-medium text-stone-700 mt-0.5">
                  {{ formatDate(store.selectedRequest.forwarded_at) }}
                </p>
              </div>
            </div>
            <!-- Tải file gốc -->
            <button
              @click="store.previewFile()"
              :disabled="store.previewing"
              class="mt-3 flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 rounded-lg text-xs font-medium text-stone-700 hover:bg-stone-50 transition disabled:opacity-50"
            >
              <div
                v-if="store.previewing"
                class="w-3.5 h-3.5 border-2 border-stone-300 border-t-stone-600 rounded-full animate-spin"
              />
              <svg
                v-else
                class="w-3.5 h-3.5 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
              </svg>
              {{ store.previewing ? 'Đang tải...' : 'Tải file gốc để ký' }}
            </button>
          </div>

          <!-- Audit log -->
          <div v-if="store.selectedRequest.logs?.length">
            <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
              Lịch sử xử lý
            </p>
            <div class="space-y-2">
              <div
                v-for="log in store.selectedRequest.logs"
                :key="log.id"
                class="flex items-start gap-2 text-xs"
              >
                <div class="w-1.5 h-1.5 rounded-full bg-teal-400 mt-1.5 flex-shrink-0" />
                <div>
                  <span class="font-medium text-stone-700">{{ log.actor?.name }}</span>
                  <span class="text-stone-400"> · {{ log.action_label ?? log.action }}</span>
                  <span class="text-stone-400"> · {{ formatDate(log.created_at) }}</span>
                  <p v-if="log.note" class="text-stone-500 italic mt-0.5">"{{ log.note }}"</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Form xác nhận ký số -->
          <LecturerSignConfirmForm v-if="store.canSign" />

          <!-- Form từ chối -->
          <LecturerSignRejectForm v-if="store.canSign" />

          <!-- Đã ký — chờ Admin -->
          <div
            v-if="store.selectedRequest.status === 'signed'"
            class="border border-blue-200 rounded-xl p-4 bg-blue-50"
          >
            <p class="text-sm font-semibold text-blue-700 mb-1">✅ Đã ký thành công</p>
            <p class="text-xs text-blue-600">
              Đã ký lúc {{ formatDate(store.selectedRequest.signed_at) }}. Đang chờ Admin phát hành
              cho sinh viên.
            </p>
          </div>

          <!-- Hoàn thành -->
          <div
            v-if="store.selectedRequest.status === 'completed'"
            class="border border-emerald-200 rounded-xl p-4 bg-emerald-50"
          >
            <p class="text-sm font-semibold text-emerald-700 mb-1">🎉 Tài liệu đã phát hành</p>
            <p class="text-xs text-emerald-600">Sinh viên đã có thể tải tài liệu đã ký.</p>
          </div>

          <!-- Bị từ chối -->
          <div
            v-if="store.selectedRequest.status?.includes('rejected')"
            class="border border-red-200 rounded-xl p-4 bg-red-50"
          >
            <p class="text-sm font-semibold text-red-700 mb-1">Yêu cầu đã bị từ chối</p>
            <p v-if="store.selectedRequest.reject_reason" class="text-xs text-red-600 italic">
              "{{ store.selectedRequest.reject_reason }}"
            </p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useLecturerSignStore } from '@/stores/lecturer/lecturerSignStore'
import LecturerSignConfirmForm from './LecturerSignConfirmForm.vue'
import LecturerSignRejectForm from './LecturerSignRejectForm.vue'

const store = useLecturerSignStore()

function handleClose() {
  store.closeDetail()
}

function statusBadgeClass(status) {
  const map = {
    pending: 'bg-amber-100 text-amber-700',
    forwarded: 'bg-amber-100 text-amber-700',
    lecturer_reviewing: 'bg-blue-100 text-blue-700',
    signed: 'bg-indigo-100 text-indigo-700',
    completed: 'bg-emerald-100 text-emerald-700',
    rejected_by_admin: 'bg-red-100 text-red-700',
    rejected_by_lecturer: 'bg-red-100 text-red-700',
  }
  return map[status] ?? 'bg-stone-100 text-stone-600'
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
