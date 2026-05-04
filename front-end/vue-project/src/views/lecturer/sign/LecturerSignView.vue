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
        <p class="text-2xl font-bold text-stone-700">{{ stats.total ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Tổng yêu cầu</p>
      </div>
      <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
        <p class="text-2xl font-bold text-amber-500">{{ stats.pending ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Chờ ký</p>
      </div>
      <div class="bg-white rounded-xl border border-blue-200 p-4 text-center">
        <p class="text-2xl font-bold text-blue-500">{{ stats.signed ?? 0 }}</p>
        <p class="text-xs text-stone-400 mt-1">Đã ký</p>
      </div>
      <div class="bg-white rounded-xl border border-emerald-200 p-4 text-center">
        <p class="text-2xl font-bold text-emerald-600">{{ stats.completed ?? 0 }}</p>
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
          @click="handleFilter(f.value)"
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
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!requests.length"
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
        v-for="req in requests"
        :key="req.id"
        class="bg-white rounded-xl border border-stone-200 p-5 hover:shadow-sm transition cursor-pointer"
        @click="openDetail(req)"
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
                <!-- Badge loại tài liệu -->
                <span
                  class="px-2 py-0.5 bg-violet-50 text-violet-700 text-[10px] font-bold rounded-lg"
                >
                  {{ req.document_category_label ?? req.document_category }}
                </span>
                <!-- Badge trạng thái -->
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
              @click="openDetail(req)"
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
    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <p class="text-xs text-stone-500">
        Trang {{ pagination.current_page }} / {{ pagination.last_page }} ·
        {{ pagination.total }} yêu cầu
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

    <!-- ── Modal: Chi tiết + Ký số ── -->
    <Teleport to="body">
      <div v-if="selectedRequest" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeDetail" />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
        >
          <!-- Modal Header -->
          <div class="p-6 border-b border-stone-100 flex items-start justify-between flex-shrink-0">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-lg font-bold text-stone-900">Chi tiết yêu cầu ký số</h3>
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                  :class="statusBadgeClass(selectedRequest.status)"
                >
                  {{ selectedRequest.status_label }}
                </span>
              </div>
              <p class="text-xs text-stone-400">
                Yêu cầu #{{ selectedRequest.id }} · {{ formatDate(selectedRequest.created_at) }}
              </p>
            </div>
            <button @click="closeDetail" class="p-1.5 hover:bg-stone-100 rounded-lg transition">
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

          <!-- Modal Body -->
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
                  {{ selectedRequest.requester?.name?.charAt(0) }}
                </div>
                <div>
                  <p class="font-semibold text-stone-900">{{ selectedRequest.requester?.name }}</p>
                  <p class="text-xs text-stone-400 font-mono">
                    {{ selectedRequest.requester?.code }} · {{ selectedRequest.requester?.email }}
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
                      selectedRequest.document_category_label ?? selectedRequest.document_category
                    }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-stone-400">Định dạng</p>
                  <p class="font-medium text-stone-700 mt-0.5 uppercase">
                    {{ selectedRequest.document_type }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-stone-400">Lớp</p>
                  <p class="font-medium text-stone-700 mt-0.5">
                    {{ selectedRequest.class_model?.name }}
                  </p>
                </div>
                <div>
                  <p class="text-xs text-stone-400">Thời gian chuyển</p>
                  <p class="font-medium text-stone-700 mt-0.5">
                    {{ formatDate(selectedRequest.forwarded_at) }}
                  </p>
                </div>
              </div>

              <!-- Nút tải file gốc -->
              <button
                @click="previewFile"
                :disabled="previewing"
                class="mt-3 flex items-center gap-1.5 px-3 py-2 bg-white border border-stone-200 rounded-lg text-xs font-medium text-stone-700 hover:bg-stone-50 transition disabled:opacity-50"
              >
                <div
                  v-if="previewing"
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
                {{ previewing ? 'Đang tải...' : 'Tải file gốc để ký' }}
              </button>
            </div>

            <!-- Audit log -->
            <div v-if="selectedRequest.logs?.length">
              <p class="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
                Lịch sử xử lý
              </p>
              <div class="space-y-2">
                <div
                  v-for="log in selectedRequest.logs"
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

            <!-- ── Form ký số ── -->
            <!-- ── Xác nhận ký số (không cần upload) ── -->
            <div
              v-if="canSign"
              class="border-2 border-dashed border-teal-200 rounded-xl p-5 bg-teal-50/50"
            >
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
                Sau khi xác nhận, hệ thống sẽ tự động tạo phiếu xác nhận ký số PDF và gửi Admin để
                phát hành cho sinh viên.
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
                  <strong>"{{ selectedRequest.document_category_label }}"</strong>
                  của sinh viên <strong>{{ selectedRequest.requester?.name }}</strong>
                  và đồng ý ký số xác nhận tài liệu này.
                </span>
              </label>

              <button
                @click="handleSign"
                :disabled="!confirmed || signing"
                class="w-full py-3 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 transition flex items-center justify-center gap-2"
              >
                <div
                  v-if="signing"
                  class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                />
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                {{ signing ? 'Đang xử lý...' : '✅ Xác nhận ký số' }}
              </button>
            </div>

            <!-- ── Từ chối ký ── -->
            <div v-if="canSign" class="border border-red-200 rounded-xl p-4">
              <p class="text-sm font-semibold text-red-700 mb-2">Từ chối ký tài liệu</p>
              <textarea
                v-model="rejectReason"
                rows="2"
                placeholder="Lý do từ chối (bắt buộc)..."
                class="w-full px-3 py-2 border border-stone-200 rounded-lg text-sm resize-none focus:ring-2 focus:ring-red-500 outline-none mb-2"
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
                {{ rejecting ? 'Đang xử lý...' : 'Từ chối ký' }}
              </button>
            </div>

            <!-- Đã ký — chờ Admin phát hành -->
            <div
              v-if="selectedRequest.status === 'signed'"
              class="border border-blue-200 rounded-xl p-4 bg-blue-50"
            >
              <p class="text-sm font-semibold text-blue-700 mb-1">✅ Đã ký thành công</p>
              <p class="text-xs text-blue-600">
                Đã ký lúc {{ formatDate(selectedRequest.signed_at) }}. Đang chờ Admin phát hành cho
                sinh viên.
              </p>
            </div>

            <!-- Hoàn thành -->
            <div
              v-if="selectedRequest.status === 'completed'"
              class="border border-emerald-200 rounded-xl p-4 bg-emerald-50"
            >
              <p class="text-sm font-semibold text-emerald-700 mb-1">🎉 Tài liệu đã phát hành</p>
              <p class="text-xs text-emerald-600">Sinh viên đã có thể tải tài liệu đã ký.</p>
            </div>

            <!-- Bị từ chối -->
            <div
              v-if="selectedRequest.status?.includes('rejected')"
              class="border border-red-200 rounded-xl p-4 bg-red-50"
            >
              <p class="text-sm font-semibold text-red-700 mb-1">Yêu cầu đã bị từ chối</p>
              <p v-if="selectedRequest.reject_reason" class="text-xs text-red-600 italic">
                "{{ selectedRequest.reject_reason }}"
              </p>
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
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const filterStatus = ref('')
const stats = ref({ total: 0, pending: 0, signed: 0, completed: 0 })

// Sign form
const fileInput = ref(null)
const signedFile = ref(null)
const dragging = ref(false)
const rejectReason = ref('')
const signing = ref(false)
const rejecting = ref(false)
const previewing = ref(false)
const confirmed = ref(false)
const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'forwarded', label: 'Chờ ký' },
  { value: 'lecturer_reviewing', label: 'Đang xem' },
  { value: 'signed', label: 'Đã ký' },
  { value: 'completed', label: 'Hoàn thành' },
  { value: 'rejected_by_lecturer', label: 'Đã từ chối' },
]

const canSign = computed(() =>
  ['forwarded', 'lecturer_reviewing'].includes(selectedRequest.value?.status),
)

onMounted(loadRequests)

async function loadRequests(page = 1) {
  loading.value = true
  try {
    const { data } = await axiosClient.get('/lecturer/sign-requests', {
      params: { status: filterStatus.value || undefined, page },
    })
    requests.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
    }
    // Tính stats từ danh sách
    calcStats(data.data)
  } catch {
    requests.value = []
  } finally {
    loading.value = false
  }
}

function calcStats(list) {
  // Load all để tính stats — dùng stats từ all requests
  // Nếu đang filter thì stats không chính xác → gọi riêng nếu cần
  stats.value = {
    total: pagination.value.total,
    pending: list.filter((r) => ['forwarded', 'lecturer_reviewing'].includes(r.status)).length,
    signed: list.filter((r) => r.status === 'signed').length,
    completed: list.filter((r) => r.status === 'completed').length,
  }
}

function handleFilter(value) {
  filterStatus.value = value
  loadRequests()
}

async function openDetail(req) {
  selectedRequest.value = null
  signedFile.value = null
  rejectReason.value = ''
  try {
    const { data } = await axiosClient.get(`/lecturer/sign-requests/${req.id}`)
    selectedRequest.value = data.data
  } catch {
    toast.error('Không thể tải chi tiết')
  }
}

function closeDetail() {
  selectedRequest.value = null
  signedFile.value = null
  rejectReason.value = ''
  confirmed.value = false
}

async function previewFile() {
  if (!selectedRequest.value) return
  previewing.value = true
  try {
    const { data } = await axiosClient.get(
      `/lecturer/sign-requests/${selectedRequest.value.id}/preview`,
    )
    window.open(data.url, '_blank')
  } catch {
    toast.error('Không thể tải file gốc')
  } finally {
    previewing.value = false
  }
}

function onFileChange(e) {
  signedFile.value = e.target.files[0] ?? null
}
function onDrop(e) {
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (
    file &&
    [
      'application/pdf',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ].includes(file.type)
  ) {
    signedFile.value = file
  } else {
    toast.error('Chỉ chấp nhận file PDF hoặc DOCX')
  }
}

async function handleSign() {
  if (!confirmed.value || !selectedRequest.value) return
  signing.value = true
  try {
    await axiosClient.post(`/lecturer/sign-requests/${selectedRequest.value.id}/sign`)
    // ← không cần body gì cả
    toast.success('Đã xác nhận ký số! Admin sẽ phát hành cho sinh viên.')
    closeDetail()
    await loadRequests()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi xác nhận ký số')
  } finally {
    signing.value = false
  }
}

async function handleReject() {
  if (!rejectReason.value.trim() || !selectedRequest.value) return
  rejecting.value = true
  try {
    await axiosClient.post(`/lecturer/sign-requests/${selectedRequest.value.id}/reject`, {
      reason: rejectReason.value,
    })
    toast.success('Đã từ chối yêu cầu ký số')
    closeDetail()
    await loadRequests()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi từ chối')
  } finally {
    rejecting.value = false
  }
}

function changePage(page) {
  if (page < 1 || page > pagination.value.last_page) return
  loadRequests(page)
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

function formatFileSize(bytes) {
  if (!bytes) return '0 KB'
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
</script>
