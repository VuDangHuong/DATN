<template>
  <div>
    <!-- ── Chưa có assignmentId → danh sách đợt nộp ── -->
    <div v-if="!props.assignmentId">
      <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Duyệt bài nộp</h2>
        <p class="text-base text-slate-500 mt-1">Chọn đợt nộp bài để xem và duyệt</p>
      </div>

      <div v-if="loadingList" class="flex justify-center py-20">
        <div
          class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
        />
      </div>

      <div v-else-if="!assignmentList.length" class="bg-white rounded-2xl border p-12 text-center">
        <p class="text-slate-400">Chưa có đợt nộp bài nào</p>
        <p class="text-base text-slate-400 mt-1">Vui lòng chọn lớp ở thanh trên để xem danh sách</p>
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
                  class="px-2 py-0.5 bg-red-100 text-red-700 text-base font-bold rounded-full"
                  >Hết hạn</span
                >
                <span
                  v-else
                  class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-base font-bold rounded-full"
                  >Đang mở</span
                >
              </div>
              <p class="text-base text-slate-400">Hạn: {{ formatDate(a.deadline) }}</p>
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

    <!-- ── Có assignmentId → danh sách bài nộp ── -->
    <div v-else>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-slate-800">Duyệt bài nộp</h2>
          <p class="text-base text-slate-500 mt-1">{{ assignment?.title }}</p>
        </div>
        <button
          @click="$router.back()"
          class="inline-flex items-center gap-1 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-base font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
        >
          <SvgIcon name="back-arrow" class="w-4 h-4" />
          Quay lại
        </button>
      </div>

      <!-- Stats -->
      <div v-if="stats" class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
          <p class="text-2xl font-bold text-slate-700">{{ stats.total }}</p>
          <p class="text-base text-slate-400 mt-1">Tổng</p>
        </div>
        <div class="bg-white rounded-xl border border-amber-200 p-4 text-center">
          <p class="text-2xl font-bold text-amber-500">{{ stats.pending }}</p>
          <p class="text-base text-slate-400 mt-1">Chờ duyệt</p>
        </div>
        <div class="bg-white rounded-xl border border-emerald-200 p-4 text-center">
          <p class="text-2xl font-bold text-emerald-600">{{ stats.approved }}</p>
          <p class="text-base text-slate-400 mt-1">Đã chấp nhận</p>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4 text-center">
          <p class="text-2xl font-bold text-red-500">{{ stats.rejected }}</p>
          <p class="text-base text-slate-400 mt-1">Đã từ chối</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
          <p class="text-2xl font-bold text-orange-500">{{ stats.late }}</p>
          <p class="text-base text-slate-400 mt-1">Trễ hạn</p>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex flex-wrap items-center gap-3 mb-4">
        <div class="flex gap-1 bg-slate-100 rounded-xl p-1">
          <button
            v-for="f in statusFilters"
            :key="f.value"
            @click="handleFilter('status', f.value)"
            class="px-3 py-1.5 rounded-lg text-base font-medium transition"
            :class="
              filterStatus === f.value
                ? 'bg-white text-slate-800 shadow-sm'
                : 'text-slate-500 hover:text-slate-700'
            "
          >
            {{ f.label }}
          </button>
        </div>
        <div class="flex gap-1 bg-slate-100 rounded-xl p-1">
          <button
            v-for="f in typeFilters"
            :key="f.value"
            @click="handleFilter('type', f.value)"
            class="px-3 py-1.5 rounded-lg text-base font-medium transition"
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
          class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-base font-medium hover:bg-emerald-700 transition flex items-center gap-2"
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
          class="px-4 py-2 bg-red-600 text-white rounded-xl text-base font-medium hover:bg-red-700 transition flex items-center gap-2"
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
          class="bg-white rounded-2xl border p-12 text-center text-slate-400 text-base"
        >
          Không có bài nộp nào
        </div>

        <div
          v-for="sub in submissions"
          :key="sub.id"
          class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
        >
          <div class="p-5 flex items-start gap-4">
            <!-- Avatar -->
            <div
              class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-base font-bold text-indigo-700 flex-shrink-0"
            >
              {{ sub.submitter_name?.charAt(0) }}
            </div>

            <div class="flex-1 min-w-0">
              <!-- Tên + badges -->
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="font-semibold text-slate-800">{{ sub.submitter_name }}</span>
                <span v-if="sub.student?.code" class="text-base text-slate-400 font-mono">{{
                  sub.student.code
                }}</span>
                <!-- Badge nhóm -->
                <span
                  v-if="sub.submitter_type === 'group'"
                  class="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded"
                >
                  Nhóm
                </span>
                <span
                  class="px-2 py-0.5 text-base font-bold rounded-full"
                  :class="{
                    'bg-amber-100 text-amber-700': sub.status === 'pending',
                    'bg-emerald-100 text-emerald-700': sub.status === 'approved',
                    'bg-red-100 text-red-700': sub.status === 'rejected',
                  }"
                >
                  {{ statusLabel(sub.status) }}
                </span>
                <span
                  v-if="sub.is_late"
                  class="px-2 py-0.5 bg-orange-100 text-orange-700 text-base font-bold rounded-full"
                  >Trễ</span
                >
              </div>

              <!-- File info -->
              <div class="flex items-center gap-3 text-base text-slate-400">
                <span class="flex items-center gap-1"
                  ><SvgIcon name="document" class="w-4 h-4 text-blue-600" />
                  {{ sub.file_name }}</span
                >
                <span>{{ sub.file_size }}</span>
                <span>{{ formatDate(sub.submitted_at) }}</span>
              </div>

              <!-- Kết quả duyệt cá nhân -->
              <div
                v-if="sub.status !== 'pending' && sub.submitter_type !== 'group'"
                class="mt-3 p-3 rounded-xl text-base"
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
                  <span class="text-base text-slate-500"
                    >Duyệt bởi {{ sub.reviewer }} lúc {{ formatDate(sub.reviewed_at) }}</span
                  >
                </div>
                <p v-if="sub.feedback" class="mt-1.5 text-slate-600 italic">{{ sub.feedback }}</p>
              </div>

              <!--Kết quả duyệt nhóm — hiện điểm từng thành viên -->
              <div
                v-if="sub.status !== 'pending' && sub.submitter_type === 'group'"
                class="mt-3 p-3 rounded-xl"
                :class="sub.status === 'approved' ? 'bg-emerald-50' : 'bg-red-50'"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="text-base font-semibold text-slate-600">Điểm từng thành viên</span>
                  <span class="text-base text-slate-400">Duyệt bởi {{ sub.reviewer }}</span>
                </div>
                <div v-if="sub.member_grades?.length" class="space-y-1.5">
                  <div
                    v-for="g in sub.member_grades"
                    :key="g.student_id"
                    class="flex items-center justify-between bg-white/70 rounded-lg px-3 py-1.5"
                  >
                    <div class="flex items-center gap-2">
                      <span class="text-base font-medium text-slate-700">{{ g.student_name }}</span>
                      <span class="text-[10px] text-slate-400 font-mono">{{ g.student_code }}</span>
                      <span
                        v-if="g.role === 'leader'"
                        class="px-1 py-0.5 bg-amber-100 text-amber-700 text-[9px] font-bold rounded"
                      >
                        Trưởng nhóm
                      </span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span
                        v-if="g.score !== null"
                        class="text-base font-bold"
                        :class="sub.status === 'approved' ? 'text-emerald-700' : 'text-red-700'"
                      >
                        {{ g.score }}/10
                      </span>
                      <span v-else class="text-base text-slate-400 italic">Chưa có điểm</span>
                    </div>
                  </div>
                </div>
                <p v-if="sub.feedback" class="mt-2 text-base text-slate-600 italic">
                  {{ sub.feedback }}
                </p>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="flex items-center gap-2 flex-shrink-0">
              <button
                @click="handleDownload(sub)"
                :disabled="downloadingId === sub.id"
                class="p-2 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition"
                title="Tải file"
              >
                <div
                  v-if="downloadingId === sub.id"
                  class="w-4 h-4 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"
                />
                <SvgIcon name="download" class="w-4 h-4" />
              </button>
              <button
                v-if="sub.status === 'pending'"
                @click="openReview(sub, 'approved')"
                class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-base font-medium hover:bg-emerald-700 transition"
              >
                Chấp nhận
              </button>
              <button
                v-if="sub.status === 'pending'"
                @click="openReview(sub, 'rejected')"
                class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-base font-medium hover:bg-red-700 transition"
              >
                Từ chối
              </button>
              <button
                v-if="sub.status !== 'pending'"
                @click="openReview(sub, sub.status)"
                class="px-3 py-1.5 border border-slate-300 text-slate-600 rounded-lg text-base font-medium hover:bg-slate-50 transition"
              >
                Sửa
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Modal: Duyệt 1 bài (dùng ReviewModal component) ── -->
    <ReviewModal
      :show="showReviewModal"
      :submission="reviewingSubmission"
      :status="reviewStatus"
      @close="showReviewModal = false"
      @saved="onReviewSaved"
    />

    <!-- ── Modal: Duyệt tất cả ── -->
    <Teleport to="body">
      <div v-if="showBulkModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showBulkModal = false" />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-slate-800 mb-1">
            {{ bulkForm.status === 'approved' ? '✅ Chấp nhận tất cả' : '❌ Từ chối tất cả' }}
          </h3>
          <p class="text-base text-slate-500 mb-5">
            Áp dụng cho {{ stats?.pending }} bài đang chờ duyệt
          </p>
          <div>
            <label class="block text-base font-medium text-slate-600 mb-1">
              Nhận xét chung <span class="text-slate-400">(tuỳ chọn)</span>
            </label>
            <textarea
              v-model="bulkForm.feedback"
              rows="3"
              placeholder="Nhận xét áp dụng cho tất cả..."
              class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-base resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
          <div class="flex gap-3 mt-6">
            <button
              @click="showBulkModal = false"
              class="flex-1 py-2.5 border border-slate-200 rounded-xl text-base font-medium text-slate-600 hover:bg-slate-50"
            >
              Hủy
            </button>
            <button
              @click="submitBulkReview"
              :disabled="reviewing"
              class="flex-1 py-2.5 rounded-xl text-base font-semibold text-white disabled:opacity-50 flex items-center justify-center gap-2"
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
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { lecturerAssignmentApi } from '@/api/lecturer/lecturerAssignmentApi'
import { useToastStore } from '@/stores/toast'
import axiosClient from '@/api/axiosClient'
import ReviewModal from '../components/review/ReviewModal.vue'
import SvgIcon from '@/components/icons/SVG.vue'

