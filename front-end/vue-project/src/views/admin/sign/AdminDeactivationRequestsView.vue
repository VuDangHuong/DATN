<!-- src/views/admin/AdminDeactivationRequestsView.vue -->
<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-stone-800">Yêu cầu vô hiệu hóa chữ ký số</h2>
        <p class="text-base text-stone-500 mt-1">Duyệt yêu cầu vô hiệu hóa từ giảng viên</p>
      </div>
      <router-link
        to="/admin/sign-profiles"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
      >
        <SvgICon name="back-arrow" class="w-5 h-5" /> Quản lý chữ ký số
      </router-link>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-stone-200 p-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[240px]">
        <input
          v-model="searchInput"
          @input="onSearchInput"
          type="text"
          placeholder="Tìm theo tên, mã giảng viên..."
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

    <!-- List -->
    <div v-if="loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-rose-200 border-t-rose-600 rounded-full animate-spin" />
    </div>

    <div
      v-else-if="!requests.length"
      class="bg-white rounded-2xl border border-stone-200 p-12 text-center"
    >
      <p class="text-sm text-stone-400">Không có yêu cầu nào</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="req in requests"
        :key="req.id"
        class="bg-white rounded-2xl border border-stone-200 p-5"
      >
        <!-- Header request -->
        <div class="flex items-start justify-between gap-3 mb-3">
          <div class="flex items-start gap-3 min-w-0 flex-1">
            <div
              class="w-10 h-10 rounded-full bg-stone-100 flex items-center justify-center text-sm font-bold text-stone-600 overflow-hidden flex-shrink-0"
            >
              <img
                v-if="req.lecturer?.avatar_url"
                :src="req.lecturer.avatar_url"
                class="w-full h-full object-cover"
              />
              <span v-else>{{ req.lecturer?.name?.charAt(0) }}</span>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-bold text-stone-800 truncate">{{ req.lecturer?.name }}</p>
              <p class="text-base text-stone-500 truncate">
                {{ req.lecturer?.code }} · {{ req.lecturer?.email }}
              </p>
              <p class="text-[10px] text-stone-400 mt-0.5">Gửi {{ formatDate(req.created_at) }}</p>
            </div>
          </div>
          <span
            class="px-2 py-1 rounded-full text-[10px] font-bold uppercase flex-shrink-0"
            :class="statusClass(req.status)"
          >
            {{ statusLabel(req.status) }}
          </span>
        </div>

        <!-- Reason -->
        <div class="bg-stone-50 rounded-xl p-3 mb-3">
          <p class="text-[10px] font-bold text-stone-500 uppercase mb-1">Lý do từ giảng viên</p>
          <p class="text-sm text-stone-700 whitespace-pre-wrap break-words">{{ req.reason }}</p>
        </div>

        <!-- Profile info -->
        <div class="grid grid-cols-2 gap-2 text-base mb-3">
          <div class="p-2 bg-stone-50 rounded-lg">
            <p class="text-stone-400">Chủ sở hữu</p>
            <p class="font-medium text-stone-700 truncate">{{ req.profile?.subject_cn || '—' }}</p>
          </div>
          <div class="p-2 bg-stone-50 rounded-lg">
            <p class="text-stone-400">Hết hạn</p>
            <p class="font-medium text-stone-700">{{ formatDate(req.profile?.cert_valid_to) }}</p>
          </div>
        </div>

        <!-- Admin note (nếu đã giải quyết) -->
        <div v-if="req.admin_note" class="bg-red-50 border border-red-200 rounded-xl p-3 mb-3">
          <p class="text-[10px] font-bold text-red-700 uppercase mb-1">
            Ghi chú của Admin ({{ req.admin?.name }})
          </p>
          <p class="text-sm text-red-700">{{ req.admin_note }}</p>
        </div>

        <!-- Actions (chỉ khi pending) -->
        <div v-if="req.status === 'pending'" class="flex gap-2 pt-3 border-t border-stone-100">
          <button
            @click="handleReject(req)"
            class="flex flex-1 items-center justify-center gap-1.5 py-2 border border-red-200 text-red-700 rounded-xl text-sm font-semibold hover:bg-red-50 transition"
          >
            <SvgICon name="close" class="w-5 h-5 text-red-500" /> Từ chối
          </button>
          <button
            @click="handleApprove(req)"
            class="flex flex-1 items-center justify-center gap-1.5 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700"
          >
            <SvgICon name="check" class="w-5 h-5 text-green-500" /> Chấp thuận vô hiệu
          </button>
        </div>

        <!-- Resolved info -->
        <p
          v-else-if="req.resolved_at"
          class="text-base text-stone-400 pt-2 border-t border-stone-100"
        >
          Xử lý lúc {{ formatDate(req.resolved_at) }} bởi {{ req.admin?.name || '—' }}
        </p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="requestsPagination.last_page > 1" class="flex items-center justify-between">
      <p class="text-base text-stone-500">
        Trang {{ requestsPagination.current_page }}/{{ requestsPagination.last_page }} ·
        {{ requestsPagination.total }} kết quả
      </p>
      <div class="flex gap-1">
        <button
          :disabled="requestsPagination.current_page === 1"
          @click="changePage(requestsPagination.current_page - 1)"
          class="px-3 py-1 border border-stone-200 rounded-md text-base hover:bg-stone-50 disabled:opacity-50"
        >
          ← Trước
        </button>
        <button
          :disabled="requestsPagination.current_page >= requestsPagination.last_page"
          @click="changePage(requestsPagination.current_page + 1)"
          class="px-3 py-1 border border-stone-200 rounded-md text-base hover:bg-stone-50 disabled:opacity-50"
        >
          Sau →
        </button>
      </div>
    </div>

    <!-- Reject modal -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeRejectModal" />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-stone-800 mb-1">TỪ CHỐI YÊU CẦU</h3>
          <p class="text-base text-stone-500 mb-4">
            Giảng viên <strong>{{ rejectingRequest?.lecturer?.name }}</strong>
          </p>

          <label class="block text-sm font-medium text-stone-700 mb-1">
            Lý do từ chối <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="rejectNote"
            rows="4"
            maxlength="1000"
            placeholder="Nhập lý do từ chối yêu cầu..."
            class="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-red-500 outline-none"
          />
          <p class="text-base text-stone-400 mt-1">Tối thiểu 5 ký tự</p>

          <div class="flex gap-3 mt-4">
            <button
              @click="closeRejectModal"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              @click="confirmReject"
              :disabled="rejectNote.length < 5 || rejecting"
              class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 disabled:opacity-50"
            >
              {{ rejecting ? 'Đang xử lý...' : 'Xác nhận từ chối' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
    <ConfirmModal
      v-model="confirmState.show"
      :title="confirmState.title"
      :message="confirmState.message"
      :item-name="confirmState.itemName"
      :warning-text="confirmState.warningText"
      :confirm-text="confirmState.confirmText"
      :cancel-text="confirmState.cancelText"
      :variant="confirmState.variant"
      :loading="confirmState.loading"
      @confirm="_handleConfirm"
      @cancel="_handleCancel"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useToastStore } from '@/stores/toast'
import { useAdminSignProfileStore } from '@/stores/admin/sign/adminSignProfileStore'
import SvgICon from '@/components/icons/SVG.vue'
import { useConfirm } from '@/composables/useConfirm'
import ConfirmModal from '@/components/common/ConfirmModal.vue'
const {
  state: confirmState,
  confirm,
  setLoading: setConfirmLoading,
  close: closeConfirm,
  _handleConfirm,
  _handleCancel,
} = useConfirm()
const store = useAdminSignProfileStore()
const toast = useToastStore()

const { requests, requestsPagination, loading } = storeToRefs(store)

const searchInput = ref('')
const filterStatus = ref('pending') // Default: chỉ hiện pending
let searchTimer = null

// Reject modal
const showRejectModal = ref(false)
const rejectingRequest = ref(null)
const rejectNote = ref('')
const rejecting = ref(false)

const statusFilters = [
  { value: 'pending', label: 'Chờ duyệt' },
  { value: 'approved', label: 'Đã duyệt' },
  { value: 'rejected', label: 'Đã từ chối' },
  { value: '', label: 'Tất cả' },
]

onMounted(() => {
  store.fetchRequests({ status: 'pending' })
})

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    store.fetchRequests({ search: searchInput.value, status: filterStatus.value, page: 1 })
  }, 400)
}

