<template>
  <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
    <!-- ── Đã có bài nộp ── -->
    <div v-if="submission && !isReuploading" class="p-4">
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <div
            class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center flex-shrink-0"
          >
            <svg
              class="w-5 h-5 text-indigo-600"
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
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-800 truncate">{{ submission.file_name }}</p>
            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
              <span class="text-xs text-slate-400">{{ formatSize(submission.file_size) }}</span>
              <span class="text-xs text-slate-400">·</span>
              <span
                class="px-1.5 py-0.5 text-[10px] font-bold rounded"
                :class="
                  submission.is_late
                    ? 'bg-amber-100 text-amber-700'
                    : 'bg-emerald-100 text-emerald-700'
                "
              >
                {{ submission.is_late ? 'Nộp trễ' : 'Đúng hạn' }}
              </span>
              <!-- Badge trạng thái duyệt -->
              <span
                v-if="submission.status"
                class="px-1.5 py-0.5 text-[10px] font-bold rounded"
                :class="{
                  'bg-amber-100 text-amber-700': submission.status === 'pending',
                  'bg-emerald-100 text-emerald-700': submission.status === 'approved',
                  'bg-red-100 text-red-700': submission.status === 'rejected',
                }"
              >
                {{ statusLabel(submission.status) }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
          <button
            v-if="canSubmit"
            @click="openReupload"
            class="px-3 py-1.5 border border-indigo-200 rounded-lg text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition"
          >
            Nộp lại
          </button>
          <button
            @click="$emit('showHistory')"
            class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-50 transition"
          >
            Lịch sử
          </button>
        </div>
      </div>

      <p
        v-if="submission.note"
        class="text-xs text-slate-500 mt-2 bg-slate-50 p-2 rounded-lg italic"
      >
        "{{ submission.note }}"
      </p>

      <!-- Feedback bị từ chối -->
      <div
        v-if="submission.status === 'rejected' && submission.feedback"
        class="mt-2 p-2.5 bg-red-50 rounded-lg"
      >
        <p class="text-xs text-red-700 font-medium">Nhận xét của giảng viên:</p>
        <p class="text-xs text-red-600 mt-0.5 italic">"{{ submission.feedback }}"</p>
      </div>

      <!-- Feedback approved + điểm -->
      <div
        v-if="submission.status === 'approved' && (submission.feedback || submission.score)"
        class="mt-2 p-2.5 bg-emerald-50 rounded-lg"
      >
        <div class="flex items-center gap-2 flex-wrap">
          <span v-if="submission.score" class="text-lg font-bold text-emerald-700">
            {{ submission.score }}/10
          </span>
          <p v-if="submission.feedback" class="text-xs text-emerald-600 italic">
            "{{ submission.feedback }}"
          </p>
        </div>
      </div>

      <!-- ✅ Khu vực ký số — chỉ hiện khi approved + requires_signing = true -->
      <SignRequestSectionView
        v-if="submission.status === 'approved' && assignment.requires_signing"
        :submission-id="submission.id"
        :assignment="assignment"
      />
    </div>

    <!-- ── Chưa có bài nộp / đang nộp lại ── -->
    <div v-else-if="canSubmit" class="p-4">
      <div
        class="border-2 border-dashed rounded-xl p-6 text-center transition cursor-pointer"
        :class="
          dragging
            ? 'border-indigo-400 bg-indigo-50'
            : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50'
        "
        @dragover.prevent="dragging = true"
        @dragleave="dragging = false"
        @drop.prevent="onDrop"
        @click="fileInput?.click()"
      >
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          :accept="acceptAttr"
          @change="onFileChange"
        />

        <template v-if="!selectedFile">
          <svg
            class="w-8 h-8 mx-auto text-slate-400 mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
            />
          </svg>
          <p class="text-sm text-slate-600 font-medium">Kéo thả hoặc click để chọn file</p>
          <p class="text-xs text-slate-400 mt-1">
            {{ assignment.allowed_extensions?.join(', ') || 'Mọi định dạng' }} · Tối đa
            {{ assignment.max_file_size }}MB
          </p>
        </template>

        <template v-else>
          <svg
            class="w-8 h-8 mx-auto text-emerald-500 mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <p class="text-sm font-medium text-emerald-700 truncate px-4">{{ selectedFile.name }}</p>
          <button
            @click.stop="selectedFile = null"
            class="mt-2 text-xs text-red-500 hover:underline"
          >
            Chọn file khác
          </button>
        </template>
      </div>

      <div v-if="selectedFile" class="mt-4 space-y-3">
        <textarea
          v-model="note"
          placeholder="Thêm ghi chú nộp bài..."
          rows="2"
          class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
        />
        <!-- ✅ Chọn loại tài liệu -->
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1">
            Loại tài liệu
            <span class="text-slate-400">(chọn nếu cần ký số)</span>
          </label>
          <select
            v-model="documentCategory"
            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          >
            <option value="">-- Tài liệu thông thường (không cần ký số) --</option>
            <option v-for="cat in documentCategories" :key="cat.value" :value="cat.value">
              {{ cat.label }}
            </option>
          </select>

          <!-- Badge thông báo nếu chọn loại cần ký số -->
          <div
            v-if="documentCategory"
            class="mt-1.5 flex items-center gap-1.5 text-xs text-violet-600"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
              />
            </svg>
            Tài liệu này sẽ được gửi lên để ký số sau khi nộp
          </div>
        </div>
        <div class="flex gap-2">
          <button
            @click="cancelUpload"
            class="flex-1 py-2 border border-slate-300 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
          >
            Hủy
          </button>
          <button
            @click="handleInternalSubmit"
            :disabled="submitting"
            class="flex-1 py-2 rounded-xl text-sm font-bold text-white transition-all flex items-center justify-center gap-2 shadow-sm"
            :class="[
              type === 'group'
                ? 'bg-amber-600 hover:bg-amber-700'
                : 'bg-indigo-600 hover:bg-indigo-700',
              submitting ? 'opacity-50 cursor-not-allowed' : '',
            ]"
          >
            <div
              v-if="submitting"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{ submitting ? 'Đang gửi...' : type === 'group' ? 'Nộp bài nhóm' : 'Nộp bài cá nhân' }}
          </button>
        </div>
      </div>

      <div v-if="isReuploading && !selectedFile" class="mt-2 text-center">
        <button @click="isReuploading = false" class="text-xs text-slate-500 hover:underline">
          Quay lại
        </button>
      </div>
    </div>

    <!-- ── Không thể nộp ── -->
    <div v-else class="p-8 text-center bg-slate-50">
      <p class="text-sm text-slate-500 font-medium">
        {{
          type === 'group' && !isLeader
            ? 'Chỉ trưởng nhóm mới có quyền nộp bài'
            : 'Đã kết thúc đợt nộp bài'
        }}
      </p>
    </div>
  </div>
