<!-- src/components/admin/sign/AdminSignDetailModal.vue -->
<template>
  <Teleport to="body">
    <div
      v-if="store.selectedRequest"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="store.closeDetail()" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
      >
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex items-start justify-between flex-shrink-0">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h3 class="text-lg font-bold text-gray-900">Chi tiết yêu cầu ký số</h3>
              <span
                class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                :class="statusBadgeClass(store.selectedRequest.status)"
              >
                {{ store.selectedRequest.status_label }}
              </span>
            </div>
            <p class="text-xs text-gray-400">
              Yêu cầu #{{ store.selectedRequest.id }} ·
              {{ formatDate(store.selectedRequest.created_at) }}
            </p>
          </div>
          <button
            @click="store.closeDetail()"
            class="p-1.5 hover:bg-gray-100 rounded-lg transition"
          >
            <svg
              class="w-5 h-5 text-gray-400"
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
          <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
              Thông tin sinh viên
            </p>
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700"
              >
                {{ store.selectedRequest.requester?.name?.charAt(0) }}
              </div>
              <div>
                <p class="font-semibold text-gray-900">
                  {{ store.selectedRequest.requester?.name }}
                </p>
                <p class="text-xs text-gray-400 font-mono">
                  {{ store.selectedRequest.requester?.code }} ·
                  {{ store.selectedRequest.requester?.email }}
                </p>
              </div>
            </div>
          </div>

          <!-- Thông tin tài liệu -->
          <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
              Thông tin tài liệu
            </p>
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <p class="text-xs text-gray-400">Loại tài liệu</p>
                <p class="font-medium text-violet-700 mt-0.5">
                  {{
                    store.selectedRequest.document_category_label ??
                    store.selectedRequest.document_category
                  }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Định dạng file</p>
                <p class="font-medium text-gray-700 mt-0.5 uppercase">
                  {{ store.selectedRequest.document_type }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">Lớp</p>
                <p class="font-medium text-gray-700 mt-0.5">
                  {{ store.selectedRequest.class_model?.name }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-400">File gốc</p>
                <button
                  @click="store.previewFile(store.selectedRequest.id)"
                  class="flex items-center gap-1 text-blue-600 hover:underline text-xs mt-0.5 font-medium"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                    />
                  </svg>
                  Tải xuống xem trước
                </button>
              </div>
            </div>
          </div>

          <!-- Audit log -->
          <div v-if="store.selectedRequest.logs?.length">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
              Lịch sử xử lý
            </p>
            <div class="space-y-2">
              <div
                v-for="log in store.selectedRequest.logs"
                :key="log.id"
                class="flex items-start gap-2 text-xs"
              >
                <div class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1.5 flex-shrink-0" />
                <div>
                  <span class="font-medium text-gray-700">{{ log.actor?.name }}</span>
                  <span class="text-gray-400"> · {{ log.action_label ?? log.action }}</span>
                  <span class="text-gray-400"> · {{ formatDate(log.created_at) }}</span>
                  <p v-if="log.note" class="text-gray-500 italic mt-0.5">"{{ log.note }}"</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Forward form -->
          <AdminSignForwardForm v-if="store.canForward" />

          <!-- Đã forward -->
          <div
            v-if="['forwarded', 'lecturer_reviewing'].includes(store.selectedRequest.status)"
            class="border border-blue-200 rounded-xl p-4 bg-blue-50"
          >
            <p class="text-xs font-semibold text-blue-700 mb-1">Đã chuyển cho giảng viên</p>
            <p class="text-sm font-medium text-blue-900">
              {{ store.selectedRequest.lecturer?.name }}
            </p>
            <p class="text-xs text-blue-600 mt-0.5">
              {{ formatDate(store.selectedRequest.forwarded_at) }}
            </p>
          </div>

          <!-- Reject form -->
          <AdminSignRejectForm v-if="store.canReject" />

          <!-- Complete form -->
          <AdminSignCompleteForm v-if="store.canComplete" />

          <!-- Đã ký — chờ phát hành -->
          <div
            v-if="store.selectedRequest.status === 'signed'"
            class="border border-indigo-200 rounded-xl p-4 bg-indigo-50"
          >
            <p class="text-sm font-semibold text-indigo-700 mb-1">✅ GV đã ký xong</p>
            <p class="text-xs text-indigo-600">Bấm "Phát hành" để sinh viên có thể tải tài liệu.</p>
          </div>

          <!-- Hoàn thành -->
          <div
            v-if="store.selectedRequest.status === 'completed'"
            class="border border-emerald-200 rounded-xl p-4 bg-emerald-50"
          >
            <p class="text-sm font-semibold text-emerald-700">🎉 Đã phát hành cho sinh viên</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import AdminSignForwardForm from './AdminSignForwardForm.vue'
import AdminSignRejectForm from './AdminSignRejectForm.vue'
import AdminSignCompleteForm from './AdminSignCompleteForm.vue'
import { useAdminSignStore } from '@/stores/admin/sign/adminSignStore'

const store = useAdminSignStore()

function statusBadgeClass(status) {
  const map = {
    pending: 'bg-amber-100 text-amber-700',
    admin_reviewing: 'bg-amber-100 text-amber-700',
    forwarded: 'bg-blue-100 text-blue-700',
    lecturer_reviewing: 'bg-blue-100 text-blue-700',
    signed: 'bg-indigo-100 text-indigo-700',
    completed: 'bg-emerald-100 text-emerald-700',
    rejected_by_admin: 'bg-red-100 text-red-700',
    rejected_by_lecturer: 'bg-red-100 text-red-700',
  }
  return map[status] ?? 'bg-gray-100 text-gray-600'
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
