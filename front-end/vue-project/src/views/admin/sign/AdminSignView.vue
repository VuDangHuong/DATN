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
        <p class="text-2xl font-bold text-gray-700">{{ stats.total ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Tổng yêu cầu</p>
      </div>
      <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ stats.pending ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Chờ xử lý</p>
      </div>
      <div class="bg-white rounded-xl border border-blue-200 p-4 text-center">
        <p class="text-2xl font-bold text-blue-500">{{ stats.forwarded ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-1">Đã chuyển GV</p>
      </div>
      <div class="bg-white rounded-xl border border-emerald-200 p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ stats.completed ?? 0 }}</p>
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
          @click="handleStatusFilter(f.value)"
          class="px-3 py-1.5 rounded-md text-xs font-medium transition"
          :class="
            filterStatus === f.value
              ? 'bg-white text-gray-800 shadow-sm'
              : 'text-gray-500 hover:text-gray-700'
          "
        >
          {{ f.label }}
        </button>
      </div>

      <select
        v-model="filterCategory"
        @change="loadRequests()"
        class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-600 focus:ring-2 focus:ring-blue-500 outline-none"
      >
        <option value="">Tất cả loại tài liệu</option>
        <option v-for="cat in documentCategories" :key="cat.value" :value="cat.value">
          {{ cat.label }}
        </option>
      </select>

      <div v-if="statsByCategory.length" class="flex gap-2 flex-wrap ml-auto">
        <span
          v-for="cat in statsByCategory"
          :key="cat.document_category"
          class="px-2 py-1 text-[10px] font-bold rounded-lg cursor-pointer transition"
          :class="
            filterCategory === cat.document_category
              ? 'bg-violet-600 text-white'
              : 'bg-violet-50 text-violet-700 hover:bg-violet-100'
          "
          @click="handleCategoryFilter(cat.document_category)"
        >
          {{ cat.document_category_label }} ({{ cat.total }})
        </span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div v-else-if="!requests.length" class="bg-white rounded-xl border p-12 text-center">
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
      <p v-if="filterCategory || filterStatus" class="text-xs text-gray-400 mt-1">
        <button @click="resetFilter" class="text-blue-500 hover:underline">Xóa bộ lọc</button>
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
          <tr v-for="req in requests" :key="req.id" class="hover:bg-gray-50 transition group">
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
                <!-- ✅ Nút forward tự động GV lớp -->
                <button
                  v-if="['pending', 'admin_reviewing'].includes(req.status)"
                  @click="quickForward(req)"
                  :disabled="forwardingId === req.id"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium transition flex items-center gap-1 disabled:opacity-50"
                  :class="
                    req.lecturer?.id
                      ? 'bg-violet-600 text-white hover:bg-violet-700'
                      : 'bg-slate-200 text-slate-600 hover:bg-slate-300'
                  "
                >
                  <div
                    v-if="forwardingId === req.id"
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
        v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-100 flex items-center justify-between"
      >
        <p class="text-xs text-gray-500">
          Trang {{ pagination.current_page }} / {{ pagination.last_page }} ·
          {{ pagination.total }} yêu cầu
        </p>
        <div class="flex gap-1">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs disabled:opacity-40 hover:bg-gray-50"
          >
            ← Trước
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs disabled:opacity-40 hover:bg-gray-50"
          >
            Sau →
          </button>
        </div>
      </div>
    </div>

    <!-- ── Modal: Chi tiết + Forward ── -->
    <Teleport to="body">
      <div v-if="selectedRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="selectedRequest = null"
        />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
        >
          <div class="p-6 border-b border-gray-100 flex items-start justify-between flex-shrink-0">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-lg font-bold text-gray-900">Chi tiết yêu cầu ký số</h3>
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                  :class="statusBadgeClass(selectedRequest.status)"
                >
                  {{ selectedRequest.status_label }}
                </span>
              </div>
              <p class="text-xs text-gray-400">
                Yêu cầu #{{ selectedRequest.id }} · {{ formatDate(selectedRequest.created_at) }}
              </p>
            </div>
            <button
              @click="selectedRequest = null"
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
                  {{ selectedRequest.requester?.name?.charAt(0) }}
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ selectedRequest.requester?.name }}</p>
                  <p class="text-xs text-gray-400 font-mono">
                    {{ selectedRequest.requester?.code }} · {{ selectedRequest.requester?.email }}
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
                      selectedRequest.document_category_label ?? selectedRequest.document_category
                    }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">Định dạng file</p>
                  <p class="font-medium text-gray-700 mt-0.5 uppercase">
                    {{ selectedRequest.document_type }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">Lớp</p>
                  <p class="font-medium text-gray-700 mt-0.5">
                    {{ selectedRequest.class_model?.name }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-gray-400">File gốc</p>
                  <button
                    @click="downloadOriginal"
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
            <div v-if="selectedRequest.logs?.length">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                Lịch sử xử lý
              </p>
              <div class="space-y-2">
                <div
                  v-for="log in selectedRequest.logs"
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

            <!-- ── Forward form — chỉ dùng khi cần đổi GV khác ── -->
            <div
              v-if="canForward"
              class="border-2 border-dashed border-blue-200 rounded-xl p-4 bg-blue-50/50"
            >
              <p class="text-sm font-semibold text-blue-800 mb-1">Chuyển cho giảng viên ký</p>

              <!-- ✅ Hiện GV phụ trách lớp — nút forward nhanh -->
              <div v-if="selectedRequest.lecturer?.name" class="mb-3 flex items-center gap-2">
                <div
                  class="flex-1 flex items-center gap-2 px-3 py-2 bg-violet-50 border border-violet-200 rounded-lg"
                >
                  <svg
                    class="w-4 h-4 text-violet-500 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                  <div>
                    <p class="text-xs font-semibold text-violet-800">
                      {{ selectedRequest.lecturer.name }}
                    </p>
                    <p class="text-[10px] text-violet-500">GV phụ trách lớp</p>
                  </div>
                </div>
                <button
                  @click="handleForwardToClassLecturer"
                  :disabled="forwarding"
                  class="px-4 py-2 bg-violet-600 text-white rounded-lg text-xs font-semibold hover:bg-violet-700 disabled:opacity-50 transition flex items-center gap-1.5"
                >
                  <div
                    v-if="forwarding"
                    class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"
                  />
                  <svg
                    v-else
                    class="w-3.5 h-3.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 7l5 5m0 0l-5 5m5-5H6"
                    />
                  </svg>
                  {{ forwarding ? 'Đang chuyển...' : 'Chuyển ngay' }}
                </button>
              </div>

              <!-- Hoặc chọn GV khác -->
              <details class="group">
                <summary
                  class="text-xs text-gray-500 cursor-pointer hover:text-gray-700 select-none"
                >
                  {{
                    selectedRequest.lecturer?.name
                      ? 'Hoặc chọn giảng viên khác...'
                      : 'Chọn giảng viên *'
                  }}
                </summary>
                <div class="mt-3 space-y-3">
                  <select
                    v-model="forwardForm.lecturer_id"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                  >
                    <option value="">-- Chọn giảng viên --</option>
                    <option v-for="l in lecturers" :key="l.id" :value="l.id">{{ l.name }}</option>
                  </select>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1"
                      >Ghi chú (tuỳ chọn)</label
                    >
                    <textarea
                      v-model="forwardForm.note"
                      rows="2"
                      placeholder="Hướng dẫn cho giảng viên..."
                      class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none focus:ring-2 focus:ring-blue-500 outline-none"
                    />
                  </div>
                  <button
                    @click="handleForward"
                    :disabled="!forwardForm.lecturer_id || forwarding"
                    class="w-full py-2.5 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 transition flex items-center justify-center gap-2"
                  >
                    <div
                      v-if="forwarding"
                      class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                    />
                    {{ forwarding ? 'Đang chuyển...' : 'Chuyển cho giảng viên này' }}
                  </button>
                </div>
              </details>
            </div>

            <!-- Đã forward -->
            <div
              v-if="
                selectedRequest.status === 'forwarded' ||
                selectedRequest.status === 'lecturer_reviewing'
              "
              class="border border-blue-200 rounded-xl p-4 bg-blue-50"
            >
              <p class="text-xs font-semibold text-blue-700 mb-1">Đã chuyển cho giảng viên</p>
              <p class="text-sm font-medium text-blue-900">{{ selectedRequest.lecturer?.name }}</p>
              <p class="text-xs text-blue-600 mt-0.5">
                {{ formatDate(selectedRequest.forwarded_at) }}
              </p>
            </div>

            <!-- ── Reject form ── -->
            <div v-if="canReject" class="border border-red-200 rounded-xl p-4">
              <p class="text-sm font-semibold text-red-700 mb-2">Từ chối yêu cầu</p>
              <textarea
                v-model="rejectReason"
                rows="2"
                placeholder="Lý do từ chối..."
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none focus:ring-2 focus:ring-red-500 outline-none mb-2"
              />
              <button
                @click="handleReject"
                :disabled="!rejectReason.trim() || rejecting"
                class="px-4 py-2 bg-red-600 text-white rounded-lg text-xs font-medium hover:bg-red-700 disabled:opacity-50 transition flex items-center gap-2"
              >
                <div
                  v-if="rejecting"
                  class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"
                />
                {{ rejecting ? 'Đang xử lý...' : 'Từ chối' }}
              </button>
            </div>

            <!-- ── Complete form ── -->
            <div v-if="canComplete" class="border border-emerald-200 rounded-xl p-4 bg-emerald-50">
              <p class="text-sm font-semibold text-emerald-700 mb-1">Phát hành tài liệu đã ký</p>
              <p class="text-xs text-emerald-600 mb-3">
                GV đã ký xong. Xác nhận để sinh viên có thể tải về.
              </p>
              <button
                @click="handleComplete"
                :disabled="completing"
                class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50 transition flex items-center gap-2"
              >
                <div
                  v-if="completing"
                  class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                />
                {{ completing ? 'Đang xử lý...' : '✅ Phát hành cho sinh viên' }}
              </button>
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

const toast = useToastStore()

const requests = ref([])
const loading = ref(false)
const selectedRequest = ref(null)
const lecturers = ref([])
const stats = ref({})
const statsByCategory = ref([])
const documentCategories = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const filterStatus = ref('')
const filterCategory = ref('')

const forwardForm = ref({ lecturer_id: '', note: '' })
const rejectReason = ref('')
const forwarding = ref(false)
const forwardingId = ref(null)
const rejecting = ref(false)
const completing = ref(false)

const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'pending', label: 'Chờ xử lý' },
  { value: 'admin_reviewing', label: 'Đang xem' },
  { value: 'forwarded', label: 'Đã chuyển GV' },
  { value: 'signed', label: 'Đã ký' },
  { value: 'completed', label: 'Hoàn thành' },
]

const canForward = computed(() =>
  ['pending', 'admin_reviewing'].includes(selectedRequest.value?.status),
)
const canReject = computed(() =>
  ['pending', 'admin_reviewing'].includes(selectedRequest.value?.status),
)
const canComplete = computed(() => selectedRequest.value?.status === 'signed')

onMounted(async () => {
  await Promise.all([loadRequests(), loadStats(), loadLecturers(), loadDocumentCategories()])
})

async function loadDocumentCategories() {
  try {
    const { data } = await axiosClient.get('/general/document-categories')
    documentCategories.value = data
  } catch {
    documentCategories.value = []
  }
}

async function loadRequests(page = 1) {
  loading.value = true
  try {
    const { data } = await axiosClient.get('/admin/sign-requests', {
      params: {
        status: filterStatus.value || undefined,
        category: filterCategory.value || undefined,
        page,
      },
    })
    requests.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
    }
  } catch {
    requests.value = []
  } finally {
    loading.value = false
  }
}

