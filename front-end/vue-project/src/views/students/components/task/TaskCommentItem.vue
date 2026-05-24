<!-- src/components/students/task/TaskCommentItem.vue -->
<template>
  <div class="flex gap-3 group">
    <!-- Avatar -->
    <div
      class="w-8 h-8 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center text-base font-bold text-white"
      :class="
        comment.user?.avatar_url
          ? 'bg-slate-200'
          : isOwn
            ? 'bg-gradient-to-br from-indigo-500 to-purple-600'
            : 'bg-gradient-to-br from-slate-400 to-slate-500'
      "
    >
      <img
        v-if="comment.user?.avatar_url"
        :src="comment.user.avatar_url"
        :alt="comment.user.name"
        class="w-full h-full object-cover"
        @error="$event.target.style.display = 'none'"
      />
      <span v-else>{{ comment.user?.name?.charAt(0) || '?' }}</span>
    </div>

    <div class="flex-1 min-w-0">
      <!-- Header -->
      <div class="flex items-center gap-2 mb-0.5">
        <span class="text-base font-semibold text-slate-700">{{ comment.user?.name }}</span>
        <span class="text-[10px] text-slate-400">{{ formatTime(comment.created_at) }}</span>
        <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
          <button
            v-if="isOwn"
            @click="$emit('start-edit', comment)"
            class="p-1 rounded hover:bg-slate-100 text-slate-400 hover:text-indigo-600"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
          </button>
          <button
            v-if="isOwn || isLeader"
            @click="$emit('delete', comment.id)"
            class="p-1 rounded hover:bg-red-50 text-slate-400 hover:text-red-500"
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

      <!-- Edit mode -->
      <div v-if="isEditing" class="space-y-2 mt-1">
        <!-- Attachments hiện có với checkbox xóa -->
        <div v-if="comment.attachments?.length" class="space-y-1">
          <p class="text-[10px] font-bold text-slate-500 uppercase">File hiện có</p>
          <label
            v-for="att in comment.attachments"
            :key="att.id"
            class="flex items-center gap-2 p-1.5 bg-slate-50 rounded text-base cursor-pointer"
          >
            <input
              type="checkbox"
              :checked="!removedAttachmentIds.includes(att.id)"
              @change="$emit('toggle-remove-attachment', att.id)"
              class="rounded text-indigo-600"
            />
            <span
              :class="removedAttachmentIds.includes(att.id) ? 'line-through text-slate-400' : ''"
            >
              {{ att.file_name }} ({{ att.file_size_human }})
            </span>
          </label>
          <p class="text-[10px] text-slate-400">Bỏ tick = đánh dấu xóa khi lưu</p>
        </div>

        <CommentAttachmentInput
          :files="editFiles"
          @update:files="$emit('update:edit-files', $event)"
        />

        <input
          :value="editContent"
          @input="$emit('update:edit-content', $event.target.value)"
          class="w-full px-3 py-1.5 border border-indigo-300 rounded-lg text-sm"
          @keyup.enter="$emit('save-edit')"
          @keyup.escape="$emit('cancel-edit')"
        />

        <div class="flex gap-2">
          <button
            @click="$emit('save-edit')"
            class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-base font-semibold"
          >
            Lưu
          </button>
          <button
            @click="$emit('cancel-edit')"
            class="px-3 py-1.5 border border-slate-200 rounded-lg text-base text-slate-500"
          >
            Hủy
          </button>
        </div>
      </div>

      <!-- Display mode -->
      <div v-else>
        <p class="text-sm text-slate-600 leading-relaxed">{{ comment.content }}</p>
        <CommentAttachmentList
          :attachments="comment.attachments"
          :current-user-id="currentUserId"
          :comment-owner-id="comment.user?.id"
          @delete="$emit('delete-attachment', $event)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import CommentAttachmentInput from './CommentAttachmentInput.vue'
import CommentAttachmentList from './CommentAttachmentList.vue'

const props = defineProps({
  comment: { type: Object, required: true },
  currentUserId: { type: Number, required: true },
  isLeader: { type: Boolean, default: false },
  isEditing: { type: Boolean, default: false },
  editContent: { type: String, default: '' },
  editFiles: { type: Array, default: () => [] },
  removedAttachmentIds: { type: Array, default: () => [] },
})

defineEmits([
  'start-edit',
  'cancel-edit',
  'save-edit',
  'delete',
  'delete-attachment',
  'toggle-remove-attachment',
  'update:edit-content',
  'update:edit-files',
])

const isOwn = computed(() => props.comment.user?.id === props.currentUserId)

function formatTime(d) {
  if (!d) return ''
  const date = new Date(d)
  return (
    date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) +
    ' · ' +
    date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
  )
}
</script>
