<template>
  <div>
    <!-- ── Khi chưa có assignmentId → hiển thị danh sách đợt nộp để chọn ── -->
    <div v-if="!props.assignmentId">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Duyệt bài nộp</h2>
        <p class="text-sm text-slate-500 mt-1">Chọn đợt nộp bài để xem và duyệt</p>
      </div>

      <div v-if="loadingList" class="flex justify-center py-20">
        <div
          class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
        />
      </div>

      <div v-else-if="!assignmentList.length" class="bg-white rounded-2xl border p-12 text-center">
        <p class="text-slate-400">Chưa có đợt nộp bài nào</p>
        <p class="text-xs text-slate-400 mt-1">Vui lòng chọn lớp ở thanh trên để xem danh sách</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="a in assignmentList"
          :key="a.id"
          @click="selectAssignment(a)"
          class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-indigo-200 transition cursor-pointer group"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1 flex-wrap">
                <h3 class="font-semibold text-slate-800 group-hover:text-indigo-700 transition">
                  {{ a.title }}
                </h3>
                <span
                  v-if="a.is_expired"
                  class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full"
                  >Hết hạn</span
                >
                <span
                  v-else
                  class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full"
                  >Đang mở</span
                >
              </div>
              <p class="text-xs text-slate-400">Hạn: {{ formatDate(a.deadline) }}</p>
            </div>
            <div class="flex items-center gap-3 text-center flex-shrink-0">
              <div>
                <p class="text-lg font-bold text-amber-500">{{ a.pending_count ?? 0 }}</p>
                <p class="text-[10px] text-slate-400">Chờ duyệt</p>
              </div>
              <div>
                <p class="text-lg font-bold text-slate-600">
                  {{ (a.group_count || 0) + (a.individual_count || 0) }}
                </p>
                <p class="text-[10px] text-slate-400">Đã nộp</p>
              </div>
              <svg
                class="w-4 h-4 text-slate-400 group-hover:text-indigo-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Khi có assignmentId → hiển thị danh sách bài nộp ── -->
    <div v-else>
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-slate-800">Duyệt bài nộp</h2>
          <p class="text-sm text-slate-500 mt-1">{{ assignment?.title }}</p>
        </div>
        <button
          @click="$router.back()"
          class="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Quay lại
        </button>
      </div>

      <!-- Stats -->
      <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
          <p class="text-2xl font-bold text-slate-700">{{ stats.total }}</p>
          <p class="text-xs text-slate-400 mt-1">Tổng</p>
        </div>
        <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
          <p class="text-2xl font-bold text-amber-500">{{ stats.pending }}</p>
          <p class="text-xs text-slate-400 mt-1">Chờ duyệt</p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-200 p-4 text-center">
          <p class="text-2xl font-bold text-emerald-600">{{ stats.approved }}</p>
          <p class="text-xs text-slate-400 mt-1">Đã chấp nhận</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4 text-center">
          <p class="text-2xl font-bold text-red-500">{{ stats.rejected }}</p>
          <p class="text-xs text-slate-400 mt-1">Đã từ chối</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
          <p class="text-2xl font-bold text-orange-500">{{ stats.late }}</p>
          <p class="text-xs text-slate-400 mt-1">Trễ hạn</p>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-wrap items-center gap-3 mb-4">
        <!-- ✅ Chỉ 1 filter status -->
        <div class="flex gap-1 bg-slate-100 rounded-xl p-1">
          <button
            v-for="f in statusFilters"
            :key="f.value"
            @click="handleFilter('status', f.value)"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
            :class="
              filterStatus === f.value
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-700'
            "
          >
            {{ f.label }}
          </button>
        </div>

        <!-- Filter type -->
        <div class="flex gap-1 bg-slate-100 rounded-xl p-1">
          <button
            v-for="f in typeFilters"
            :key="f.value"
            @click="handleFilter('type', f.value)"
            class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
            :class="
              filterType === f.value
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-700'
            "
          >
            {{ f.label }}
          </button>
        </div>

        <div class="flex-1" />

        <button
          v-if="stats?.pending > 0"
          @click="openBulkReview('approved')"
          class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700 transition flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>
          Chấp nhận tất cả ({{ stats.pending }})
        </button>
        <button
          v-if="stats?.pending > 0"
          @click="openBulkReview('rejected')"
          class="px-4 py-2 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
          Từ chối tất cả
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-16">
        <div
          class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
        />
      </div>

      <!-- Submission list -->
      <div v-else class="space-y-3">
        <div
          v-if="!submissions.length"
          class="bg-white rounded-2xl border p-12 text-center text-slate-400 text-sm"
        >
          Không có bài nộp nào
        </div>

        <div
          v-for="sub in submissions"
          :key="sub.id"
          class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
        >
          <div class="p-5 flex items-start gap-4">
            <div
              class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-700 flex-shrink-0"
            >
              {{ sub.submitter_name?.charAt(0) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="font-semibold text-slate-800">{{ sub.submitter_name }}</span>
                <span v-if="sub.student?.code" class="text-xs text-slate-400 font-mono">{{
                  sub.student.code
                }}</span>
                <span
                  class="px-2 py-0.5 text-xs font-bold rounded-full"
                  :class="{
                    'bg-amber-100 text-amber-700': sub.status === 'pending',
                    'bg-emerald-100 text-emerald-700': sub.status === 'approved',
                    'bg-red-100 text-red-700': sub.status === 'rejected',
                  }"
                >
                  {{ sub.status_label }}
                </span>
                <span
                  v-if="sub.is_late"
                  class="px-2 py-0.5 bg-orange-100 text-orange-700 text-xs font-bold rounded-full"
                  >Trễ</span
                >
              </div>
              <div class="flex items-center gap-3 text-xs text-slate-400">
                <span>📎 {{ sub.file_name }}</span>
                <span>{{ sub.file_size }}</span>
                <span>{{ formatDate(sub.submitted_at) }}</span>
              </div>
              <div
                v-if="sub.status !== 'pending'"
                class="mt-3 p-3 rounded-xl text-sm"
                :class="sub.status === 'approved' ? 'bg-emerald-50' : 'bg-red-50'"
              >
                <div class="flex items-center gap-3 flex-wrap">
                  <span
                    v-if="sub.score !== null"
                    class="font-bold text-lg"
                    :class="sub.status === 'approved' ? 'text-emerald-700' : 'text-red-700'"
                  >
                    {{ sub.score }}/10
                  </span>
                  <span class="text-xs text-slate-500"
                    >Duyệt bởi {{ sub.reviewer }} lúc {{ formatDate(sub.reviewed_at) }}</span
                  >
                </div>
                <p v-if="sub.feedback" class="mt-1.5 text-slate-600 italic">{{ sub.feedback }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <a
                :href="downloadUrl(sub.id)"
                target="_blank"
                class="p-2 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition"
                title="Tải file"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  />
                </svg>
              </a>
              <button
                v-if="sub.status === 'pending'"
                @click="openReview(sub, 'approved')"
                class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-medium hover:bg-emerald-700 transition"
              >
                Chấp nhận
              </button>
              <button
                v-if="sub.status === 'pending'"
                @click="openReview(sub, 'rejected')"
                class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-medium hover:bg-red-700 transition"
              >
                Từ chối
              </button>
              <button
                v-if="sub.status !== 'pending'"
                @click="openReview(sub, sub.status)"
                class="px-3 py-1.5 border border-slate-300 text-slate-600 rounded-lg text-xs font-medium hover:bg-slate-50 transition"
              >
                Sửa
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Duyệt 1 bài -->
    <Teleport to="body">
      <div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showReviewModal = false"
        />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-1">
            {{ reviewForm.status === 'approved' ? '✅ Chấp nhận bài nộp' : '❌ Từ chối bài nộp' }}
          </h3>
          <p class="text-sm text-slate-500 mb-5">{{ reviewingSubmission?.submitter_name }}</p>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1"
                >Điểm số <span class="text-slate-400">(0–10, tuỳ chọn)</span></label
              >
              <input
                v-model.number="reviewForm.score"
                type="number"
                min="0"
                max="10"
                step="0.5"
                placeholder="VD: 8.5"
                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1"
                >Nhận xét <span class="text-slate-400">(tuỳ chọn)</span></label
              >
              <textarea
                v-model="reviewForm.feedback"
                rows="4"
                placeholder="Nhận xét chi tiết..."
                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
            <div
              class="flex items-center gap-2 text-xs text-slate-400 bg-slate-50 rounded-xl px-3 py-2"
            >
              <svg
                class="w-4 h-4 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              Sinh viên sẽ nhận email thông báo kết quả
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button
              @click="showReviewModal = false"
              class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
              Hủy
            </button>
            <button
              @click="submitReview"
              :disabled="reviewing"
              class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white disabled:opacity-50 flex items-center justify-center gap-2 transition"
              :class="
                reviewForm.status === 'approved'
                  ? 'bg-emerald-600 hover:bg-emerald-700'
                  : 'bg-red-600 hover:bg-red-700'
              "
            >
              <div
                v-if="reviewing"
                class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
              />
              {{
                reviewing
                  ? 'Đang xử lý...'
                  : reviewForm.status === 'approved'
                    ? 'Xác nhận chấp nhận'
                    : 'Xác nhận từ chối'
              }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Duyệt tất cả -->
    <Teleport to="body">
      <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showBulkModal = false" />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-1">
            {{ bulkForm.status === 'approved' ? '✅ Chấp nhận tất cả' : '❌ Từ chối tất cả' }}
          </h3>
          <p class="text-sm text-slate-500 mb-5">
            Áp dụng cho {{ stats?.pending }} bài đang chờ duyệt
          </p>
          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1"
              >Nhận xét chung <span class="text-slate-400">(tuỳ chọn)</span></label
            >
            <textarea
              v-model="bulkForm.feedback"
              rows="3"
              placeholder="Nhận xét áp dụng cho tất cả..."
              class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
          <div class="flex gap-3 mt-6">
            <button
              @click="showBulkModal = false"
              class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
              Hủy
            </button>
            <button
              @click="submitBulkReview"
              :disabled="reviewing"
              class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white disabled:opacity-50 flex items-center justify-center gap-2"
              :class="
                bulkForm.status === 'approved'
                  ? 'bg-emerald-600 hover:bg-emerald-700'
                  : 'bg-red-600 hover:bg-red-700'
              "
            >
              <div
                v-if="reviewing"
                class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
              />
              {{ reviewing ? 'Đang xử lý...' : 'Xác nhận' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast.show"
        class="fixed bottom-6 right-6 z-50 px-5 py-3.5 rounded-2xl shadow-lg text-white text-sm font-medium"
        :class="toast.type === 'success' ? 'bg-gray-900' : 'bg-red-600'"
      >
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'
import { lecturerAssignmentApi } from '@/api/lecturer/lecturerAssignmentApi'
import axiosClient from '@/api/axiosClient'

const props = defineProps({
  assignmentId: { type: Number, default: null },
})

const router = useRouter()
const lecturerStore = useLecturerStore()
const assignmentStore = useLecturerAssignmentStore()

// ── State cho màn danh sách (khi không có assignmentId) ──
const assignmentList = ref([])
const loadingList = ref(false)

// ── State cho màn duyệt ──
const assignment = ref(null)
const submissions = ref([])
const stats = ref(null)
const loading = ref(false)
const reviewing = ref(false)
const filterStatus = ref('')
const filterType = ref('')
const showReviewModal = ref(false)
const showBulkModal = ref(false)
const reviewingSubmission = ref(null)
const reviewForm = ref({ status: 'approved', score: null, feedback: '' })
const bulkForm = ref({ status: 'approved', feedback: '' })
const toast = ref({ show: false, type: 'success', message: '' })

const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'pending', label: 'Chờ duyệt' },
  { value: 'approved', label: 'Đã chấp nhận' },
  { value: 'rejected', label: 'Đã từ chối' },
]
const typeFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'group', label: 'Nhóm' },
  { value: 'individual', label: 'Cá nhân' },
]

onMounted(() => {
  if (props.assignmentId) {
    loadSubmissions()
  } else {
    loadAssignmentList()
  }
})

// Reload khi classId thay đổi (chọn lớp khác ở topbar)
watch(
  () => props.assignmentId,
  (id) => {
    if (id) {
      loadSubmissions()
    } else {
      loadAssignmentList()
    }
  },
  { immediate: true }, // ← immediate thay thế onMounted
)
watch(
  () => lecturerStore.selectedClassId,
  (classId) => {
    // Chỉ reload danh sách khi đang ở màn chọn đợt nộp (không có assignmentId)
    if (!props.assignmentId && classId) {
      loadAssignmentList()
    }
  },
)
// ── Load danh sách đợt nộp (màn chọn) ──
async function loadAssignmentList() {
  const classId = lecturerStore.selectedClassId
  if (!classId) {
    assignmentList.value = []
    return
  }
  loadingList.value = true
  try {
    const { data } = await lecturerAssignmentApi.getByClass(classId)
    assignmentList.value = data
  } catch (e) {
    assignmentList.value = []
  } finally {
    loadingList.value = false
  }
}

function selectAssignment(a) {
  router.push({ name: 'lecturer-assignment-review', params: { assignmentId: a.id } })
}

// ── Load danh sách bài nộp của đợt ──
async function loadSubmissions() {
  if (!props.assignmentId) return
  loading.value = true
  try {
    const params = {}
    if (filterStatus.value) params.status = filterStatus.value
    if (filterType.value) params.type = filterType.value

    const { data } = await axiosClient.get(
      `/lecturer/assignments/${props.assignmentId}/submissions`,
      { params },
    )
    assignment.value = data.assignment
    submissions.value = data.submissions
    stats.value = data.stats
  } finally {
    loading.value = false
  }
}

function handleFilter(field, value) {
  if (field === 'status') filterStatus.value = value
  if (field === 'type') filterType.value = value
  loadSubmissions()
}

function openReview(sub, status) {
  reviewingSubmission.value = sub
  reviewForm.value = { status, score: sub.score ?? null, feedback: sub.feedback ?? '' }
  showReviewModal.value = true
}

function openBulkReview(status) {
  bulkForm.value = { status, feedback: '' }
  showBulkModal.value = true
}

async function submitReview() {
  reviewing.value = true
  try {
    await axiosClient.patch(
      `/lecturer/submissions/${reviewingSubmission.value.id}/review`,
      reviewForm.value,
    )
    showReviewModal.value = false
    showToast(
      reviewForm.value.status === 'approved' ? 'Đã chấp nhận bài nộp' : 'Đã từ chối bài nộp',
    )
    await loadSubmissions()
  } catch (e) {
    showToast(e.response?.data?.message ?? 'Có lỗi xảy ra', 'error')
  } finally {
    reviewing.value = false
  }
}

async function submitBulkReview() {
  reviewing.value = true
  try {
    const { data } = await axiosClient.post(
      `/lecturer/assignments/${props.assignmentId}/review-all`,
      bulkForm.value,
    )
    showBulkModal.value = false
    showToast(data.message)
    await loadSubmissions()
  } catch (e) {
    showToast(e.response?.data?.message ?? 'Có lỗi xảy ra', 'error')
  } finally {
    reviewing.value = false
  }
}

function downloadUrl(submissionId) {
  return lecturerAssignmentApi.downloadUrl(submissionId)
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function showToast(message, type = 'success') {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
