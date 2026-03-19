<template>
  <div
    class="fixed inset-0 bg-black/55 backdrop-blur-sm z-50 flex items-center justify-center p-5"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl w-full max-w-[520px] shadow-2xl animate-modal font-['Be_Vietnam_Pro',sans-serif]"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-400 flex items-center justify-center text-white flex-shrink-0"
          >
            <svg
              width="18"
              height="18"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
              <polyline points="17 8 12 3 7 8" />
              <line x1="12" y1="3" x2="12" y2="15" />
            </svg>
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900">Nhập tài liệu</h3>
            <p class="text-xs text-slate-400 mt-0.5">Hỗ trợ PDF, DOCX, TXT, CSV,XLSX, JSON</p>
          </div>
        </div>
        <button
          class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 text-lg flex items-center justify-center hover:bg-red-100 hover:text-red-500 transition-colors"
          @click="$emit('close')"
        >
          ×
        </button>
      </div>

      <!-- Body -->
      <div class="p-6">
        <!-- Dropzone -->
        <div
          class="border-2 border-dashed rounded-2xl text-center cursor-pointer transition-all duration-200 flex flex-col items-center gap-3 bg-slate-50/80"
          :class="[
            isDragging
              ? 'border-indigo-500 bg-indigo-50 scale-[1.01]'
              : 'border-slate-300 hover:border-indigo-400 hover:bg-indigo-50/60',
            files.length > 0 ? 'py-5 px-6' : 'py-9 px-6',
          ]"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          @click="triggerInput"
        >
          <input
            ref="fileInputRef"
            type="file"
            multiple
            accept=".pdf,.docx,.txt,.csv,.json"
            class="hidden"
            @change="handleFileSelect"
          />
          <div
            class="w-16 h-16 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-indigo-500"
          >
            <svg
              width="32"
              height="32"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
            >
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
              <polyline points="17 8 12 3 7 8" />
              <line x1="12" y1="3" x2="12" y2="15" />
            </svg>
          </div>
          <p class="text-sm text-slate-600">
            Kéo thả file vào đây hoặc <strong class="text-indigo-600">nhấn để chọn</strong>
          </p>
          <div class="flex gap-1.5 flex-wrap justify-center">
            <span
              v-for="ext in ['PDF', 'DOCX', 'TXT', 'CSV', 'XLSX', 'JSON']"
              :key="ext"
              class="bg-white border border-slate-200 rounded-md px-2 py-0.5 text-[11px] font-semibold text-slate-500"
              >{{ ext }}</span
            >
          </div>
        </div>

        <!-- File list -->
        <transition-group
          v-if="files.length > 0"
          name="file-list"
          tag="div"
          class="mt-3.5 flex flex-col gap-2"
        >
          <div
            v-for="(f, i) in files"
            :key="f.name + i"
            class="flex items-center gap-2.5 px-3 py-2.5 bg-slate-50 border border-slate-100 rounded-xl hover:border-indigo-200 transition-colors"
          >
            <!-- File type icon -->
            <div
              class="w-9 h-9 rounded-lg flex items-center justify-center text-[9px] font-bold flex-shrink-0"
              :class="extIconClass(f.name)"
            >
              {{ getExt(f.name) }}
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <span class="text-[13px] font-medium text-slate-800 block truncate">{{
                f.name
              }}</span>
              <span class="text-[11.5px] text-slate-400">{{ formatSize(f.size) }}</span>
            </div>

            <!-- Status -->
            <div class="flex-shrink-0">
              <span
                v-if="importing"
                class="inline-block w-3.5 h-3.5 border-2 border-indigo-200 border-t-indigo-500 rounded-full animate-spin"
              ></span>
              <span v-else class="text-[11.5px] text-emerald-500 font-medium">Sẵn sàng</span>
            </div>

            <!-- Remove -->
            <button
              class="w-6 h-6 rounded-md text-slate-400 text-base flex items-center justify-center hover:bg-red-100 hover:text-red-500 transition-colors flex-shrink-0 disabled:opacity-40 disabled:cursor-default"
              :disabled="importing"
              @click.stop="files.splice(i, 1)"
            >
              ×
            </button>
          </div>
        </transition-group>

        <!-- Empty hint -->
        <p v-if="files.length === 0" class="text-[12.5px] text-slate-300 text-center mt-3">
          Chưa có file nào được chọn. Kéo thả hoặc nhấn vào ô trên để thêm file.
        </p>

        <!-- Progress bar -->
        <div v-if="importing" class="mt-3.5">
          <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 transition-all duration-300"
              :style="{ width: progress + '%' }"
            ></div>
          </div>
          <span class="block text-center text-xs text-slate-400 mt-1.5"
            >Đang xử lý... {{ progress }}%</span
          >
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-2.5 px-6 py-4 border-t border-slate-100">
        <span v-if="files.length > 0" class="text-[12.5px] font-medium text-indigo-500 mr-auto">
          {{ files.length }} file đã chọn
        </span>
        <button
          class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium border border-slate-200 text-slate-500 bg-transparent hover:bg-slate-50 disabled:opacity-50 disabled:cursor-default transition-colors"
          :disabled="importing"
          @click="$emit('close')"
        >
          Hủy
        </button>
        <button
          class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium text-white bg-gradient-to-br from-indigo-500 to-indigo-400 shadow-[0_2px_10px_#6366f130] hover:shadow-[0_4px_16px_#6366f140] hover:-translate-y-px disabled:opacity-50 disabled:cursor-default disabled:transform-none transition-all"
          :disabled="files.length === 0 || importing"
          @click="handleImport"
        >
          <svg
            v-if="!importing"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
            <polyline points="17 8 12 3 7 8" />
            <line x1="12" y1="3" x2="12" y2="15" />
          </svg>
          <span
            v-if="importing"
            class="inline-block w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"
          ></span>
          {{
            importing
              ? 'Đang nhập...'
              : `Nhập${files.length > 0 ? ' (' + files.length + ' file)' : ''}`
          }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useToastStore } from '@/stores/toast'

//Emits
const emit = defineEmits(['close', 'imported'])

//Store
const toast = useToastStore()

//State
const fileInputRef = ref(null)
const files = ref([])
const isDragging = ref(false)
const importing = ref(false)
const progress = ref(0)

//Helpers
const ALLOWED = ['pdf', 'docx', 'txt', 'csv', 'json', 'xlsx']

function getExt(name) {
  return name.split('.').pop().toUpperCase()
}

function extIconClass(name) {
  const ext = name.split('.').pop().toLowerCase()
  const map = {
    pdf: 'bg-red-50 text-red-500',
    docx: 'bg-blue-50 text-blue-500',
    txt: 'bg-green-50 text-green-600',
    csv: 'bg-emerald-50 text-emerald-500',
    json: 'bg-yellow-50 text-yellow-600',
  }
  return map[ext] ?? 'bg-slate-100 text-slate-500'
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

function filterAllowed(fileList) {
  const arr = Array.from(fileList)
  const valid = arr.filter((f) => {
    const ext = f.name.split('.').pop().toLowerCase()
    return ALLOWED.includes(ext)
  })
  const invalid = arr.length - valid.length
  if (invalid > 0) toast.error(`Bỏ qua ${invalid} file không hỗ trợ`)
  return valid
}

function addFiles(fileList) {
  const newFiles = filterAllowed(fileList)
  const existing = new Set(files.value.map((f) => f.name))
  const unique = newFiles.filter((f) => !existing.has(f.name))
  files.value.push(...unique)
}

// ─── Events ───────────────────────────────────────────────────────────────────
function triggerInput() {
  fileInputRef.value?.click()
}

function handleDrop(e) {
  isDragging.value = false
  addFiles(e.dataTransfer.files)
}

function handleFileSelect(e) {
  addFiles(e.target.files)
  e.target.value = ''
}

//Import
async function handleImport() {
  if (files.value.length === 0) return
  importing.value = true
  progress.value = 0

  // Giả lập tiến trình — thay bằng API call thực tế
  const step = 100 / files.value.length
  for (let i = 0; i < files.value.length; i++) {
    await new Promise((r) => setTimeout(r, 400))
    progress.value = Math.round((i + 1) * step)
  }

  toast.success(`Đã nhập thành công ${files.value.length} tài liệu vào kho tri thức`)
  emit('imported', [...files.value])
  importing.value = false
  files.value = []
  emit('close')
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap');

.animate-modal {
  animation: modalIn 0.2s ease;
}
@keyframes modalIn {
  from {
    transform: translateY(20px) scale(0.97);
    opacity: 0;
  }
  to {
    transform: none;
    opacity: 1;
  }
}

/* Vue transition-group — không thể viết bằng Tailwind thuần */
.file-list-enter-active,
.file-list-leave-active {
  transition: all 0.2s;
}
.file-list-enter-from {
  opacity: 0;
  transform: translateX(-8px);
}
.file-list-leave-to {
  opacity: 0;
  transform: translateX(8px);
}
</style>