const props = defineProps({
  assignmentId: { type: Number, default: null },
})

const router = useRouter()
const lecturerStore = useLecturerStore()
const toast = useToastStore()

const assignmentList = ref([])
const loadingList = ref(false)
const assignment = ref(null)
const submissions = ref([])
const stats = ref(null)
const loading = ref(false)
const reviewing = ref(false)
const downloadingId = ref(null)
const filterStatus = ref('')
const filterType = ref('')
const showReviewModal = ref(false)
const showBulkModal = ref(false)
const reviewingSubmission = ref(null)
const reviewStatus = ref('approved')
const bulkForm = ref({ status: 'approved', feedback: '' })

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
  if (props.assignmentId) loadSubmissions()
  else loadAssignmentList()
})

watch(
  () => props.assignmentId,
  (id) => {
    if (id) loadSubmissions()
    else loadAssignmentList()
  },
  { immediate: true },
)

watch(
  () => lecturerStore.selectedClassId,
  (classId) => {
    if (!props.assignmentId && classId) loadAssignmentList()
  },
)

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
  } catch {
    assignmentList.value = []
  } finally {
    loadingList.value = false
  }
}

function selectAssignment(a) {
  router.push({ name: 'lecturer-assignment-review', params: { assignmentId: a.id } })
}
function statusLabel(s) {
  return (
    {
      pending: 'Chờ duyệt',
      approved: 'Đã chấp nhận',
      rejected: 'Đã từ chối',
    }[s] || s
  )
}
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

