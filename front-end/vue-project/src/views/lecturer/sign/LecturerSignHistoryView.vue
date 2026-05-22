<!-- src/views/lecturer/LecturerSignHistoryView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-stone-800">Lịch sử ký số</h2>
        <p class="text-sm text-stone-500 mt-1">Danh sách tài liệu đã ký và mã xác thực</p>
      </div>
      <!-- Export button -->
      <button
        @click="exportCsv"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
      >
        <SvgICon name="download" class="w-4 h-4" />
        Xuất CSV
      </button>
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
        <p class="text-2xl font-bold text-emerald-600">{{ store.stats.rejected ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Hoàn thành</p>
      </div>
    </div>

    <!-- Filter + Search -->
    <div
      class="bg-white rounded-xl border border-stone-200 p-4 mb-5 flex flex-wrap gap-3 items-center"
    >
      <!-- Filter status -->
      <div class="flex gap-1 bg-stone-100 rounded-lg p-1">
        <button
          v-for="f in statusFilters"
          :key="f.value"
          @click="setFilter('status', f.value)"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition"
          :class="
            filterStatus === f.value
              ? 'bg-white text-stone-800 shadow-sm'
              : 'text-stone-500 hover:text-stone-700'
          "
        >
          {{ f.label }}
        </button>
      </div>

      <!-- Filter category -->
      <select
        v-model="filterCategory"
        @change="loadHistory()"
        class="px-3 py-1.5 border border-stone-200 rounded-lg text-xs text-stone-600 outline-none focus:ring-2 focus:ring-teal-500"
      >
        <option value="">Tất cả loại tài liệu</option>
        <option value="bao_cao_thuc_tap">Báo cáo thực tập</option>
        <option value="nckh">Nghiên cứu khoa học</option>
        <option value="do_an_tot_nghiep">Đồ án tốt nghiệp</option>
        <option value="bao_cao_du_an">Báo cáo dự án</option>
        <option value="khoa_luan">Khóa luận</option>
      </select>

      <!-- Search by hash hoặc tên SV -->
      <div class="flex-1 min-w-48 relative">
        <svg
          class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-stone-400"
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
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          placeholder="Tìm theo tên SV hoặc mã hash..."
          class="w-full pl-9 pr-3 py-1.5 border border-stone-200 rounded-lg text-xs text-stone-700 outline-none focus:ring-2 focus:ring-teal-500"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!records.length"
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
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <p class="text-stone-400 font-medium">Chưa có tài liệu nào đã ký</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl border border-stone-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-stone-100 bg-stone-50">
            <th class="text-left px-5 py-3.5 font-medium text-stone-600">Sinh viên</th>
            <th class="text-left px-5 py-3.5 font-medium text-stone-600">Loại tài liệu</th>
            <th class="text-left px-5 py-3.5 font-medium text-stone-600">Mã xác thực (Hash)</th>
            <th class="text-left px-5 py-3.5 font-medium text-stone-600">Thời gian ký</th>
            <th class="text-left px-5 py-3.5 font-medium text-stone-600">Trạng thái</th>
            <th class="text-right px-5 py-3.5 font-medium text-stone-600">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
          <tr v-for="rec in records" :key="rec.id" class="hover:bg-stone-50 transition group">
            <!-- Sinh viên -->
            <td class="px-5 py-4">
              <div class="flex items-center gap-2">
                <div
                  class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center text-xs font-bold text-teal-700"
                >
                  {{ rec.requester?.name?.charAt(0) }}
                </div>
                <div>
                  <p class="font-medium text-stone-900 text-sm">{{ rec.requester?.name }}</p>
                  <p class="text-xs text-stone-400 font-mono">{{ rec.requester?.code }}</p>
                </div>
              </div>
            </td>

            <!-- Loại tài liệu -->
            <td class="px-5 py-4">
              <span class="px-2 py-0.5 bg-violet-50 text-violet-700 text-xs font-medium rounded-lg">
                {{ rec.document_category_label ?? rec.document_category }}
              </span>
              <p class="text-xs text-stone-400 mt-1">{{ rec.class_model?.name }}</p>
            </td>

            <!-- Mã hash -->
            <td class="px-5 py-4">
              <div v-if="rec.sign_hash" class="flex items-center gap-2">
                <code
                  class="text-[12px] font-mono text-stone-600 bg-stone-100 px-2 py-1 rounded truncate max-w-40"
                >
                  {{ rec.sign_hash }}
                </code>
                <button
                  @click="copyHash(rec.sign_hash)"
                  class="opacity-0 group-hover:opacity-100 p-1 hover:bg-stone-200 rounded transition flex-shrink-0"
                  title="Copy hash"
                >
                  <svg
                    class="w-3.5 h-3.5 text-stone-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                  </svg>
                </button>
              </div>
              <span v-else class="text-xs text-stone-400 italic">Chưa có hash</span>
            </td>

            <!-- Thời gian ký -->
            <td class="px-5 py-4">
              <p class="text-xs text-stone-600">{{ formatDate(rec.signed_at) }}</p>
              <p class="text-[12px] text-stone-400 mt-0.5">Tạo: {{ formatDate(rec.created_at) }}</p>
            </td>

            <!-- Trạng thái -->
            <td class="px-5 py-4">
              <span
                class="px-2 py-0.5 text-xs font-bold rounded-full"
                :class="statusBadgeClass(rec.status)"
              >
                {{ rec.status }}
              </span>
            </td>

            <!-- Actions -->
            <td class="px-5 py-4 text-right">
              <button
                @click="openDetail(rec)"
                class="px-3 py-1.5 border border-stone-200 text-stone-600 rounded-lg text-xs font-medium hover:bg-stone-50 transition"
              >
                Chi tiết
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-stone-100 flex items-center justify-between"
      >
        <p class="text-xs text-stone-500">
          Trang {{ pagination.current_page }} / {{ pagination.last_page }} ·
          {{ pagination.total }} tài liệu
        </p>
        <div class="flex gap-1">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 border border-stone-200 rounded-lg text-xs disabled:opacity-40 hover:bg-stone-50"
          >
            ← Trước
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 border border-stone-200 rounded-lg text-xs disabled:opacity-40 hover:bg-stone-50"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>

    <!-- ── Modal: Chi tiết hash ── -->
    <Teleport to="body">
      <div v-if="selectedRecord" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="selectedRecord = null" />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
          <!-- Header -->
          <div class="p-6 border-b border-stone-100 flex items-start justify-between">
            <div>
              <h3 class="text-lg font-bold text-stone-900">Chi tiết ký số</h3>
              <p class="text-xs text-stone-400 mt-0.5">Yêu cầu #{{ selectedRecord.id }}</p>
            </div>
            <button @click="selectedRecord = null" class="p-1.5 hover:bg-stone-100 rounded-lg">
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
          <div class="p-6 space-y-4">
            <!-- Thông tin SV -->
            <div class="bg-stone-50 rounded-xl p-4">
              <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
                Sinh viên
              </p>
              <p class="font-semibold text-stone-900">{{ selectedRecord.requester?.name }}</p>
              <p class="text-xs text-stone-400 font-mono mt-0.5">
                {{ selectedRecord.requester?.code }}
              </p>
            </div>

            <!-- Thông tin tài liệu -->
            <div class="bg-stone-50 rounded-xl p-4">
              <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
                Tài liệu
              </p>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <p class="text-xs text-stone-400">Loại</p>
                  <p class="font-medium text-violet-700 mt-0.5">
                    {{ selectedRecord.document_category_label }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-stone-400">Lớp</p>
                  <p class="font-medium text-stone-700 mt-0.5">
                    {{ selectedRecord.class_model?.name }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-stone-400">Thời gian ký</p>
                  <p class="font-medium text-stone-700 mt-0.5">
                    {{ formatDate(selectedRecord.signed_at) }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-stone-400">Trạng thái</p>
                  <span
                    class="inline-block mt-0.5 px-2 py-0.5 text-xs font-bold rounded-full"
                    :class="statusBadgeClass(selectedRecord.status)"
                  >
                    {{ selectedRecord.status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Mã hash -->
            <div class="border border-stone-200 rounded-xl p-4">
              <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider">
                  Mã xác thực SHA256
                </p>
                <button
                  @click="copyHash(selectedRecord.sign_hash)"
                  class="flex items-center gap-1 px-2 py-1 text-[12px] font-medium text-teal-600 bg-teal-50 hover:bg-teal-100 rounded-lg transition"
                >
                  <SvgICon name="copy" class="w-4 h-4 text-green-700" />
                  Copy
                </button>
              </div>
              <code
                class="block text-xs font-mono text-stone-700 bg-stone-50 p-3 rounded-lg break-all leading-relaxed"
              >
                {{ selectedRecord.sign_hash ?? 'Chưa có mã hash' }}
              </code>
              <p class="text-[12px] text-stone-400 mt-2">
                Mã này dùng để xác minh tính toàn vẹn của phiếu xác nhận ký số.
              </p>
            </div>

            <!-- Verify section -->
            <div class="border border-teal-200 rounded-xl p-4 bg-teal-50/50">
              <p class="text-xs font-semibold text-teal-700 mb-2 flex items-center gap-1.5">
                <SvgICon name="shield-check" class="w-4 h-4 text-green-700" />
                Xác minh tài liệu
              </p>
              <p class="text-[12px] text-teal-600 mb-3">
                Nhập mã hash của file PDF phiếu xác nhận để kiểm tra tính hợp lệ.
              </p>
              <div class="flex gap-2">
                <input
                  v-model="verifyInput"
                  placeholder="Dán mã hash cần kiểm tra..."
                  class="flex-1 px-3 py-2 border border-stone-200 rounded-lg text-xs font-mono outline-none focus:ring-2 focus:ring-teal-500"
                />
                <button
                  @click="verifyHash"
                  class="px-3 py-2 bg-teal-600 text-white rounded-lg text-xs font-medium hover:bg-teal-700 transition flex-shrink-0"
                >
                  Kiểm tra
                </button>
              </div>
              <!-- Kết quả verify -->
              <div
                v-if="verifyResult !== null"
                class="mt-2 px-3 py-2 rounded-lg text-xs font-medium flex items-center gap-1.5"
                :class="
                  verifyResult ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                "
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    v-if="verifyResult"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                  <path
                    v-else
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
                {{
                  verifyResult
                    ? 'Hash hợp lệ — tài liệu chưa bị chỉnh sửa'
                    : 'Hash không khớp — tài liệu có thể đã bị thay đổi'
                }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axiosClient from '@/api/axiosClient'
import { useToastStore } from '@/stores/toast'
import SvgICon from '@/components/icons/SVG.vue'
import { useLecturerSignStore } from '@/stores/lecturer/lecturerSignStore'

const store = useLecturerSignStore()
const toast = useToastStore()

const records = ref([])
const loading = ref(false)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filterStatus = ref('signed') // Mặc định show đã ký
const filterCategory = ref('')
const searchQuery = ref('')
const selectedRecord = ref(null)
const verifyInput = ref('')
const verifyResult = ref(null)

let searchTimer = null

const stats = computed(() => {
  const all = records.value
  const uniqueCategories = new Set(all.map((r) => r.document_category).filter(Boolean))
  return {
    total: pagination.value.total,
    completed: all.filter((r) => r.status === 'completed').length,
    signed: all.filter((r) => r.status === 'signed').length,
    categories: uniqueCategories.size,
  }
})

const statusFilters = [
  { value: 'signed', label: 'Đã ký' },
  { value: 'completed', label: 'Đã phát hành' },
  { value: '', label: 'Tất cả' },
]

onMounted(() => loadHistory())

async function loadHistory(page = 1) {
  loading.value = true
  try {
    const { data } = await axiosClient.get('/lecturer/sign-requests', {
      params: {
        // Chỉ lấy các request đã ký (signed + completed)
        status: filterStatus.value || 'signed,completed',
        category: filterCategory.value || undefined,
        search: searchQuery.value || undefined,
        page,
      },
    })
    records.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
    }
  } catch {
    records.value = []
  } finally {
    loading.value = false
  }
}

function setFilter(type, value) {
  if (type === 'status') filterStatus.value = value
  loadHistory()
}

function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadHistory(), 400)
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  loadHistory(page)
}

function openDetail(rec) {
  selectedRecord.value = rec
  verifyInput.value = ''
  verifyResult.value = null
}

function verifyHash() {
  if (!verifyInput.value.trim() || !selectedRecord.value?.sign_hash) return
  verifyResult.value =
    verifyInput.value.trim().toLowerCase() === selectedRecord.value.sign_hash.toLowerCase()
}

async function copyHash(hash) {
  if (!hash) return
  try {
    await navigator.clipboard.writeText(hash)
    toast.success('Đã copy mã hash')
  } catch {
    toast.error('Không thể copy')
  }
}

function exportCsv() {
  if (!records.value.length) return

  const headers = [
    'STT',
    'Sinh viên',
    'Mã SV',
    'Loại tài liệu',
    'Lớp',
    'Mã Hash SHA256',
    'Thời gian ký',
    'Trạng thái',
  ]
  const rows = records.value.map((r, i) => [
    i + 1,
    r.requester?.name ?? '',
    r.requester?.code ?? '',
    r.document_category_label ?? r.document_category ?? '',
    r.class_model?.name ?? '',
    r.sign_hash ?? '',
    r.signed_at ? new Date(r.signed_at).toLocaleString('vi-VN') : '',
    r.status_label ?? r.status ?? '',
  ])

  const csvContent = [headers, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `lich_su_ky_so_${new Date().toISOString().slice(0, 10)}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
  toast.success('Đã xuất file CSV')
}

function statusBadgeClass(status) {
  const map = {
    signed: 'bg-indigo-100 text-indigo-700',
    completed: 'bg-emerald-100 text-emerald-700',
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