async function loadStats() {
  try {
    const { data } = await axiosClient.get('/admin/sign-requests/stats')
    const byStatus = {}
    let total = 0
    ;(data.by_status ?? []).forEach((s) => {
      byStatus[s.status] = s.total
      total += s.total
    })
    stats.value = {
      total,
      pending: (byStatus.pending ?? 0) + (byStatus.admin_reviewing ?? 0),
      forwarded: (byStatus.forwarded ?? 0) + (byStatus.lecturer_reviewing ?? 0),
      completed: byStatus.completed ?? 0,
    }
    statsByCategory.value = data.by_category ?? []
  } catch {}
}

async function loadLecturers() {
  try {
    const { data } = await axiosClient.get('/admin/users', { params: { role: 'lecturer' } })
    lecturers.value = data.data ?? data
  } catch {}
}

function handleStatusFilter(value) {
  filterStatus.value = value
  loadRequests()
}
function handleCategoryFilter(cat) {
  filterCategory.value = filterCategory.value === cat ? '' : cat
  loadRequests()
}
function resetFilter() {
  filterStatus.value = ''
  filterCategory.value = ''
  loadRequests()
}

// ✅ Forward thẳng GV lớp — không mở modal
async function quickForward(req) {
  if (!req.lecturer?.id) {
    toast.error('Lớp chưa có GV phụ trách. Vào "Xem & Xử lý" để chọn thủ công.')
    return
  }
  forwardingId.value = req.id
  try {
    await axiosClient.post(`/admin/sign-requests/${req.id}/forward`, {
      lecturer_id: req.lecturer.id,
      note: '',
    })
    toast.success(`Đã chuyển cho ${req.lecturer.name}`)
    await Promise.all([loadRequests(), loadStats()])
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi chuyển yêu cầu')
  } finally {
    forwardingId.value = null
  }
}

