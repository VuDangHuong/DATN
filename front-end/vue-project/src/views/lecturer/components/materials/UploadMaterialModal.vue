<!-- src/components/lecturer/materials/UploadMaterialModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] flex flex-col"
      >
        <!-- Header -->
        <div class="flex items-start gap-3 mb-4 flex-shrink-0">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
            <SvgIcon name="upload" class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-stone-800">
              {{ mode === 'add' ? 'Thêm file vào tài liệu' : 'Upload tài liệu mới' }}
            </h3>
            <p class="text-base text-stone-500 mt-1">
              <template v-if="mode === 'add'">
                Vào: <strong>{{ existingTitle }}</strong> · Tối đa 20 file × 50MB
              </template>
              <template v-else> 1 tài liệu chứa nhiều file · Tối đa 20 file × 50MB </template>
            </p>
          </div>
        </div>

        <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto space-y-4 -mx-2 px-2">
          <!-- Title (chỉ hiện khi tạo mới) -->
          <template v-if="mode === 'create'">
            <div>
              <label class="block text-base font-medium text-stone-700 mb-1">
                Tên tài liệu (Title) <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                placeholder="Vd: Chương 1 - Giới thiệu môn học"
                maxlength="255"
                class="w-full px-3 py-2 border border-stone-200 rounded-xl text-base focus:ring-2 focus:ring-emerald-500 outline-none"
              />
              <p class="text-base text-stone-400 mt-1">Title sẽ chứa tất cả file bên dưới</p>
            </div>

            <!-- Category -->
            <div>
              <label class="block text-base font-medium text-stone-700 mb-1">Loại</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="(label, value) in categories"
                  :key="value"
                  type="button"
                  @click="form.category = value"
                  class="px-2 py-2 border rounded-xl text-base font-medium transition"
                  :class="
                    form.category === value
                      ? 'bg-emerald-600 text-white border-emerald-600'
                      : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'
                  "
                >
                  {{ label }}
                </button>
              </div>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-base font-medium text-stone-700 mb-1">
                Mô tả <span class="text-stone-400 text-base">(tùy chọn)</span>
              </label>
              <textarea
                v-model="form.description"
                rows="2"
                maxlength="1000"
                class="w-full px-3 py-2 border border-stone-200 rounded-xl text-base resize-none focus:ring-2 focus:ring-emerald-500 outline-none"
              />
            </div>
          </template>

          <!-- File drop zone -->
          <div>
            <label class="block text-base font-medium text-stone-700 mb-1">
              Files <span class="text-red-500">*</span>
              <span class="text-base text-stone-400 ml-2">{{ selectedFiles.length }}/20</span>
            </label>
            <label
              @dragover.prevent
              @drop.prevent="handleDrop"
              class="block w-full px-4 py-6 border-2 border-dashed border-stone-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/30 transition text-center"
            >
              <input
                ref="fileInput"
                type="file"
                multiple
                @change="handleFileChange"
                class="hidden"
              />
              <svg
                class="w-10 h-10 mx-auto text-stone-400 mb-2"
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
              <p class="text-base text-stone-600 font-medium">Click hoặc kéo nhiều file vào đây</p>
              <p class="text-base text-stone-400 mt-1">
                PDF, Word, Excel, PPT, ZIP, ảnh, video... (mỗi file max 50MB)
              </p>
            </label>
          </div>

          <!-- File list -->
          <div v-if="selectedFiles.length" class="space-y-1.5">
            <p class="text-base font-medium text-stone-500">
              📎 Đã chọn {{ selectedFiles.length }} file:
            </p>
            <div
              v-for="(file, idx) in selectedFiles"
              :key="idx"
              class="flex items-center gap-2 p-2 bg-stone-50 rounded-lg text-base"
            >
              <span class="text-lg flex-shrink-0">{{ getFileIcon(file.name) }}</span>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-stone-700 truncate text-base">{{ file.name }}</p>
                <p class="text-[10px] text-stone-400">{{ formatSize(file.size) }}</p>
              </div>
              <button
                type="button"
                @click="removeFile(idx)"
                class="p-1 text-red-500 hover:bg-red-50 rounded flex-shrink-0"
                title="Xóa khỏi danh sách"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <p class="text-base text-stone-500 pt-1 border-t border-stone-100">
              Tổng: {{ formatSize(totalSize) }}
            </p>
          </div>
        </form>

        <!-- Footer -->
        <div class="flex gap-3 pt-4 mt-2 border-t border-stone-100 flex-shrink-0">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-stone-200 rounded-xl text-base font-medium text-stone-600 hover:bg-stone-50"
          >
            Hủy
          </button>
          <button
            @click="handleSubmit"
            :disabled="!canSubmit || uploading"
            class="flex-1 py-2.5 bg-emerald-600 text-white rounded-xl text-base font-semibold hover:bg-emerald-700 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <div
              v-if="uploading"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{
              uploading
                ? 'Đang upload...'
                : mode === 'add'
                  ? `📤 Thêm ${selectedFiles.length} file`
                  : `📤 Upload ${selectedFiles.length} file`
            }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useMaterialStore } from '@/stores/lecturer/materialStore'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  classId: { type: Number, required: true },
  // Nếu có materialId => add files vào title đã có
  materialId: { type: Number, default: null },
  existingTitle: { type: String, default: '' },
})

