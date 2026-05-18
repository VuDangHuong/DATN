// src/stores/taskStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { commentApi } from '@/api/students/studentApi'
import { taskApi } from '@/api/students/studentApi'
export const useTaskStore = defineStore('task', () => {
  const tasks = ref([])
  const stats = ref({ total: 0, todo: 0, doing: 0, done: 0, late: 0 })
  const currentTask = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({ status: '', priority: '', assignee_id: '' })

  // Kanban columns
  const todoTasks = computed(() => tasks.value.filter((t) => t.status === 'todo'))
  const doingTasks = computed(() => tasks.value.filter((t) => t.status === 'doing'))
  const doneTasks = computed(() => tasks.value.filter((t) => t.status === 'done'))
  const lateTasks = computed(() => tasks.value.filter((t) => t.status === 'late'))

  // Danh sách task
  async function fetchTasks(groupId, customFilters = null) {
    loading.value = true
    error.value = null
    try {
      const params = customFilters || {}
      // Xóa key rỗng
      Object.keys(params).forEach((key) => {
        if (!params[key]) delete params[key]
      })
      const { data } = await taskApi.getByGroup(groupId, params)
      console.log('API raw response:', data)
      tasks.value = data.tasks || data.data?.tasks || data.data || []
      stats.value = data.stats || data.data?.stats || stats.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tải task'
    } finally {
      loading.value = false
    }
  }

  // Chi tiết task
  async function fetchTaskDetail(taskId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await taskApi.getDetail(taskId)
      currentTask.value = data.task
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tải chi tiết task'
    } finally {
      loading.value = false
    }
  }

  // Tạo task
  async function createTask(groupId, taskData) {
    error.value = null
    try {
      const { data } = await taskApi.create(groupId, taskData)
      await fetchTasks(groupId)
      return { success: true, data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tạo task'
      return { success: false, message: error.value }
    }
  }

  // Cập nhật task
  async function updateTask(taskId, groupId, taskData) {
    error.value = null
    try {
      const { data } = await taskApi.update(taskId, taskData)
      await fetchTasks(groupId)
      return { success: true, data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi cập nhật task'
      return { success: false, message: error.value }
    }
  }

  // Đổi trạng thái
  async function changeStatus(taskId, groupId, status) {
    error.value = null
    try {
      await taskApi.updateStatus(taskId, status)
      await fetchTasks(groupId)
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi đổi trạng thái'
      return { success: false, message: error.value }
    }
  }

  // Xóa task
  async function deleteTask(taskId, groupId) {
    error.value = null
    try {
      await taskApi.delete(taskId)
      await fetchTasks(groupId)
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi xóa task'
      return { success: false, message: error.value }
    }
  }

  function setFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
  }

  // ── Task Comments ────────────────────────────

  // Lấy bình luận của task
  async function fetchComments(taskId) {
    try {
      const { data } = await commentApi.getByTask(taskId)
      // Cập nhật comments trong currentTask
      if (currentTask.value && currentTask.value.id === taskId) {
        currentTask.value.comments = data.comments || []
      }
      return { success: true, comments: data.comments }
    } catch (err) {
      return { success: false, message: err.response?.data?.message || 'Lỗi tải bình luận' }
    }
  }

  // Thêm bình luận
  async function addComment(taskId, content, files = []) {
    try {
      const { data } = await commentApi.create(taskId, { content, files })
      if (currentTask.value && currentTask.value.id === taskId) {
        if (!currentTask.value.comments) currentTask.value.comments = []
        currentTask.value.comments.push(data.comment)
      }
      return { success: true, comment: data.comment }
    } catch (err) {
      return { success: false, message: err.response?.data?.message || 'Lỗi thêm bình luận' }
    }
  }

  // Sửa bình luận
  async function updateComment(commentId, content, files = [], removedAttachmentIds = []) {
    try {
      const { data } = await commentApi.update(commentId, {
        content,
        files,
        removedAttachmentIds,
      })
      if (currentTask.value?.comments) {
        const idx = currentTask.value.comments.findIndex((c) => c.id === commentId)
        if (idx !== -1) {
          currentTask.value.comments[idx] = data.comment
        }
      }
      return { success: true }
    } catch (err) {
      return { success: false, message: err.response?.data?.message || 'Lỗi sửa bình luận' }
    }
  }

  // Xóa bình luận
  async function deleteComment(commentId) {
    try {
      await commentApi.delete(commentId)
      // Xóa khỏi danh sách
      if (currentTask.value?.comments) {
        currentTask.value.comments = currentTask.value.comments.filter((c) => c.id !== commentId)
      }
      return { success: true }
    } catch (err) {
      return { success: false, message: err.response?.data?.message || 'Lỗi xóa bình luận' }
    }
  }

  async function deleteAttachment(attachmentId, commentId) {
    try {
      await commentApi.deleteAttachment(attachmentId)
      // Update local: xóa attachment khỏi comment
      if (currentTask.value?.comments) {
        const comment = currentTask.value.comments.find((c) => c.id === commentId)
        if (comment?.attachments) {
          comment.attachments = comment.attachments.filter((a) => a.id !== attachmentId)
        }
      }
      return { success: true }
    } catch (err) {
      return { success: false, message: err.response?.data?.message || 'Lỗi xóa file' }
    }
  }

  async function bulkCreateTasks(groupId, tasksData) {
    loading.value = true
    try {
      const { data } = await taskApi.bulkCreate(groupId, tasksData)
      // Refresh task list
      await fetchTasks(groupId)

      return {
        success: true,
        data: data.data,
        total: data.total,
        message: data.message,
      }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Tạo công việc thất bại',
        errors: e.response?.data?.errors ?? {},
      }
    } finally {
      loading.value = false
    }
  }

  async function submitForReview(taskId, note = '') {
    try {
      const { data } = await taskApi.submitForReview(taskId, note)

      // Cập nhật local
      const idx = tasks.value.findIndex((t) => t.id === taskId)
      if (idx >= 0 && data.task) {
        tasks.value[idx] = data.task
      }
      if (currentTask.value?.id === taskId && data.task) {
        currentTask.value = data.task
      }

      return { success: true, message: data.message, data: data.task }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Gửi yêu cầu thất bại',
      }
    }
  }

  async function approveTask(taskId, note = '') {
    try {
      const { data } = await taskApi.approve(taskId, note)

      const idx = tasks.value.findIndex((t) => t.id === taskId)
      if (idx >= 0 && data.task) {
        tasks.value[idx] = data.task
      }
      if (currentTask.value?.id === taskId && data.task) {
        currentTask.value = data.task
      }

      return { success: true, message: data.message, data: data.task }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Duyệt thất bại',
      }
    }
  }

  async function rejectTask(taskId, reason) {
    try {
      const { data } = await taskApi.reject(taskId, reason)

      const idx = tasks.value.findIndex((t) => t.id === taskId)
      if (idx >= 0 && data.task) {
        tasks.value[idx] = data.task
      }
      if (currentTask.value?.id === taskId && data.task) {
        currentTask.value = data.task
      }

      return { success: true, message: data.message, data: data.task }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Từ chối thất bại',
      }
    }
  }
  return {
    tasks,
    stats,
    currentTask,
    loading,
    error,
    filters,
    todoTasks,
    doingTasks,
    doneTasks,
    lateTasks,
    fetchTasks,
    fetchTaskDetail,
    createTask,
    updateTask,
    changeStatus,
    deleteTask,
    setFilters,
    fetchComments,
    addComment,
    updateComment,
    deleteComment,
    deleteAttachment,
    bulkCreateTasks,
    submitForReview,
    approveTask,
    rejectTask,
  }
})
