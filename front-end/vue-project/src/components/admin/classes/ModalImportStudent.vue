<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
    <div
      class="absolute inset-0 bg-black/40 backdrop-blur-sm"
      @click="!importing && $emit('close')"
    />

    <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl p-6 mx-4">
      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Import sinh viên từ Excel</h3>
          <p class="text-sm text-gray-500 mt-0.5">Cột A chứa mã sinh viên</p>
        </div>
        <button
          @click="$emit('close')"
          :disabled="importing"
          class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition disabled:opacity-40"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Upload zone -->
      <div
        class="border-2 border-dashed rounded-xl p-8 text-center mb-4 transition cursor-pointer"
        :class="
          dragging
            ? 'border-blue-400 bg-blue-50'
            : selectedFile
              ? 'border-green-400 bg-green-50'
              : 'border-gray-300 hover:border-blue-400 hover:bg-blue-50'
        "
        @dragover.prevent="dragging = true"
        @dragleave="dragging = false"
        @drop.prevent="onDrop"
        @click="fileInput.click()"
      >
        <input
          ref="fileInput"
          type="file"
          accept=".xlsx,.xls,.csv"
          class="hidden"
          @change="onFileChange"
        />

        <template v-if="!selectedFile">
          <svg
            class="w-10 h-10 mx-auto text-gray-400 mb-3"
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
          <p class="text-sm text-gray-600 font-medium">Kéo thả file hoặc click để chọn</p>
          <p class="text-xs text-gray-400 mt-1">Hỗ trợ .xlsx, .xls, .csv</p>
        </template>

        <template v-else>
          <svg
            class="w-10 h-10 mx-auto text-green-500 mb-3"
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
          <p class="text-sm font-medium text-green-700">{{ selectedFile.name }}</p>
          <p class="text-xs text-green-600 mt-1">{{ (selectedFile.size / 1024).toFixed(1) }} KB</p>
          <button
            type="button"
            @click.stop="selectedFile = null"
            class="mt-2 text-xs text-gray-400 hover:text-red-500 transition"
          >
            Chọn file khác
          </button>
        </template>
      </div>

      <!-- Result sau import -->
      <div v-if="result" class="mb-4 rounded-xl border overflow-hidden">
        <div class="bg-gray-50 px-4 py-2.5 border-b flex items-center justify-between">
          <span class="text-sm font-medium text-gray-700">Kết quả import</span>
          <span class="text-xs text-gray-500">Sĩ số hiện tại: {{ result.current_count }}</span>
        </div>
        <div class="grid grid-cols-2 gap-px bg-gray-200">
          <div class="bg-white px-4 py-3 text-center">
            <p class="text-2xl font-bold text-green-600">{{ result.added }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Thêm thành công</p>
          </div>
          <div class="bg-white px-4 py-3 text-center">
            <p class="text-2xl font-bold text-amber-500">{{ result.skipped }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Bỏ qua</p>
          </div>
        </div>
        <!-- Chi tiết lỗi -->
        <div v-if="result.details?.not_found_codes?.length" class="px-4 py-2.5 border-t bg-red-50">
          <p class="text-xs font-medium text-red-700 mb-1">Mã không tồn tại:</p>
          <p class="text-xs text-red-600 font-mono">
            {{ result.details.not_found_codes.join(', ') }}
          </p>
        </div>
        <div
          v-if="result.details?.duplicate_codes?.length"
          class="px-4 py-2.5 border-t bg-amber-50"
        >
          <p class="text-xs font-medium text-amber-700 mb-1">Đã có trong lớp:</p>
          <p class="text-xs text-amber-600 font-mono">
            {{ result.details.duplicate_codes.join(', ') }}
          </p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-3">
        <button
          type="button"
          @click="$emit('close')"
          :disabled="importing"
          class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition disabled:opacity-40"
        >
          {{ result ? 'Đóng' : 'Hủy' }}
        </button>
        <button
          v-if="!result"
          type="button"
          @click="handleImport"
          :disabled="!selectedFile || importing"
          class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
        >
          <svg v-if="importing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
          </svg>
          {{ importing ? 'Đang import...' : 'Import' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  importing: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'import'])

const fileInput = ref(null)
const selectedFile = ref(null)
const dragging = ref(false)
const result = ref(null)

watch(
  () => props.show,
  (val) => {
    if (val) {
      selectedFile.value = null
      result.value = null
    }
  },
)

function onFileChange(e) {
  selectedFile.value = e.target.files[0] ?? null
}

function onDrop(e) {
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) selectedFile.value = file
}

function handleImport() {
  if (!selectedFile.value) return
  emit('import', selectedFile.value)
}

// Gọi từ parent để set result
function setResult(data) {
  result.value = data
}

defineExpose({ setResult })
</script>
