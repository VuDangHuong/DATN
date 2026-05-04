<!-- src/components/lecturer/assignment/AssignmentFormModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
      >
        <h3 class="text-lg font-bold text-slate-800 mb-5">
          {{ editingId ? 'Cập nhật đợt nộp' : 'Tạo đợt nộp mới' }}
        </h3>

        <div class="space-y-4">
          <!-- Tiêu đề -->
          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Tiêu đề *</label>
            <input
              v-model="form.title"
              type="text"
              class="input-field"
              placeholder="VD: Đồ án cuối kỳ"
            />
          </div>

          <!-- Mô tả -->
          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Mô tả</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="input-field"
              placeholder="Hướng dẫn nộp bài..."
            />
          </div>

          <!-- Deadline + Loại nộp -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Deadline *</label>
              <input v-model="form.deadline" type="datetime-local" class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Loại nộp</label>
              <select v-model="form.submission_type" class="input-field">
                <option value="both">Cả nhóm và cá nhân</option>
                <option value="group">Chỉ nhóm</option>
                <option value="individual">Chỉ cá nhân</option>
              </select>
            </div>
          </div>

          <!-- Dung lượng + Định dạng -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1"
                >Dung lượng tối đa (MB)</label
              >
              <input
                v-model.number="form.max_file_size"
                type="number"
                min="1"
                max="500"
                class="input-field"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1"
                >Định dạng cho phép</label
              >
              <input
                v-model="extensionsInput"
                type="text"
                class="input-field"
                placeholder="pdf,docx,zip"
              />
            </div>
          </div>

          <!-- Allow late -->
          <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
            <input
              v-model="form.allow_late"
              type="checkbox"
              class="rounded border-slate-300 text-indigo-600"
            />
            Cho phép nộp trễ hạn
          </label>

          <!-- Phân loại tài liệu ký số -->
          <div class="border border-slate-200 rounded-xl p-4 space-y-3">
            <p class="text-sm font-semibold text-slate-700 flex items-center gap-2">
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
              Phân loại tài liệu
            </p>
            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">
                Loại tài liệu
                <span class="text-slate-400">(để trống nếu không cần ký số)</span>
              </label>

              <div
                v-if="!store.documentCategories?.length"
                class="flex items-center gap-2 text-xs text-slate-400 py-2"
              >
                <div
                  class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-500 rounded-full animate-spin"
                />
                Đang tải danh mục...
              </div>

              <select v-else v-model="form.document_category" class="input-field">
                <option value="">-- Tài liệu thông thường (không cần ký số) --</option>
                <option v-for="cat in store.documentCategories" :key="cat.value" :value="cat.value">
                  {{ cat.label }}
                </option>
              </select>

              <div
                v-if="form.document_category"
                class="mt-2 flex items-center gap-2 text-xs text-violet-600 bg-violet-50 px-3 py-2 rounded-lg"
              >
                <svg
                  class="w-3.5 h-3.5 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                Sinh viên nộp bài sẽ có tùy chọn gửi yêu cầu ký số lên Admin
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 mt-6">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
          >
            Hủy
          </button>
          <button
            @click="handleSave"
            :disabled="!form.title || !form.deadline"
            class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ editingId ? 'Cập nhật' : 'Tạo' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  show: { type: Boolean, required: true },
  editingId: { type: Number, default: null },
  initial: { type: Object, default: null }, // data khi edit
  classId: { type: Number, required: true },
})

const emit = defineEmits(['close', 'saved'])

const store = useLecturerAssignmentStore()
const toast = useToastStore()

const extensionsInput = ref('pdf,docx,zip')
const form = ref(defaultForm())

function defaultForm() {
  return {
    title: '',
    description: '',
    deadline: '',
    allow_late: true,
    submission_type: 'both',
    max_file_size: 50,
    document_category: '',
  }
}

// Khi mở modal edit — pre-fill form
watch(
  () => props.show,
  (val) => {
    if (!val) return
    if (props.initial) {
      form.value = {
        title: props.initial.title,
        description: props.initial.description,
        deadline: props.initial.deadline?.slice(0, 16),
        allow_late: props.initial.allow_late,
        submission_type: props.initial.submission_type,
        max_file_size: props.initial.max_file_size,
        document_category: props.initial.document_category ?? '',
      }
      extensionsInput.value = props.initial.allowed_extensions?.join(',') || ''
    } else {
      form.value = defaultForm()
      extensionsInput.value = 'pdf,docx,zip'
    }
  },
)

async function handleSave() {
  const selectedCat = store.documentCategories.find((c) => c.value === form.value.document_category)

  const payload = {
    ...form.value,
    allowed_extensions: extensionsInput.value
      .split(',')
      .map((e) => e.trim())
      .filter(Boolean),
    document_category: form.value.document_category || null,
    document_category_label: selectedCat?.label ?? null,
  }

  const result = props.editingId
    ? await store.updateAssignment(props.editingId, payload)
    : await store.createAssignment(props.classId, payload)

  if (result.success) {
    toast.success(props.editingId ? 'Cập nhật đợt nộp thành công' : 'Tạo đợt nộp thành công')
    emit('saved')
    emit('close')
  } else {
    toast.error(result.message ?? 'Có lỗi xảy ra')
  }
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent;
}
</style>