async function openDetail(req) {
  selectedRequest.value = null
  forwardForm.value = { lecturer_id: '', note: '' }
  rejectReason.value = ''
  try {
    const { data } = await axiosClient.get(`/admin/sign-requests/${req.id}`)
    selectedRequest.value = data.data
    if (data.data.lecturer_id) forwardForm.value.lecturer_id = data.data.lecturer_id
  } catch {
    toast.error('Không thể tải chi tiết')
  }
}

// ✅ Forward nhanh GV lớp từ trong detail modal
async function handleForwardToClassLecturer() {
  if (!selectedRequest.value?.lecturer?.id) return
  forwarding.value = true
  try {
    await axiosClient.post(`/admin/sign-requests/${selectedRequest.value.id}/forward`, {
      lecturer_id: selectedRequest.value.lecturer.id,
      note: '',
    })
    toast.success(`Đã chuyển cho ${selectedRequest.value.lecturer.name}`)
    selectedRequest.value = null
    await Promise.all([loadRequests(), loadStats()])
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi chuyển yêu cầu')
  } finally {
    forwarding.value = false
  }
}

// Forward GV khác (từ <details> trong modal)
async function handleForward() {
  forwarding.value = true
  try {
    await axiosClient.post(
      `/admin/sign-requests/${selectedRequest.value.id}/forward`,
      forwardForm.value,
    )
    toast.success('Đã chuyển yêu cầu cho giảng viên')
    selectedRequest.value = null
    await Promise.all([loadRequests(), loadStats()])
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi chuyển yêu cầu')
  } finally {
    forwarding.value = false
  }
}

async function handleReject() {
  rejecting.value = true
  try {
    await axiosClient.post(`/admin/sign-requests/${selectedRequest.value.id}/reject`, {
      reason: rejectReason.value,
    })
    toast.success('Đã từ chối yêu cầu')
    selectedRequest.value = null
    await Promise.all([loadRequests(), loadStats()])
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi từ chối')
  } finally {
    rejecting.value = false
  }
}

async function handleComplete() {
  completing.value = true
  try {
    await axiosClient.post(`/admin/sign-requests/${selectedRequest.value.id}/complete`)
    toast.success('Đã phát hành tài liệu cho sinh viên')
    selectedRequest.value = null
    await Promise.all([loadRequests(), loadStats()])
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi phát hành')
  } finally {
    completing.value = false
  }
}

async function downloadOriginal() {
  if (!selectedRequest.value?.original_file) return
  try {
    const response = await axiosClient.get(
      `/admin/sign-requests/${selectedRequest.value.id}/preview`,
    )
    window.open(response.data.url, '_blank')
  } catch {
    toast.error('Không thể tải file')
  }
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  loadRequests(page)
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
