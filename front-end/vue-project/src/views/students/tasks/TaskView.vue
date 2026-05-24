<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Quản lý công việc</h2>
        <p class="text-slate-500 mt-0.5 text-sm">Kanban board · Nhóm {{ groupName }}</p>
      </div>
      <div v-if="isLeader && groupId" class="flex items-center gap-2">
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-white border border-indigo-200 text-indigo-700 rounded-xl text-sm font-semibold hover:bg-indigo-50 transition flex items-center gap-2"
        >
          <SvgIcon name="plus" class="w-4 h-4" />
          Tạo 1 việc
        </button>
        <button
          @click="showBulkModal = true"
          class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition flex items-center gap-2"
        >
          <SvgIcon name="tasks" class="w-4 h-4" />
          Tạo nhiều việc
        </button>
      </div>
    </div>

    <!-- No group -->
    <div v-if="!groupId" class="bg-white rounded-2xl border p-12 text-center">
      <p class="text-slate-500">Bạn chưa có nhóm. Hãy tạo hoặc tham gia nhóm trước.</p>
    </div>

    <template v-else>
      <!-- Component thống kê -->
      <TaskStats :stats="stats" />

      <KanbanboardView
        :tasks="tasks"
        :members="members"
        :is-leader="isLeader"
        :current-user-id="user.id"
        @click-task="openTaskDetail"
        @change-status="handleChangeStatus"
        @delete-task="handleDeleteTask"
        @task-moved="handleTaskMoved"
      />
    </template>

    <!-- Modal tạo/sửa task -->
    <TaskFormModal
      :show="showTaskModal"
      :editing-task="editingTask"
      :members="members"
      :loading="taskLoading"
      @close="showTaskModal = false"
      @save="handleSaveTask"
    />

    <!-- Modal chi tiết task -->
    <TaskDetailModal
      :show="showDetailModal"
      :task="currentTask"
      :current-user-id="user.id"
      :is-leader="isLeader"
      :can-change-status="canChangeStatusFor(currentTask)"
      :new-comment="newComment"
      :new-comment-files="commentFiles"
      :comment-sending="commentSending"
      :editing-comment-id="editingCommentId"
      :edit-comment-content="editCommentContent"
      :edit-comment-files="editCommentFiles"
      :removed-edit-attachment-ids="removedEditAttachmentIds"
      :leader-name="leaderName"
      @refresh="taskStore.fetchTasks(groupId)"
      @close="showDetailModal = false"
      @edit-task="openEditModal"
      @change-status="handleChangeStatus"
      @add-comment="handleAddComment"
      @start-edit-comment="startEditComment"
      @cancel-edit-comment="cancelEditComment"
      @save-edit-comment="handleUpdateComment"
      @delete-comment="handleDeleteComment"
      @delete-attachment="handleDeleteAttachment"
      @toggle-remove-attachment="toggleRemoveAttachment"
      @update:new-comment="newComment = $event"
      @update:new-comment-files="commentFiles = $event"
      @update:edit-comment-content="editCommentContent = $event"
      @update:edit-comment-files="editCommentFiles = $event"
    />

    <!-- Modal tạo nhiều task -->
    <BulkCreateTaskModal
      :show="showBulkModal"
      :group-id="groupId"
      :members="members"
      @close="showBulkModal = false"
      @success="onBulkSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { useTaskStore } from '@/stores/students/taskStore'
import { useGroupStore } from '@/stores/students/groupStore'
//Components
import KanbanboardView from './KanbanboardView.vue'
import TaskStats from '../components/task/TaskStats.vue'
import TaskFormModal from '../components/task/TaskFormModal.vue'
import TaskDetailModal from '../components/task/TaskDetailModal.vue'
import BulkCreateTaskModal from '../components/task/BulkCreateTaskModal.vue'
import SvgIcon from '@/components/icons/SVG.vue'
import { useToastStore } from '@/stores/toast'
const toast = useToastStore()
const route = useRoute()
const dashboardStore = useDashboardStore()
const taskStore = useTaskStore()
const groupStore = useGroupStore()

const { stats, currentTask, loading: taskLoading, tasks } = storeToRefs(taskStore)

const user = JSON.parse(localStorage.getItem('user') || '{}')

// ── State ─────────────────────────────────────────────────
const showTaskModal = ref(false)
const showDetailModal = ref(false)
const showBulkModal = ref(false)
const editingTask = ref(null)

