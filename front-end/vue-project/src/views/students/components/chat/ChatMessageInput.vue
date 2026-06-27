<!-- src/components/students/chat/ChatMessageInput.vue -->
<template>
  <div class="bg-white p-3 space-y-2">
    <!-- File preview -->
    <div v-if="files.length" class="space-y-1.5">
      <div
        v-for="(file, idx) in files"
        :key="idx"
        class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg"
      >
        <span class="text-lg flex items-center">
          <SvgIcon v-if="isImage(file)" name="image" class="h-5 w-5 text-indigo-500" />
          <SvgIcon v-else name="document" class="h-5 w-5 text-red-500" />
        </span>
        <div class="flex-1 min-w-0">
          <p class="text-base font-medium text-slate-700 truncate">{{ file.name }}</p>
          <p class="text-[10px] text-slate-400">{{ formatBytes(file.size) }}</p>
        </div>
        <button @click="removeFile(idx)" class="text-slate-400 hover:text-red-500">
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

    <!-- Selected mentions chips -->
    <div v-if="selectedMentions.length" class="flex flex-wrap gap-1">
      <span
        v-for="m in selectedMentions"
        :key="m.id"
        class="inline-flex items-center gap-1 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-base"
      >
        @{{ m.name }}
        <button @click="removeMention(m.id)" class="hover:text-indigo-900">
          <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </span>
    </div>

    <!-- Mention dropdown -->
    <div v-if="showMentionDropdown" class="relative">
      <div
        class="absolute bottom-full left-0 mb-1 w-72 max-h-60 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-lg z-10"
      >
        <div v-if="!filteredMembers.length" class="p-3 text-center text-base text-slate-400">
          Không tìm thấy thành viên
        </div>
        <button
          v-for="(member, idx) in filteredMembers"
          :key="member.id"
          @click="selectMention(member)"
          class="w-full flex items-center gap-2 px-3 py-2 hover:bg-indigo-50 text-left transition"
          :class="idx === activeIndex ? 'bg-indigo-50' : ''"
        >
          <div
            class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-base font-bold text-indigo-700 overflow-hidden flex-shrink-0"
          >
            <img
              v-if="member.avatar_url"
              :src="member.avatar_url"
              class="w-full h-full object-cover"
            />
            <span v-else>{{ member.name?.charAt(0) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-700 truncate">{{ member.name }}</p>
            <p class="text-[10px] text-slate-400">{{ member.code }}</p>
          </div>
        </button>
      </div>
    </div>

    <!-- Input row -->
    <div class="flex items-end gap-2">
      <!-- File attach button -->
      <button
        type="button"
        @click="$refs.fileInput.click()"
        class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg transition flex-shrink-0"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
          />
        </svg>
      </button>
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/*,application/pdf"
        class="hidden"
        @change="onFilesSelected"
      />

      <!-- Textarea -->
      <textarea
        ref="textareaRef"
        v-model="content"
        @input="onContentInput"
        @keydown="onKeyDown"
        placeholder="Nhập tin nhắn... Gõ @ để nhắc thành viên"
        rows="1"
        class="flex-1 px-4 py-2 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-slate-400 max-h-32"
      />

      <!-- Send -->
      <button
        @click="handleSend"
        :disabled="!canSend || sending"
        class="px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 flex-shrink-0"
      >
        <svg v-if="!sending" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
          />
        </svg>
        <div
          v-else
          class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"
        />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const props = defineProps({
  members: { type: Array, default: () => [] }, // [{id, name, code, avatar_url}]
  sending: { type: Boolean, default: false },
  maxFiles: { type: Number, default: 5 },
  maxSize: { type: Number, default: 20 * 1024 * 1024 },
})

const emit = defineEmits(['send'])

const toast = useToastStore()

const content = ref('')
const files = ref([])
const selectedMentions = ref([]) // [{id, name, code}]
const showMentionDropdown = ref(false)
const mentionQuery = ref('')
const mentionStartPos = ref(-1)
const activeIndex = ref(0)
const textareaRef = ref(null)

const canSend = computed(() => content.value.trim() || files.value.length)

const filteredMembers = computed(() => {
  const q = mentionQuery.value.toLowerCase().trim()
  const selectedIds = selectedMentions.value.map((m) => m.id)

  return props.members
    .filter((m) => !selectedIds.includes(m.id)) // Bỏ những người đã tag
    .filter((m) => !q || m.name.toLowerCase().includes(q) || m.code?.toLowerCase().includes(q))
    .slice(0, 8)
})

// ── Handlers ─────────────────────────────────

function onContentInput(e) {
  const text = e.target.value
  const cursorPos = e.target.selectionStart

  // Tìm @ gần nhất TRƯỚC con trỏ
  let atPos = -1
  for (let i = cursorPos - 1; i >= 0; i--) {
    const ch = text[i]
    if (ch === '@') {
      atPos = i
      break
    }
    if (/\s/.test(ch)) break // gặp whitespace → hủy
  }

  if (atPos === -1) {
    showMentionDropdown.value = false
    mentionStartPos.value = -1
    return
  }

  // @ phải ở đầu chuỗi hoặc sau whitespace
  if (atPos > 0 && !/\s/.test(text[atPos - 1])) {
    showMentionDropdown.value = false
    return
  }

  const query = text.substring(atPos + 1, cursorPos)

  // Query không có whitespace
  if (/\s/.test(query)) {
    showMentionDropdown.value = false
    return
  }

  mentionStartPos.value = atPos
  mentionQuery.value = query
  showMentionDropdown.value = true
  activeIndex.value = 0
}

function onKeyDown(e) {
  // Nếu dropdown đang mở
  if (showMentionDropdown.value && filteredMembers.value.length > 0) {
    if (e.key === 'ArrowDown') {
      e.preventDefault()
      activeIndex.value = (activeIndex.value + 1) % filteredMembers.value.length
      return
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault()
      activeIndex.value =
        (activeIndex.value - 1 + filteredMembers.value.length) % filteredMembers.value.length
      return
    }
    if (e.key === 'Enter' || e.key === 'Tab') {
      e.preventDefault()
      selectMention(filteredMembers.value[activeIndex.value])
      return
    }
    if (e.key === 'Escape') {
      showMentionDropdown.value = false
      return
    }
  }

  // Enter để gửi (Shift+Enter = xuống dòng)
  if (e.key === 'Enter' && !e.shiftKey && !showMentionDropdown.value) {
    e.preventDefault()
    handleSend()
  }
}

function selectMention(member) {
  if (!member) return

  // Thay text từ @ → cursor bằng @Tên
  const before = content.value.substring(0, mentionStartPos.value)
  const after = content.value.substring(textareaRef.value?.selectionStart ?? content.value.length)
  content.value = `${before}@${member.name} ${after}`

  // Thêm vào mentions
  if (!selectedMentions.value.find((m) => m.id === member.id)) {
    selectedMentions.value.push(member)
  }

  showMentionDropdown.value = false
  mentionStartPos.value = -1

  // Focus lại textarea
  nextTick(() => {
    textareaRef.value?.focus()
  })
}

function removeMention(userId) {
  selectedMentions.value = selectedMentions.value.filter((m) => m.id !== userId)
}

function onFilesSelected(e) {
  const selected = Array.from(e.target.files)
  const newFiles = [...files.value]

  for (const file of selected) {
    if (newFiles.length >= props.maxFiles) {
      toast.error(`Tối đa ${props.maxFiles} file`)
      break
    }
    if (file.size > props.maxSize) {
      toast.error(`${file.name}: vượt quá 20MB`)
      continue
    }
    if (!file.type.startsWith('image/') && file.type !== 'application/pdf') {
      toast.error(`${file.name}: chỉ chấp nhận image hoặc PDF`)
      continue
    }
    newFiles.push(file)
  }

  files.value = newFiles
  e.target.value = ''
}

function removeFile(idx) {
  files.value.splice(idx, 1)
}

function isImage(file) {
  return file.type?.startsWith('image/')
}

function formatBytes(b) {
  if (b < 1024) return b + ' B'
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'
  return (b / 1048576).toFixed(1) + ' MB'
}

function handleSend() {
  if (!canSend.value) return

  emit('send', {
    content: content.value.trim(),
    mentions: selectedMentions.value.map((m) => m.id),
    files: files.value,
  })

  // Reset
  content.value = ''
  files.value = []
  selectedMentions.value = []
}
</script>
