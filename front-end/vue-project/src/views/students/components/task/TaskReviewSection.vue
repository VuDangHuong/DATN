<!-- src/components/students/task/TaskReviewSection.vue -->
<!--
  Component hiển thị section "Báo hoàn thành" / "Duyệt hoàn thành"
  trong TaskDetailModal. Tự động render theo:
  - status === 'doing'  + isAssignee  → nút "Báo hoàn thành"
  - status === 'pending_review' + isAssignee → banner "Đang chờ duyệt"
  - status === 'pending_review' + isLeader → 2 nút Duyệt / Từ chối
-->
<template>
  <div class="bg-stone-50 rounded-xl p-4">
    <!-- ─── Assignee: Status = doing → Nút Báo hoàn thành ─── -->
    <div v-if="canSubmitReview">
      <div class="flex items-start gap-2 mb-3">
        <span class="text-lg">🎯</span>
        <div class="flex-1">
          <p class="text-sm font-semibold text-stone-700">Hoàn thành công việc?</p>
          <p class="text-xs text-stone-500 mt-0.5">
            Bấm "Báo hoàn thành" để gửi cho nhóm trưởng duyệt.
          </p>
        </div>
      </div>
      <button
        @click="showSubmitModal = true"
        class="w-full py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700"
      >
        📤 Báo hoàn thành
      </button>
    </div>

    <!-- ─── Assignee: Status = pending_review → Đang chờ duyệt ─── -->
    <div v-else-if="isPendingForAssignee" class="space-y-2">
      <div class="flex items-start gap-2">
        <span class="text-lg">⏳</span>
        <div class="flex-1">
          <p class="text-sm font-semibold text-amber-700">Đang chờ nhóm trưởng duyệt</p>
          <p class="text-xs text-stone-500 mt-0.5">
            Gửi lúc {{ formatTime(task.submitted_for_review_at) }}
          </p>
          <p v-if="task.submission_note" class="text-xs text-stone-600 mt-1.5 p-2 bg-white rounded">
            <span class="font-medium">Ghi chú:</span> {{ task.submission_note }}
          </p>
        </div>
      </div>
    </div>

    <!-- ─── Leader: Status = pending_review → Duyệt / Từ chối ─── -->
    <div v-else-if="canReview" class="space-y-3">
      <div class="flex items-start gap-2">
        <SvgIcon name="clipboard" class="h-4 w-4" />
        <div class="flex-1">
          <p class="text-sm font-semibold text-stone-700">Yêu cầu xác nhận hoàn thành</p>
          <p class="text-xs text-stone-500 mt-0.5">
            {{ task.assignee?.name }} báo hoàn thành lúc
            {{ formatTime(task.submitted_for_review_at) }}
          </p>
          <p
            v-if="task.submission_note"
            class="text-xs text-stone-600 mt-1.5 p-2 bg-white rounded border border-stone-200"
          >
            <span class="font-medium">Ghi chú từ thành viên:</span> {{ task.submission_note }}
          </p>
        </div>
      </div>

      <div class="flex gap-2">
        <button
          @click="showRejectModal = true"
          class="flex-1 py-2 border border-red-200 text-red-700 rounded-xl text-sm font-semibold hover:bg-red-50"
        >
          Từ chối
        </button>
        <button
          @click="showApproveModal = true"
          class="flex-1 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700"
        >
          Duyệt hoàn thành
        </button>
      </div>
    </div>

    <!-- ─── Đã review xong → hiện history ─── -->
    <div
      v-else-if="task.reviewed_at && (task.status === 'done' || task.status === 'late')"
      class="flex items-start gap-2"
    >
      <SvgIcon name="check" class="w-4 h-4" />
      <div class="flex-1">
        <p class="text-sm font-semibold text-emerald-700">Đã được duyệt hoàn thành</p>
        <p class="text-xs text-stone-500 mt-0.5">
          {{ task.reviewer?.name }} duyệt lúc {{ formatTime(task.reviewed_at) }}
        </p>
        <p v-if="task.review_note" class="text-xs text-stone-600 mt-1.5 p-2 bg-white rounded">
          <span class="font-medium">Ghi chú:</span> {{ task.review_note }}
        </p>
      </div>
    </div>

    <!-- ─── Đã bị reject lần trước ─── -->
    <div
      v-else-if="task.reviewed_at && task.review_note && task.status === 'doing'"
      class="bg-red-50 border border-red-200 rounded-xl p-3 mt-2"
    >
      <div class="flex items-start gap-2">
        <span class="text-lg">⚠️</span>
        <div class="flex-1">
          <p class="text-xs font-semibold text-red-700">Nhóm trưởng đã từ chối yêu cầu trước đó</p>
          <p class="text-xs text-red-600 mt-1">
            <span class="font-medium">Lý do:</span> {{ task.review_note }}
          </p>
          <p class="text-[10px] text-red-500 mt-1">
            {{ formatTime(task.reviewed_at) }} bởi {{ task.reviewer?.name }}
          </p>
        </div>
      </div>
    </div>

    <!-- ─── Submit Modal ─── -->
    <Teleport to="body">
      <div v-if="showSubmitModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeSubmitModal" />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-stone-800 mb-1">📤 Báo hoàn thành</h3>
          <p class="text-xs text-stone-500 mb-4">
            Yêu cầu sẽ được gửi cho <strong>{{ leaderName }}</strong> duyệt
          </p>

          <label class="block text-sm font-medium text-stone-700 mb-1">
            Ghi chú <span class="text-stone-400 text-xs">(tùy chọn)</span>
          </label>
          <textarea
            v-model="submitNote"
            rows="3"
            maxlength="1000"
            placeholder="Vd: Đã hoàn thành phần A, B. Phần C đã upload file đính kèm..."
            class="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none"
          />
          <p class="text-xs text-stone-400 mt-1 text-right">{{ submitNote.length }}/1000</p>

          <div class="flex gap-3 mt-4">
            <button
              @click="closeSubmitModal"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              @click="handleSubmit"
              :disabled="submitting"
              class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ submitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ─── Approve Modal ─── -->
    <Teleport to="body">
      <div
        v-if="showApproveModal"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="showApproveModal = false"
        />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-stone-800 mb-1">Duyệt hoàn thành</h3>
          <p class="text-xs text-stone-500 mb-4">
            Xác nhận <strong>{{ task.assignee?.name }}</strong> đã hoàn thành công việc?
          </p>

          <label class="block text-sm font-medium text-stone-700 mb-1">
            Ghi chú <span class="text-stone-400 text-xs">(tùy chọn)</span>
          </label>
          <textarea
            v-model="approveNote"
            rows="3"
            maxlength="1000"
            placeholder="Vd: Làm tốt!"
            class="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-emerald-500 outline-none"
          />

          <div class="flex gap-3 mt-4">
            <button
              @click="showApproveModal = false"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              @click="handleApprove"
              :disabled="processing"
              class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 disabled:opacity-50"
            >
              {{ processing ? 'Đang xử lý...' : 'Xác nhận duyệt' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ─── Reject Modal ─── -->
    <Teleport to="body">
      <div v-if="showRejectModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="showRejectModal = false"
        />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-stone-800 mb-1">❌ Từ chối duyệt</h3>
          <p class="text-xs text-stone-500 mb-4">Công việc sẽ quay lại trạng thái "Đang làm"</p>

          <label class="block text-sm font-medium text-stone-700 mb-1">
            Lý do từ chối <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="rejectReason"
            rows="4"
            maxlength="1000"
            placeholder="Vd: Phần A chưa hoàn thiện, cần bổ sung..."
            class="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-red-500 outline-none"
          />
          <div class="flex justify-between mt-1">
            <p class="text-xs text-stone-400">Tối thiểu 5 ký tự</p>
            <p class="text-xs text-stone-400">{{ rejectReason.length }}/1000</p>
          </div>

          <div class="flex gap-3 mt-4">
            <button
              @click="showRejectModal = false"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              @click="handleReject"
              :disabled="rejectReason.length < 5 || processing"
              class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 disabled:opacity-50"
            >
              {{ processing ? 'Đang xử lý...' : 'Xác nhận từ chối' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useTaskStore } from '@/stores/students/taskStore'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'
const props = defineProps({
  task: { type: Object, required: true },
  currentUserId: { type: Number, required: true },
  isLeader: { type: Boolean, default: false },
  leaderName: { type: String, default: 'Nhóm trưởng' },
})

const emit = defineEmits(['updated'])

const taskStore = useTaskStore()
const toast = useToastStore()

// State modals
const showSubmitModal = ref(false)
const showApproveModal = ref(false)
const showRejectModal = ref(false)

const submitNote = ref('')
const approveNote = ref('')
const rejectReason = ref('')
const submitting = ref(false)
const processing = ref(false)

// ── Computed ────────────────────────────────
const isAssignee = computed(() => props.task.assignee?.id === props.currentUserId)

const canSubmitReview = computed(() => isAssignee.value && props.task.status === 'doing')

const isPendingForAssignee = computed(
  () => isAssignee.value && props.task.status === 'pending_review',
)

const canReview = computed(() => props.isLeader && props.task.status === 'pending_review')

// ── Handlers ────────────────────────────────
function closeSubmitModal() {
  showSubmitModal.value = false
  submitNote.value = ''
}

async function handleSubmit() {
  submitting.value = true
  const result = await taskStore.submitForReview(props.task.id, submitNote.value.trim())
  submitting.value = false

  if (result.success) {
    toast.success(result.message)
    closeSubmitModal()
    emit('updated')
  } else {
    toast.error(result.message)
  }
}

async function handleApprove() {
  processing.value = true
  const result = await taskStore.approveTask(props.task.id, approveNote.value.trim())
  processing.value = false

  if (result.success) {
    toast.success(result.message)
    showApproveModal.value = false
    approveNote.value = ''
    emit('updated')
  } else {
    toast.error(result.message)
  }
}

async function handleReject() {
  if (rejectReason.value.length < 5) return

  processing.value = true
  const result = await taskStore.rejectTask(props.task.id, rejectReason.value.trim())
  processing.value = false

  if (result.success) {
    toast.success(result.message)
    showRejectModal.value = false
    rejectReason.value = ''
    emit('updated')
  } else {
    toast.error(result.message)
  }
}

// ── Formatters ──────────────────────────────
function formatTime(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