// ✅ Mở ReviewModal — truyền submission + status
function openReview(sub, status) {
  reviewingSubmission.value = sub
  reviewStatus.value = status
  showReviewModal.value = true
}

// ✅ Callback khi ReviewModal lưu thành công
function onReviewSaved(updatedSub) {
  // Cập nhật submission trong list ngay không cần reload toàn bộ
  const idx = submissions.value.findIndex((s) => s.id === updatedSub.id)
  if (idx !== -1) submissions.value[idx] = updatedSub
  // Reload stats
  loadSubmissions()
}

function openBulkReview(status) {
  bulkForm.value = { status, feedback: '' }
  showBulkModal.value = true
}

async function submitBulkReview() {
  reviewing.value = true
  try {
    const { data } = await axiosClient.post(
      `/lecturer/assignments/${props.assignmentId}/review-all`,
      bulkForm.value,
    )
    showBulkModal.value = false
    toast.success(data.message)
    await loadSubmissions()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Có lỗi xảy ra')
  } finally {
    reviewing.value = false
  }
}

async function handleDownload(sub) {
  downloadingId.value = sub.id
  try {
    const response = await axiosClient.get(`/lecturer/submissions/${sub.id}/download`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = sub.file_name ?? 'download'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
    toast.success(`Đã tải: ${sub.file_name}`)
  } catch {
    toast.error('Không thể tải file, vui lòng thử lại')
  } finally {
    downloadingId.value = null
  }
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
</script>