// Comment state
const newComment = ref('')
const commentFiles = ref([])
const commentSending = ref(false)

// Edit comment state
const editingCommentId = ref(null)
const editCommentContent = ref('')
const editCommentFiles = ref([])
const removedEditAttachmentIds = ref([])

// ── Computed ──────────────────────────────────────────────
const groupId = computed(() => {
  const fromRoute = route.query.group_id
  if (fromRoute) return Number(fromRoute)
  return dashboardStore.myGroup?.id || null
})

const groupName = computed(() => dashboardStore.myGroup?.name || '')
const isLeader = computed(() => dashboardStore.myGroup?.leader?.id === user.id)
const members = computed(
  () => groupStore.currentGroup?.members || dashboardStore.myGroup?.members || [],
)
const leaderName = computed(() => dashboardStore.myGroup?.leader?.name || 'Nhóm trưởng')

function canChangeStatusFor(task) {
  if (!task) return false
  return task.assignee?.id === user.id || isLeader.value
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  if (!dashboardStore.classes.length) {
    await dashboardStore.fetchMyClasses()
  }
})

watch(
  () => dashboardStore.myGroup?.id,
  (id) => {
    if (id) {
      taskStore.fetchTasks(id)
      groupStore.fetchGroupDetail(id)
    }
  },
  { immediate: true },
)

// ── Task handlers ─────────────────────────────────────────
function openCreateModal() {
  editingTask.value = null
  showTaskModal.value = true
}

function openEditModal(task) {
  editingTask.value = task
  showDetailModal.value = false
  showTaskModal.value = true
}

async function openTaskDetail(task) {
  await taskStore.fetchTaskDetail(task.id)
  showDetailModal.value = true
}

async function handleSaveTask(data) {
  const result = editingTask.value
    ? await taskStore.updateTask(editingTask.value.id, groupId.value, data)
    : await taskStore.createTask(groupId.value, data)
  if (result.success) showTaskModal.value = false
}

async function handleChangeStatus(taskId, status) {
  if (!status) return
  await taskStore.changeStatus(taskId, groupId.value, status)
  showDetailModal.value = false
}

async function handleDeleteTask(taskId) {
  if (!taskId) return

  try {
    const res = await taskStore.deleteTask(taskId, groupId.value)
    if (res?.success === false) {
      toast.error(res.message || 'Không thể xóa task')
    } else {
      toast.success('Đã xóa task')
    }
  } catch (e) {
    toast.error(e.response?.data?.message || 'Lỗi khi xóa task')
  }
}

async function handleTaskMoved({ taskId, newStatus }) {
  await taskStore.changeStatus(taskId, groupId.value, newStatus)
}

async function onBulkSuccess() {
  // Store đã refresh
}

// ── Comment handlers ─────────────────────────────────────
async function handleAddComment() {
  if (!newComment.value.trim() && !commentFiles.value.length) return
  if (!currentTask.value) return

  commentSending.value = true
  const result = await taskStore.addComment(
    currentTask.value.id,
    newComment.value.trim(),
    commentFiles.value,
  )
  if (result.success) {
    newComment.value = ''
    commentFiles.value = []
  }
  commentSending.value = false
}

function startEditComment(comment) {
  editingCommentId.value = comment.id
  editCommentContent.value = comment.content
  editCommentFiles.value = []
  removedEditAttachmentIds.value = []
}

function cancelEditComment() {
  editingCommentId.value = null
  editCommentContent.value = ''
  editCommentFiles.value = []
  removedEditAttachmentIds.value = []
}

async function handleUpdateComment() {
  if (!editCommentContent.value.trim() || !editingCommentId.value) return
  const result = await taskStore.updateComment(
    editingCommentId.value,
    editCommentContent.value.trim(),
    editCommentFiles.value,
    removedEditAttachmentIds.value,
  )
  if (result.success) cancelEditComment()
}

async function handleDeleteComment(commentId) {
  if (!confirm('Xóa bình luận này?')) return
  await taskStore.deleteComment(commentId)
}

async function handleDeleteAttachment(attachmentId) {
  if (!confirm('Xóa file đính kèm này?')) return
  await taskStore.deleteAttachment(attachmentId, currentTask.value.id)
}

function toggleRemoveAttachment(attId) {
  const idx = removedEditAttachmentIds.value.indexOf(attId)
  if (idx >= 0) {
    removedEditAttachmentIds.value.splice(idx, 1)
  } else {
    removedEditAttachmentIds.value.push(attId)
  }
}
</script>