const emit = defineEmits(['close', 'uploaded'])

const materialStore = useMaterialStore()
const toast = useToastStore()

const fileInput = ref(null)
const selectedFiles = ref([])
const uploading = ref(false)

const form = ref({
  title: '',
  description: '',
  category: 'lecture',
})

const categories = {
  lecture: 'Slide',
  exercise: 'Bài tập',
  reference: 'Tham khảo',
  exam: 'Đề thi',
  other: 'Khác',
}

const MAX_FILES = 20
const MAX_SIZE = 50 * 1024 * 1024

const mode = computed(() => (props.materialId ? 'add' : 'create'))

const canSubmit = computed(() => {
  if (mode.value === 'create') {
    return form.value.title.trim().length > 0 && selectedFiles.value.length > 0
  }
  return selectedFiles.value.length > 0
})

const totalSize = computed(() => selectedFiles.value.reduce((sum, f) => sum + f.size, 0))

watch(
  () => props.show,
  (v) => {
    if (v) reset()
  },
)

function addFiles(files) {
  const newFiles = Array.from(files).filter((f) => {
    if (f.size > MAX_SIZE) {
      toast.error(`"${f.name}" vượt quá 50MB`)
      return false
    }
    return true
  })

  // Tránh trùng tên
  const existingNames = selectedFiles.value.map((f) => f.name)
  const toAdd = newFiles.filter((f) => !existingNames.includes(f.name))

  if (selectedFiles.value.length + toAdd.length > MAX_FILES) {
    toast.warning(
      `Tối đa ${MAX_FILES} file. Đã thêm ${MAX_FILES - selectedFiles.value.length} file.`,
    )
  }

  const remaining = MAX_FILES - selectedFiles.value.length
  selectedFiles.value.push(...toAdd.slice(0, remaining))

  // Auto-fill title nếu chưa có
  if (mode.value === 'create' && !form.value.title && selectedFiles.value.length === 1) {
    form.value.title = selectedFiles.value[0].name.replace(/\.[^.]+$/, '')
  }
}

function handleFileChange(e) {
  if (e.target.files?.length) addFiles(e.target.files)
  if (fileInput.value) fileInput.value.value = ''
}

function handleDrop(e) {
  if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files)
}

function removeFile(idx) {
  selectedFiles.value.splice(idx, 1)
}

async function handleSubmit() {
  if (!canSubmit.value) return

  uploading.value = true

  const formData = new FormData()
  selectedFiles.value.forEach((f) => formData.append('files[]', f))

  let result
  if (mode.value === 'add') {
    result = await materialStore.addFilesToMaterial(props.materialId, formData)
  } else {
    formData.append('title', form.value.title)
    formData.append('description', form.value.description)
    formData.append('category', form.value.category)
    result = await materialStore.createMaterial(props.classId, formData)
  }

  uploading.value = false

  if (result.success) {
    toast.success(result.message ?? 'Thành công')
    emit('uploaded', result.material)
    emit('close')
    reset()
  } else {
    toast.error(result.message)
  }
}

function reset() {
  selectedFiles.value = []
  form.value = { title: '', description: '', category: 'lecture' }
  if (fileInput.value) fileInput.value.value = ''
}

function getFileIcon(name) {
  const ext = name.split('.').pop()?.toLowerCase()
  return (
    {
      pdf: '📄',
      doc: '📝',
      docx: '📝',
      xls: '📊',
      xlsx: '📊',
      ppt: '📈',
      pptx: '📈',
      zip: '🗜️',
      rar: '🗜️',
      jpg: '🖼️',
      jpeg: '🖼️',
      png: '🖼️',
      gif: '🖼️',
      mp4: '🎥',
      avi: '🎥',
      mov: '🎥',
      mp3: '🎵',
      wav: '🎵',
    }[ext] || '📎'
  )
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>
