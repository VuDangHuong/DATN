<!-- src/components/students/task/CommentAttachmentList.vue -->
<template>
  <div v-if="attachments?.length" class="mt-2 space-y-1.5">
    <!-- Image grid -->
    <div v-if="images.length" class="grid grid-cols-3 gap-1.5 mb-2">
      <div v-for="att in images" :key="att.id" class="relative group">
        <a :href="att.file_url" target="_blank" rel="noopener">
          <img
            :src="att.file_url"
            :alt="att.file_name"
            class="w-full h-20 object-cover rounded-lg border border-slate-200 hover:opacity-90 transition"
          />
        </a>
        <button
          v-if="canDelete(att)"
          @click="$emit('delete', att.id)"
          class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white opacity-0 group-hover:opacity-100 flex items-center justify-center hover:bg-red-600 transition"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- Non-image files -->
    <div
      v-for="att in files"
      :key="att.id"
      class="flex items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-lg group hover:bg-slate-100 transition"
    >
      <span class="text-base flex-shrink-0">{{ fileIcon(att.mime_type) }}</span>
      <a :href="att.file_url" target="_blank" rel="noopener" class="flex-1 min-w-0">
        <p class="text-xs font-medium text-indigo-700 truncate hover:underline">
          {{ att.file_name }}
        </p>
        <p class="text-[10px] text-slate-400">{{ att.file_size_human }}</p>
      </a>
      <a
        :href="att.file_url"
        :download="att.file_name"
        class="p-1 hover:bg-white rounded text-slate-400 hover:text-indigo-600 transition"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
          />
        </svg>
      </a>
      <button
        v-if="canDelete(att)"
        @click="$emit('delete', att.id)"
        class="p-1 rounded text-slate-400 hover:text-red-500 hover:bg-red-50 opacity-0 group-hover:opacity-100 transition"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  attachments: { type: Array, default: () => [] },
  currentUserId: { type: Number, default: null },
  commentOwnerId: { type: Number, default: null },
})

defineEmits(['delete'])

const images = computed(() => props.attachments.filter((a) => a.is_image))
const files = computed(() => props.attachments.filter((a) => !a.is_image))

function canDelete(att) {
  // Chỉ uploader hoặc chủ comment được xóa
  return att.uploaded_by === props.currentUserId || props.commentOwnerId === props.currentUserId
}

function fileIcon(mime) {
  if (!mime) return '📄'
  if (mime.includes('pdf')) return '📕'
  if (mime.includes('word') || mime.includes('document')) return '📘'
  if (mime.includes('sheet') || mime.includes('excel')) return '📗'
  if (mime.includes('zip') || mime.includes('compress')) return '🗜️'
  if (mime.includes('video')) return '🎬'
  if (mime.includes('audio')) return '🎵'
  if (mime.startsWith('text/')) return '📝'
  return '📎'
}
</script>
