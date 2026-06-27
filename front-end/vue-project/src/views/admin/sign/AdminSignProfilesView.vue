<!-- src/views/admin/AdminSignProfilesView.vue -->
<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-stone-800">Quản lý chữ ký số</h2>
        <p class="text-base text-stone-500 mt-1">Danh sách chữ ký số của tất cả giảng viên</p>
      </div>
      <router-link
        to="/admin/deactivation-requests"
        class="px-4 py-2 bg-rose-600 text-white rounded-xl text-base font-semibold hover:bg-rose-600 flex items-center gap-2"
      >
        <SvgIcon name="document" class="w-4 h-4" />
        <span>Yêu cầu vô hiệu</span>
        <span
          v-if="stats.pending_requests > 0"
          class="bg-white text-rose-600 px-1.5 rounded-full text-base font-bold"
        >
          {{ stats.pending_requests }}
        </span>
      </router-link>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
      <div class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-2xl font-bold text-stone-700">{{ stats.total }}</p>
        <p class="text-base text-stone-500">Tổng</p>
      </div>
      <div class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
        <p class="text-base text-stone-500">Đang hoạt động</p>
      </div>
      <div class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-2xl font-bold text-amber-600">{{ stats.pending_deactivation }}</p>
        <p class="text-base text-stone-500">Chờ duyệt vô hiệu</p>
      </div>
      <div class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-2xl font-bold text-stone-500">{{ stats.inactive }}</p>
        <p class="text-base text-stone-500">Đã vô hiệu</p>
      </div>
      <div class="bg-white rounded-xl border border-stone-200 p-4">
        <p class="text-2xl font-bold text-red-600">{{ stats.expired }}</p>
        <p class="text-base text-stone-500">Hết hạn</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-stone-200 p-4 flex flex-wrap items-center gap-3">
      <!-- Search -->
      <div class="relative flex-1 min-w-[240px]">
        <input
          v-model="searchInput"
          @input="onSearchInput"
          type="text"
          placeholder="Tìm theo tên, mã, email giảng viên..."
          class="w-full pl-9 pr-3 py-2 border border-stone-200 rounded-lg text-sm focus:ring-2 focus:ring-rose-500 outline-none"
        />
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>

      <!-- Filter status -->
      <div class="flex gap-1 bg-stone-100 rounded-lg p-1">
        <button
          v-for="f in statusFilters"
          :key="f.value"
          @click="changeFilter(f.value)"
          class="px-3 py-1.5 rounded-md text-base font-medium transition"
          :class="
            filterStatus === f.value
              ? 'bg-white text-stone-800 shadow-sm'
              : 'text-stone-500 hover:text-stone-700'
          "
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-rose-200 border-t-rose-600 rounded-full animate-spin" />
      </div>

      <div v-else-if="!profiles.length" class="text-center py-12 text-sm text-stone-400">
        Không có chữ ký số nào
      </div>

      <table v-else class="w-full text-sm">
        <thead class="bg-stone-50 border-b border-stone-200">
          <tr>
            <th class="px-4 py-3 text-left text-base font-semibold text-stone-600">Giảng viên</th>
            <th class="px-4 py-3 text-left text-base font-semibold text-stone-600">Chứng thư</th>
            <th class="px-4 py-3 text-left text-base font-semibold text-stone-600">Hiệu lực</th>
            <th class="px-4 py-3 text-left text-base font-semibold text-stone-600">Trạng thái</th>
            <th class="px-4 py-3 text-left text-base font-semibold text-stone-600">Ngày tạo</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
          <tr v-for="p in profiles" :key="p.id" class="hover:bg-stone-50">
            <!-- Lecturer -->
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div
                  class="w-8 h-8 rounded-full bg-stone-100 flex items-center justify-center text-base font-bold text-stone-600 overflow-hidden flex-shrink-0"
                >
                  <img
                    v-if="p.lecturer?.avatar_url"
                    :src="p.lecturer.avatar_url"
                    class="w-full h-full object-cover"
                  />
                  <span v-else>{{ p.lecturer?.name?.charAt(0) }}</span>
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-stone-800 truncate">{{ p.lecturer?.name }}</p>
                  <p class="text-base text-stone-500 truncate">
                    {{ p.lecturer?.code }} · {{ p.lecturer?.email }}
                  </p>
                </div>
              </div>
            </td>

            <!-- Cert -->
            <td class="px-4 py-3">
              <p
                class="font-medium text-stone-800 text-base truncate max-w-[200px]"
                :title="p.subject_cn"
              >
                {{ p.subject_cn || '—' }}
              </p>
              <p
                class="text-[12px] text-stone-400 truncate font-mono max-w-[200px]"
                :title="p.serial_number"
              >
                {{ p.serial_number?.substring(0, 30) }}...
              </p>
            </td>

            <!-- Valid -->
            <td class="px-4 py-3">
              <p class="text-base text-stone-600">
                {{ formatDate(p.cert_valid_from) }}
              </p>
              <p
                class="text-base"
                :class="isExpired(p.cert_valid_to) ? 'text-red-600 font-medium' : 'text-stone-400'"
              >
                → {{ formatDate(p.cert_valid_to) }}
              </p>
            </td>

            <!-- Status -->
            <td class="px-4 py-3">
              <span
                class="inline-block px-2 py-0.5 rounded-full text-[12px] font-bold uppercase"
                :class="statusClass(p.status)"
              >
                {{ statusLabel(p.status) }}
              </span>
              <p
                v-if="p.pending_request"
                class="flex items-center gap-1 text-[12px] text-amber-700 mt-1 truncate max-w-[200px]"
                :title="p.pending_request.reason"
              >
                <SvgIcon name="chat" class="w-5 h-5" />
                {{ p.pending_request.reason }}
              </p>
            </td>

            <!-- Created -->
            <td class="px-4 py-3">
              <p class="text-base text-stone-500">{{ formatDate(p.created_at) }}</p>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="px-4 py-3 border-t border-stone-100 flex items-center justify-between"
      >
        <p class="text-base text-stone-500">
          Trang {{ pagination.current_page }}/{{ pagination.last_page }} ·
          {{ pagination.total }} kết quả
        </p>
        <div class="flex gap-1">
          <button
            :disabled="pagination.current_page === 1"
            @click="changePage(pagination.current_page - 1)"
            class="px-3 py-1 border border-stone-200 rounded-md text-base hover:bg-stone-50 disabled:opacity-50"
          >
            ← Trước
          </button>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            @click="changePage(pagination.current_page + 1)"
            class="px-3 py-1 border border-stone-200 rounded-md text-base hover:bg-stone-50 disabled:opacity-50"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAdminSignProfileStore } from '@/stores/admin/sign/adminSignProfileStore'
