<template>
  <div class="mt-3 border-t border-slate-100 pt-3">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center gap-2 text-xs text-slate-400 py-1">
      <div
        class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"
      />
      Đang kiểm tra trạng thái ký số...
    </div>

    <template v-else>
      <!-- Chưa có yêu cầu → hiện nút tạo -->
      <div v-if="!signRequest">
        <div class="flex items-center gap-2 mb-2">
          <svg
            class="w-4 h-4 text-violet-600"
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
          <span class="text-xs font-semibold text-violet-700">Yêu cầu ký số tài liệu</span>
          <span class="px-1.5 py-0.5 bg-violet-100 text-violet-600 text-[10px] font-bold rounded">
            {{ assignment.document_category_label }}
          </span>
        </div>
        <p class="text-xs text-slate-500 mb-2">
          Bài nộp đã được chấp nhận. Bạn có thể gửi yêu cầu ký số tài liệu cho giảng viên.
        </p>
        <button
          @click="handleCreate"
          :disabled="creating"
          class="flex items-center gap-1.5 px-3 py-1.5 bg-violet-600 text-white rounded-lg text-xs font-medium hover:bg-violet-700 disabled:opacity-50 transition"
        >
          <div
            v-if="creating"
            class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"
          />
          <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          {{ creating ? 'Đang gửi...' : 'Gửi yêu cầu ký số' }}
        </button>
      </div>

      <!-- Đã có yêu cầu -->
      <div v-else>
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <svg
              class="w-4 h-4 text-violet-600 flex-shrink-0"
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
            <span class="text-xs font-semibold text-violet-700">Ký số tài liệu</span>
          </div>
          <div class="flex items-center gap-1.5">
            <div
              v-if="isPolling"
              class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse"
              title="Đang tự động cập nhật..."
            />
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="statusClass">
              {{ signRequest.status }}
            </span>
          </div>
        </div>

        <!-- ✅ Timeline mới — 3 bước theo UC-11 (bỏ Admin) -->
        <div class="mt-2 flex items-center gap-1 overflow-x-auto pb-1">
          <div
            v-for="(step, idx) in steps"
            :key="step.key"
            class="flex items-center gap-1 flex-shrink-0"
          >
            <div class="flex flex-col items-center gap-0.5">
              <div
                class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold"
                :class="getStepClass(step.key)"
              >
                <svg
                  v-if="isStepDone(step.key)"
                  class="w-3 h-3"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
                <span v-else>{{ idx + 1 }}</span>
              </div>
              <span class="text-[9px] text-slate-500 text-center w-14 leading-tight">{{
                step.label
              }}</span>
            </div>
            <div
              v-if="idx < steps.length - 1"
              class="w-6 h-0.5 mb-3 flex-shrink-0"
              :class="isStepDone(step.key) ? 'bg-violet-400' : 'bg-slate-200'"
            />
          </div>
        </div>

        <!-- Thông báo từ chối -->
        <div v-if="isRejected" class="mt-2 p-2.5 bg-red-50 rounded-lg">
          <p class="text-xs text-red-700 font-medium">Giảng viên đã từ chối yêu cầu</p>
          <p v-if="signRequest.reject_reason" class="text-xs text-red-600 mt-0.5 italic">
            "{{ signRequest.reject_reason }}"
          </p>
          <button
            @click="handleCreate"
            :disabled="creating"
            class="mt-2 flex items-center gap-1 px-2.5 py-1 bg-red-600 text-white rounded-lg text-[10px] font-medium hover:bg-red-700 disabled:opacity-50 transition"
          >
            Gửi lại yêu cầu
          </button>
        </div>

        <!-- Khi đã ký xong -->
        <div v-if="signRequest.status === 'signed'" class="mt-2 space-y-2">
          <!-- Nút download -->
          <button
            @click="handleDownload"
            :disabled="downloading"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-medium hover:bg-emerald-700 disabled:opacity-50"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
              />
            </svg>
            Tải tài liệu đã ký
          </button>

          <!-- ✅ Box thông tin xác thực -->
          <div
            v-if="signRequest.sign_certificate"
            class="p-2.5 bg-blue-50 border border-blue-200 rounded-lg"
          >
            <div class="flex items-center justify-between gap-2 mb-1">
              <span class="text-[10px] font-bold text-blue-700 uppercase">🔍 Serial chứng thư</span>
              <button @click="copySerial" class="text-[10px] text-blue-600 hover:underline">
                📋 Copy
              </button>
            </div>
            <p class="text-[10px] font-mono text-blue-800 break-all leading-relaxed">
              {{ signRequest.sign_certificate }}
            </p>
            <a
              href="/verify"
              target="_blank"
              class="block mt-1.5 text-[10px] text-blue-600 hover:underline"
            >
              → Xác thực file đã ký tại trang công khai
            </a>
          </div>
        </div>

        <!-- Refresh thủ công -->
        <button
          v-if="!isTerminalStatus"
          @click="refreshStatus"
          class="mt-2 flex items-center gap-1 text-[10px] text-slate-400 hover:text-slate-600 transition"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Cập nhật trạng thái
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useToastStore } from '@/stores/toast'
import { useStudentAssignmentStore } from '@/stores/students/studentAssignmentStore'