function changeFilter(value) {
  filterStatus.value = value
  store.fetchRequests({ search: searchInput.value, status: value, page: 1 })
}

function changePage(page) {
  store.fetchRequests({
    search: searchInput.value,
    status: filterStatus.value,
    page,
  })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ── Approve ───────────────────
async function handleApprove(req) {
  if (!req || !req.id) {
    toast.error('Yêu cầu không hợp lệ')
    return
  }

  // ✅ Dùng confirm() variant 'warning' vì chấp thuận = hành động quan trọng
  const ok = await confirm({
    title: 'Chấp thuận vô hiệu hóa',
    message:
      'Sau khi chấp thuận, chữ ký số của giảng viên sẽ ngừng hoạt động hoàn toàn. Giảng viên sẽ không thể ký tài liệu cho đến khi đăng ký chữ ký mới.',
    itemName: req.lecturer?.name,
    warningText: req.lecturer?.code ? `Mã GV: ${req.lecturer.code}` : '',
    confirmText: 'Chấp thuận',
    cancelText: 'Hủy',
    variant: 'warning', // ← Vàng (action nghiêm trọng nhưng không phải xóa)
  })

  if (!ok) return

  setConfirmLoading(true)
  try {
    const result = await store.approveRequest(req.id)
    if (result.success) {
      toast.success(result.message)
      await store.fetchRequests({
        status: filterStatus.value,
        search: searchInput.value,
      })
    } else {
      toast.error(result.message || 'Xử lý thất bại')
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Lỗi khi chấp thuận')
  } finally {
    closeConfirm()
  }
}

// ── Reject ────────────────────
function handleReject(req) {
  rejectingRequest.value = req
  rejectNote.value = ''
  showRejectModal.value = true
}

function closeRejectModal() {
  showRejectModal.value = false
  rejectingRequest.value = null
  rejectNote.value = ''
}

async function confirmReject() {
  if (rejectNote.value.length < 5) return

  rejecting.value = true
  const result = await store.rejectRequest(rejectingRequest.value.id, rejectNote.value)
  rejecting.value = false

  if (result.success) {
    toast.success(result.message)
    closeRejectModal()
    await store.fetchRequests({ status: filterStatus.value, search: searchInput.value })
  } else {
    toast.error(result.message || 'Xử lý thất bại')
  }
}

// ── Formatters ─────────────────
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

function statusClass(s) {
  return (
    {
      pending: 'bg-amber-100 text-amber-700',
      approved: 'bg-emerald-100 text-emerald-700',
      rejected: 'bg-red-100 text-red-700',
    }[s] || 'bg-stone-100 text-stone-600'
  )
}

function statusLabel(s) {
  return (
    {
      pending: 'Chờ duyệt',
      approved: 'Đã duyệt',
      rejected: 'Từ chối',
    }[s] || s
  )
}
</script>
