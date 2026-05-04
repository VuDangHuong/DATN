<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Quản lý ký số tài liệu</h1>
        <p class="text-sm text-gray-500 mt-1">Xem xét và chuyển yêu cầu ký số cho giảng viên</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <p class="text-2xl font-bold text-gray-700">{{ store.stats.total ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Tổng yêu cầu</p>
      </div>
      <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ store.stats.pending ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Chờ xử lý</p>
      </div>
      <div class="bg-white rounded-xl border border-blue-200 p-4 text-center">
        <p class="text-2xl font-bold text-blue-500">{{ store.stats.forwarded ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Đã chuyển GV</p>
      </div>
      <div class="bg-white rounded-xl border border-emerald-200 p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ store.stats.completed ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Hoàn thành</p>
      </div>
    </div>

    <!-- Filters -->
    <div
      class="bg-white rounded-xl border border-gray-200 p-4 mb-5 flex flex-wrap gap-3 items-center"
    >
      <div class="flex gap-1 bg-gray-100 rounded-lg p-1 flex-wrap">
        <button
          v-for="f in statusFilters"
          :key="f.value"
          @click="store.setStatusFilter(f.value)"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition"
          :class="
            store.filterStatus === f.value
              ? 'bg-white text-gray-800 shadow-sm'
              : 'text-gray-500 hover:text-gray-700'
          "
        >
          {{ f.label }}
        </button>
      </div>

      <select
        v-model="store.filterCategory"
        @change="store.loadRequests()"
        class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 focus:ring-2 focus:ring-blue-500 outline-none"
      >
        <option value="">Tất cả loại tài liệu</option>
        <option v-for="cat in store.documentCategories" :key="cat.value" :value="cat.value">
          {{ cat.label }}
        </option>
      </select>

      <div v-if="store.statsByCategory.length" class="flex gap-2 flex-wrap ml-auto">
        <span
          v-for="cat in store.statsByCategory"
          :key="cat.document_category"
          class="px-2 py-1 text-[10px] font-bold rounded-lg cursor-pointer transition"
          :class="
            store.filterCategory === cat.document_category
              ? 'bg-violet-600 text-white'
              : 'bg-violet-50 text-violet-700 hover:bg-violet-100'
          "
          @click="store.setCategoryFilter(cat.document_category)"
        >
          {{ cat.document_category_label }} ({{ cat.total }})
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div v-else-if="!store.requests.length" class="bg-white rounded-xl border p-12 text-center">
      <svg
        class="w-12 h-12 mx-auto text-gray-300 mb-3"
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
      <p class="text-gray-400 font-medium">Không có yêu cầu nào</p>
      <p v-if="store.filterCategory || store.filterStatus" class="text-xs text-gray-400 mt-1">
        <button @click="store.resetFilter()" class="text-blue-500 hover:underline">
          Xóa bộ lọc
        </button>
      </p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100 bg-gray-50">
            <th class="text-left px-5 py-3.5 font-medium text-gray-600">Sinh viên</th>
            <th class="text-left px-5 py-3.5 font-medium text-gray-600">Loại tài liệu</th>
            <th class="text-left px-5 py-3.5 font-medium text-gray-600">Lớp</th>
            <th class="text-left px-5 py-3.5 font-medium text-gray-600">Trạng thái</th>
            <th class="text-left px-5 py-3.5 font-medium text-gray-600">Giảng viên</th>
            <th class="text-left px-5 py-3.5 font-medium text-gray-600">Ngày gửi</th>
            <th class="text-right px-5 py-3.5 font-medium text-gray-600">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="req in store.requests" :key="req.id" class="hover:bg-gray-50 transition group">
            <!-- Sinh viên -->
            <td class="px-5 py-4">
              <div class="flex items-center gap-2">
                <div
                  class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-700"
                >
                  {{ req.requester?.name?.charAt(0) }}
                </div>
                <div>
                  <p class="font-medium text-gray-900 text-sm">{{ req.requester?.name }}</p>
                  <p class="text-xs text-gray-400 font-mono">{{ req.requester?.code }}</p>
                </div>
              </div>
            </td>
            <!-- Loại tài liệu -->
            <td class="px-5 py-4">
              <span class="px-2 py-0.5 bg-violet-50 text-violet-700 text-xs font-medium rounded-lg">
                {{ req.document_category_label ?? req.document_category ?? '—' }}
              </span>
            </td>
            <!-- Lớp -->
            <td class="px-5 py-4">
              <p class="text-sm text-gray-700">{{ req.class_model?.name ?? '—' }}</p>
              <p class="text-xs text-gray-400 font-mono">{{ req.class_model?.code }}</p>
            </td>
            <!-- Trạng thái -->
            <td class="px-5 py-4">
              <span
                class="px-2 py-0.5 text-xs font-bold rounded-full"
                :class="statusBadgeClass(req.status)"
              >
                {{ req.status }}
              </span>
            </td>
            <!-- Giảng viên -->
            <td class="px-5 py-4">
              <p class="text-sm text-gray-600">{{ req.lecturer?.name ?? '—' }}</p>
              <p
                v-if="req.lecturer?.name && ['pending', 'admin_reviewing'].includes(req.status)"
                class="text-[10px] text-violet-500 mt-0.5"
              >
                GV phụ trách lớp
              </p>
            </td>
            <!-- Ngày gửi -->
            <td class="px-5 py-4">
              <p class="text-xs text-gray-500">{{ formatDate(req.created_at) }}</p>
            </td>
            <!-- Actions -->
            <td class="px-5 py-4 text-right">
              <div class="flex items-center justify-end gap-2">
                <button
                  v-if="['pending', 'admin_reviewing'].includes(req.status)"
                  @click="store.quickForward(req)"
                  :disabled="store.forwardingId === req.id"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 disabled:opacity-50"
                  :class="
                    req.lecturer?.id
                      ? 'bg-violet-600 text-white hover:bg-violet-700'
                      : 'bg-slate-200 text-slate-600 hover:bg-slate-300'
                  "
                >
                  <div
                    v-if="store.forwardingId === req.id"
                    class="w-3 h-3 border-2 border-white/40 border-t-white rounded-full animate-spin"
                  />
                  <template v-else>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                      />
                    </svg>
                    {{ req.lecturer?.name ? req.lecturer.name : 'Chưa có GV' }}
                  </template>
                </button>
                <button
                  @click="openDetail(req)"
                  class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition"
                >
                  Xem & Xử lý
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="store.pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 flex items-center justify-between"
      >
        <p class="text-xs text-gray-500">
          Trang {{ store.pagination.current_page }} / {{ store.pagination.last_page }} ·
          {{ store.pagination.total }} yêu cầu
        </p>
        <div class="flex gap-1">
          <button
            @click="store.changePage(store.pagination.current_page - 1, store.pagination.last_page)"
            :disabled="store.pagination.current_page === 1"
            class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs disabled:opacity-40 hover:bg-gray-50"
          >
            ← Trước
          </button>
          <button
            @click="store.changePage(store.pagination.current_page + 1, store.pagination.last_page)"
            :disabled="store.pagination.current_page === store.pagination.last_page"
            class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs disabled:opacity-40 hover:bg-gray-50"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>

    <!-- Modal chi tiết -->
    <AdminSignDetailModal />
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAdminSignStore } from '@/stores/admin/sign/adminSignStore'
import AdminSignDetailModal from '../components/sign/AdminSignDetailModal.vue'
const store = useAdminSignStore()

const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'pending', label: 'Chờ xử lý' },
  { value: 'admin_reviewing', label: 'Đang xem' },
  { value: 'forwarded', label: 'Đã chuyển GV' },
  { value: 'signed', label: 'Đã ký' },
  { value: 'completed', label: 'Hoàn thành' },
]

onMounted(() => store.init())

async function openDetail(req) {
  await store.loadDetail(req)
}

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