</template>

<script setup>
import axiosClient from '@/api/axiosClient'
import SignRequestSectionView from '@/views/students/Assignment/SignRequestSectionView.vue'
import { ref, computed, onMounted } from 'vue'
const props = defineProps({
  submission: { type: Object, default: null },
  assignment: { type: Object, required: true },
  type: { type: String, default: 'individual' },
  isLeader: { type: Boolean, default: true },
  submitting: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'showHistory'])

const fileInput = ref(null)
const selectedFile = ref(null)
const dragging = ref(false)
const note = ref('')
const isReuploading = ref(false)
const documentCategory = ref('')
const documentCategories = ref([])
const canSubmit = computed(() => {
  if (props.type === 'group' && !props.isLeader) return false
  if (!props.assignment.allow_late && props.assignment.is_expired) return false
  return true
})

const acceptAttr = computed(
  () => props.assignment.allowed_extensions?.map((e) => `.${e}`).join(',') || '*',
)

function statusLabel(status) {
  return (
    { pending: 'Chờ duyệt', approved: 'Đã chấp nhận', rejected: 'Bị từ chối' }[status] ?? status
  )
}
function cancelUpload() {
  selectedFile.value = null
  note.value = ''
  documentCategory.value = ''
  isReuploading.value = false
}

// Emit thêm documentCategory
async function handleInternalSubmit() {
  if (!selectedFile.value) return
  emit('submit', selectedFile.value, note.value, documentCategory.value) // ← thêm
  isReuploading.value = false
}
function openReupload() {
  isReuploading.value = true
  selectedFile.value = null
  note.value = ''
}

// function cancelUpload() {
//   selectedFile.value = null
//   note.value = ''
//   isReuploading.value = false
// }

function onFileChange(e) {
  selectedFile.value = e.target.files[0] ?? null
}

function onDrop(e) {
  dragging.value = false
  if (!canSubmit.value) return
  selectedFile.value = e.dataTransfer.files[0] ?? null
}

// async function handleInternalSubmit() {
//   if (!selectedFile.value) return
//   emit('submit', selectedFile.value, note.value)
//   isReuploading.value = false
// }

function formatSize(bytes) {
  if (!bytes) return '0 KB'
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}
onMounted(async () => {
  try {
    const { data } = await axiosClient.get('/general/document-categories')
    documentCategories.value = data
  } catch {
    // fallback hardcode nếu API lỗi
    documentCategories.value = [
      { value: 'bao_cao_thuc_tap', label: 'Báo cáo thực tập' },
      { value: 'nckh', label: 'Nghiên cứu khoa học' },
      { value: 'do_an_tot_nghiep', label: 'Đồ án tốt nghiệp' },
      { value: 'bao_cao_du_an', label: 'Báo cáo dự án' },
      { value: 'khoa_luan', label: 'Khóa luận' },
    ]
  }
})
</script>
