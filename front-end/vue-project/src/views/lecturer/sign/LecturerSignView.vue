<!-- src/views/lecturer/LecturerSignView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-stone-800">Yêu cầu ký số</h2>
        <p class="text-sm text-stone-500 mt-1">Danh sách tài liệu cần ký số từ sinh viên</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
        <p class="text-2xl font-bold text-stone-700">{{ store.stats.total ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Tổng yêu cầu</p>
      </div>
      <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ store.stats.pending ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Chờ ký</p>
      </div>
      <div class="bg-white rounded-xl border border-blue-200 p-4 text-center">
        <p class="text-2xl font-bold text-blue-500">{{ store.stats.signed ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Đã ký</p>
      </div>
      <div class="bg-white rounded-xl border border-emerald-200 p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ store.stats.completed ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Hoàn thành</p>
      </div>
    </div>

    <!-- Filter -->
    <div
      class="bg-white rounded-xl border border-stone-200 p-4 mb-5 flex flex-wrap gap-3 items-center"
    >
      <div class="flex gap-1 bg-stone-100 rounded-lg p-1">
        <button
          v-for="f in statusFilters"
          :key="f.value"
          @click="store.setFilter(f.value)"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition"
          :class="
            store.filterStatus === f.value
              ? 'bg-white text-stone-800 shadow-sm'
              : 'text-stone-500 hover:text-stone-700'
          "
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!store.requests.length"
      class="bg-white rounded-xl border border-stone-200 p-12 text-center"
    >
      <svg
        class="w-12 h-12 mx-auto text-stone-300 mb-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
        />
      </svg>
      <p class="text-stone-400 font-medium">Không có yêu cầu ký số nào</p>
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="req in store.requests"
        :key="req.id"
        class="bg-white rounded-xl border border-stone-200 p-5 hover:shadow-sm transition cursor-pointer"
        @click="store.loadDetail(req)"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-start gap-3 flex-1 min-w-0">
            <!-- Avatar SV -->
            <div
              class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700 flex-shrink-0"
            >
              {{ req.requester?.name?.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <p class="font-semibold text-stone-800">{{ req.requester?.name }}</p>
                <p class="text-xs text-stone-400 font-mono">{{ req.requester?.code }}</p>
                <span
                  class="px-2 py-0.5 bg-violet-50 text-violet-700 text-[10px] font-bold rounded-lg"
                >
                  {{ req.document_category_label ?? req.document_category }}
                </span>
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                  :class="statusBadgeClass(req.status)"
                >
                  {{ req.status_label }}
                </span>
              </div>
              <div class="flex items-center gap-3 text-xs text-stone-400 flex-wrap">
                <span>{{ req.class_model?.name }}</span>
                <span>·</span>
                <span class="uppercase font-mono">{{ req.document_type }}</span>
                <span>·</span>
                <span>{{ formatDate(req.created_at) }}</span>
                <span v-if="req.forwarded_at">· Chuyển: {{ formatDate(req.forwarded_at) }}</span>
              </div>
            </div>
          </div>
          <!-- Action nhanh -->
          <div class="flex-shrink-0 flex items-center gap-2" @click.stop>
            <button
              v-if="['forwarded', 'lecturer_reviewing'].includes(req.status)"
              @click="store.loadDetail(req)"
              class="px-4 py-2 bg-teal-600 text-white rounded-lg text-xs font-semibold hover:bg-teal-700 transition flex items-center gap-1.5"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                />
              </svg>
              Ký tài liệu
            </button>
            <span
              v-else-if="req.status === 'signed'"
              class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg"
            >
              Chờ Admin phát hành
            </span>
            <span
              v-else-if="req.status === 'completed'"
              class="px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-lg"
            >
              Hoàn thành
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="store.pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <p class="text-xs text-stone-500">
        Trang {{ store.pagination.current_page }} / {{ store.pagination.last_page }} ·
        {{ store.pagination.total }} yêu cầu
      </p>
      <div class="flex gap-1">
        <button
          @click="store.changePage(store.pagination.current_page - 1)"
          :disabled="store.pagination.current_page === 1"
          class="px-3 py-1.5 border border-stone-200 rounded-lg text-xs disabled:opacity-40 hover:bg-stone-50"
        >
          ← Trước
        </button>
        <button
          @click="store.changePage(store.pagination.current_page + 1)"
          :disabled="store.pagination.current_page === store.pagination.last_page"
          class="px-3 py-1.5 border border-stone-200 rounded-lg text-xs disabled:opacity-40 hover:bg-stone-50"
        >
          Sau →
        </button>
      </div>
    </div>

    <!-- Modal chi tiết -->
    <LecturerSignDetailModal />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useLecturerSignStore } from '@/stores/lecturer/lecturerSignStore'
import LecturerSignDetailModal from '../components/sign/LecturerSignDetailModal.vue'

const store = useLecturerSignStore()

const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'forwarded', label: 'Chờ ký' },
  { value: 'lecturer_reviewing', label: 'Đang xem' },
  { value: 'signed', label: 'Đã ký' },
  { value: 'completed', label: 'Hoàn thành' },
  { value: 'rejected_by_lecturer', label: 'Đã từ chối' },
]

onMounted(() => store.loadRequests())

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