import SvgIcon from '@/components/icons/SVG.vue'

const store = useAdminSignProfileStore()
const { profiles, stats, pagination, loading } = storeToRefs(store)

const searchInput = ref('')
const filterStatus = ref('')
let searchTimer = null

const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'active', label: 'Active' },
  { value: 'pending_deactivation', label: 'Chờ vô hiệu' },
  { value: 'inactive', label: 'Vô hiệu' },
  { value: 'expired', label: 'Hết hạn' },
]

onMounted(async () => {
  await Promise.all([store.fetchStats(), store.fetchProfiles()])
})

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    store.fetchProfiles({ search: searchInput.value, status: filterStatus.value, page: 1 })
  }, 400)
}

function changeFilter(value) {
  filterStatus.value = value
  store.fetchProfiles({ search: searchInput.value, status: value, page: 1 })
}

function changePage(page) {
  store.fetchProfiles({
    search: searchInput.value,
    status: filterStatus.value,
    page,
  })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function isExpired(date) {
  if (!date) return false
  return new Date(date) < new Date()
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function statusClass(s) {
  return (
    {
      active: 'bg-emerald-100 text-emerald-700',
      pending_deactivation: 'bg-amber-100 text-amber-700',
      inactive: 'bg-stone-100 text-stone-600',
      expired: 'bg-red-100 text-red-700',
    }[s] || 'bg-stone-100 text-stone-600'
  )
}

function statusLabel(s) {
  return (
    {
      active: 'Hoạt động',
      pending_deactivation: 'Chờ vô hiệu',
      inactive: 'Vô hiệu',
      expired: 'Hết hạn',
    }[s] || s
  )
}
</script>
