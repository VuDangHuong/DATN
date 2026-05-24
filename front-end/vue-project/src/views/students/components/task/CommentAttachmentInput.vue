<!-- src/components/students/task/CommentAttachmentInput.vue -->
<template>
  <div v-if="files.length > 0" class="space-y-1.5 mb-2">
    <div
      v-for="(file, idx) in files"
      :key="idx"
      class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg group"
    >
      <!-- Icon theo loại file -->
      <div class="flex-shrink-0">
        <svg
          v-if="isImage(file)"
          class="w-5 h-5 text-emerald-600"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
            clip-rule="evenodd"
          />
        </svg>
        <svg v-else class="w-5 h-5 text-slate-500" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-base font-medium text-slate-700 truncate">{{ file.name }}</p>
        <p class="text-[10px] text-slate-400">{{ formatBytes(file.size) }}</p>
      </div>
      <button
        type="button"
        @click="removeFile(idx)"
        class="text-slate-400 hover:text-red-500 transition"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>
    </div>
  </div>

  <!-- Nút chọn file -->
  <button
    type="button"
    @click="$refs.fileInput.click()"
    class="flex items-center gap-1 px-2.5 py-1.5 text-base text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
  >
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
      />
    </svg>
    Đính kèm file
  </button>

  <input ref="fileInput" type="file" multiple class="hidden" @change="onFilesSelected" />
</template>

<script setup>
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  files: { type: Array, default: () => [] },
  maxFiles: { type: Number, default: 10 },
  maxSize: { type: Number, default: 20 * 1024 * 1024 }, // 20MB
})

const emit = defineEmits(['update:files'])

const toast = useToastStore()

function onFilesSelected(e) {
  const selected = Array.from(e.target.files)
  const newFiles = [...props.files]

  for (const file of selected) {
    if (newFiles.length >= props.maxFiles) {
      toast.error(`Tối đa ${props.maxFiles} file`)
      break
    }
    if (file.size > props.maxSize) {
      toast.error(`${file.name}: vượt quá 20MB`)
      continue
    }
    newFiles.push(file)
  }

  emit('update:files', newFiles)
  e.target.value = '' // reset input
}

function removeFile(index) {
  const newFiles = [...props.files]
  newFiles.splice(index, 1)
  emit('update:files', newFiles)
}

function isImage(file) {
  return file.type?.startsWith('image/')
}

function formatBytes(b) {
  if (b < 1024) return b + ' B'
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'
  return (b / 1048576).toFixed(1) + ' MB'
}
</script>