const props = defineProps({
  submissionId: { type: Number, required: true },
  assignment: { type: Object, required: true },
})

const store = useStudentAssignmentStore()
const toast = useToastStore()
const loading = ref(false)
const creating = ref(false)
const downloading = ref(false)
const signRequest = ref(null)

// ── Polling ────────────────────────────────────────────
const POLL_INTERVAL = 30_000
let pollTimer = null

const isTerminalStatus = computed(() => ['signed', 'rejected'].includes(signRequest.value?.status))

const isPolling = computed(() => !!signRequest.value && !isTerminalStatus.value)

function startPolling() {
  stopPolling()
  if (isTerminalStatus.value) return

  pollTimer = setInterval(async () => {
    const data = await store.fetchSignRequest(props.submissionId)
    if (!data) return

    const oldStatus = signRequest.value?.status
    signRequest.value = data

    if (oldStatus && oldStatus !== data.status) {
      if (data.status === 'signed') {
        toast.success('🎉 Tài liệu đã được ký! Bạn có thể tải về.')
      } else if (data.status === 'rejected') {
        toast.error('Yêu cầu ký số đã bị từ chối.')
      } else if (data.status === 'lecturer_reviewing') {
        toast.success('Giảng viên đang xem xét yêu cầu của bạn.')
      }
    }

    if (isTerminalStatus.value) stopPolling()
  }, POLL_INTERVAL)
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(async () => {
  await loadSignRequest()
  if (signRequest.value && !isTerminalStatus.value) startPolling()
})

onUnmounted(() => stopPolling())

// ✅ Timeline mới — chỉ 3 bước (bỏ Admin)
const steps = [
  { key: 'pending', label: 'Đã gửi' },
  { key: 'lecturer_reviewing', label: 'GV xem' },
  { key: 'signed', label: 'Đã ký' },
]

const stepOrder = ['pending', 'lecturer_reviewing', 'signed']

const isRejected = computed(() => signRequest.value?.status === 'rejected')

const statusClass = computed(() => {
  const s = signRequest.value?.status
  if (s === 'signed') return 'bg-emerald-100 text-emerald-700'
  if (s === 'rejected') return 'bg-red-100 text-red-700'
  if (s === 'lecturer_reviewing') return 'bg-blue-100 text-blue-700'
  if (s === 'pending') return 'bg-amber-100 text-amber-700'
  return 'bg-slate-100 text-slate-600'
})

function getStepClass(stepKey) {
  if (!signRequest.value) return 'bg-slate-100 text-slate-400'
  if (isStepDone(stepKey)) return 'bg-violet-600 text-white'
  if (isCurrentStep(stepKey)) return 'bg-violet-100 text-violet-700 ring-2 ring-violet-400'
  return 'bg-slate-100 text-slate-400'
}

function isStepDone(stepKey) {
  if (!signRequest.value) return false
  const currentIdx = stepOrder.indexOf(signRequest.value.status)
  const stepIdx = stepOrder.indexOf(stepKey)
  return stepIdx <= currentIdx
}

function isCurrentStep(stepKey) {
  if (!signRequest.value) return false
  return signRequest.value.status === stepKey
}

async function loadSignRequest() {
  loading.value = true
  signRequest.value = await store.fetchSignRequest(props.submissionId)
  loading.value = false
}

async function refreshStatus() {
  const data = await store.fetchSignRequest(props.submissionId)
  if (!data) return
  const oldStatus = signRequest.value?.status
  signRequest.value = data
  if (oldStatus !== data.status) {
    toast.success('Trạng thái đã được cập nhật')
  }
  if (!isTerminalStatus.value) startPolling()
  else stopPolling()
}

async function handleCreate() {
  creating.value = true
  const result = await store.createSignRequest(props.submissionId)
  if (result.success) {
    signRequest.value = result.data
    toast.success('Đã gửi yêu cầu ký số. Giảng viên sẽ nhận email thông báo.')
    startPolling()
  } else {
    toast.error(result.message)
  }
  creating.value = false
}

async function handleDownload() {
  downloading.value = true
  const fileName = `phieu_xac_nhan_ky_so_${signRequest.value.id}.pdf`
  const result = await store.downloadSignedFile(signRequest.value.id, fileName)
  if (result.success) toast.success('Tải file thành công')
  else toast.error(result.message)
  downloading.value = false
}
</script>
