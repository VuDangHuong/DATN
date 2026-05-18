<!-- src/components/students/task/TaskDetailModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
      >
        <template v-if="task">
          <!-- Header -->
          <div class="p-6 border-b border-slate-100 flex-shrink-0">
            <div class="flex items-start justify-between">
              <div>
                <span
                  :class="priorityClass(task.priority)"
                  class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                  >{{ task.priority }}</span
                >
                <h3 class="text-lg font-bold text-slate-800 mt-2">{{ task.title }}</h3>
              </div>
              <button @click="$emit('close')" class="p-1.5 hover:bg-slate-100 rounded-lg">
                <svg
                  class="w-5 h-5 text-slate-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
            <p v-if="task.description" class="text-sm text-slate-600 mt-3 leading-relaxed">
              {{ task.description }}
            </p>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- Info grid -->
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="p-3 bg-slate-50 rounded-xl">
                <p class="text-slate-400 text-xs mb-1">Trạng thái</p>
                <span
                  :class="statusClass(task.status)"
                  class="px-2 py-0.5 rounded-full text-xs font-bold"
                  >{{ statusLabel(task.status) }}</span
                >
              </div>
              <div class="p-3 bg-slate-50 rounded-xl">
                <p class="text-slate-400 text-xs mb-1">Giao cho</p>
                <p class="font-medium text-slate-700">{{ task.assignee?.name || 'Chưa giao' }}</p>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl">
                <p class="text-slate-400 text-xs mb-1">Người tạo</p>
                <p class="font-medium text-slate-700">{{ task.creator?.name }}</p>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl">
                <p class="text-slate-400 text-xs mb-1">Deadline</p>
                <p class="font-medium" :class="task.is_overdue ? 'text-red-600' : 'text-slate-700'">
                  {{ formatDate(task.deadline) }}
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <button
                v-if="isLeader"
                @click="$emit('edit-task', task)"
                class="flex-1 py-2 bg-slate-100 text-slate-700 rounded-xl text-sm font-medium hover:bg-slate-200"
              >
                ✏️ Sửa
              </button>
              <select
                v-if="canChangeStatus"
                :value="task.status"
                @change="$emit('change-status', task.id, $event.target.value)"
                class="flex-1 py-2 border border-slate-200 rounded-xl text-sm font-medium text-center cursor-pointer"
              >
                <option value="todo">📋 Cần làm</option>
                <option value="doing">🔄 Đang làm</option>
                <option v-if="isLeader" value="done">✅ Hoàn thành (override)</option>
              </select>
            </div>
            <TaskReviewSection
              :task="task"
              :current-user-id="currentUserId"
              :is-leader="isLeader"
              :leader-name="leaderName"
              @updated="$emit('refresh')"
            />
            <!-- Comments -->
            <div>
              <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <svg
                  class="w-4 h-4 text-slate-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"
                  />
                </svg>
                Bình luận ({{ task.comments?.length || 0 }})
              </h4>

              <!-- Danh sách comments -->
              <div class="space-y-3 max-h-80 overflow-y-auto mb-4">
                <div v-if="!task.comments?.length" class="text-center py-6 text-sm text-slate-400">
                  Chưa có bình luận.
                </div>
                <TaskCommentItem
                  v-for="comment in task.comments"
                  :key="comment.id"
                  :comment="comment"
                  :current-user-id="currentUserId"
                  :is-leader="isLeader"
                  :is-editing="editingCommentId === comment.id"
                  :edit-content="editCommentContent"
                  :edit-files="editCommentFiles"
                  :removed-attachment-ids="removedEditAttachmentIds"
                  @start-edit="$emit('start-edit-comment', $event)"
                  @cancel-edit="$emit('cancel-edit-comment')"
                  @save-edit="$emit('save-edit-comment')"
                  @delete="$emit('delete-comment', $event)"
                  @delete-attachment="$emit('delete-attachment', $event)"
                  @toggle-remove-attachment="$emit('toggle-remove-attachment', $event)"
                  @update:edit-content="$emit('update:edit-comment-content', $event)"
                  @update:edit-files="$emit('update:edit-comment-files', $event)"
                />
              </div>

              <!-- Form thêm comment -->
              <div class="space-y-2">
                <CommentAttachmentInput
                  :files="newCommentFiles"
                  @update:files="$emit('update:new-comment-files', $event)"
                />
                <div class="flex gap-2 items-end">
                  <textarea
                    :value="newComment"
                    @input="$emit('update:new-comment', $event.target.value)"
                    @keydown.enter.exact.prevent="$emit('add-comment')"
                    placeholder="Viết bình luận... (Enter để gửi)"
                    rows="2"
                    class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-slate-400"
                  />
                  <button
                    @click="$emit('add-comment')"
                    :disabled="(!newComment.trim() && !newCommentFiles.length) || commentSending"
                    class="px-4 py-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 flex-shrink-0"
                  >
                    <svg
                      v-if="!commentSending"
                      class="w-5 h-5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
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
            </div>

            <!-- Activities -->
            <div v-if="task.activities?.length">
              <h4 class="text-sm font-bold text-slate-700 mb-3">Lịch sử hoạt động</h4>
              <div class="space-y-2 max-h-40 overflow-y-auto">
                <div
                  v-for="a in task.activities"
                  :key="a.id"
                  class="flex items-start gap-2 text-xs"
                >
                  <div class="w-1.5 h-1.5 rounded-full bg-indigo-400 mt-1.5 flex-shrink-0" />
                  <div>
                    <span class="font-medium text-slate-600">{{ a.user?.name }}</span>
                    <span class="text-slate-400">
                      · {{ activityLabel(a.action) }} · {{ formatTime(a.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import TaskCommentItem from './TaskCommentItem.vue'
import CommentAttachmentInput from './CommentAttachmentInput.vue'
import TaskReviewSection from './TaskReviewSection.vue'

defineProps({
  show: { type: Boolean, default: false },
  task: { type: Object, default: null },
  currentUserId: { type: Number, required: true },
  isLeader: { type: Boolean, default: false },
  canChangeStatus: { type: Boolean, default: false },
  newComment: { type: String, default: '' },
  newCommentFiles: { type: Array, default: () => [] },
  commentSending: { type: Boolean, default: false },
  editingCommentId: { type: Number, default: null },
  editCommentContent: { type: String, default: '' },
  editCommentFiles: { type: Array, default: () => [] },
  removedEditAttachmentIds: { type: Array, default: () => [] },
  leaderName: { type: String, default: 'Nhóm trưởng' },
})

defineEmits([
  'close',
  'edit-task',
  'change-status',
  'add-comment',
  'start-edit-comment',
  'cancel-edit-comment',
  'save-edit-comment',
  'delete-comment',
  'delete-attachment',
  'toggle-remove-attachment',
  'update:new-comment',
  'update:new-comment-files',
  'update:edit-comment-content',
  'update:edit-comment-files',
])

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatTime(d) {
  if (!d) return ''
  const date = new Date(d)
  return (
    date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) +
    ' · ' +
    date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
  )
}

function activityLabel(action) {
  return (
    {
      created: 'đã tạo task',
      status_changed: 'đổi trạng thái',
      updated: 'cập nhật task',
      commented: 'đã bình luận',
      comment_updated: 'sửa bình luận',
      comment_deleted: 'xóa bình luận',
    }[action] || action
  )
}

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-700',
      high: 'bg-orange-100 text-orange-700',
      medium: 'bg-blue-100 text-blue-700',
      low: 'bg-slate-100 text-slate-600',
    }[p] || ''
  )
}

function statusClass(s) {
  return (
    {
      todo: 'bg-slate-100 text-slate-600',
      doing: 'bg-blue-100 text-blue-700',
      done: 'bg-emerald-100 text-emerald-700',
      pending_review: 'bg-amber-100 text-amber-700',
      late: 'bg-red-100 text-red-700',
    }[s] || ''
  )
}

function statusLabel(s) {
  return (
    {
      todo: 'Cần làm',
      doing: 'Đang làm',
      pending_review: 'Chờ duyệt',
      done: 'Hoàn thành',
      late: 'Trễ hạn',
    }[s] || s
  )
}
</script>
