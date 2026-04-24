import { studentAssignmentApi } from '@/api/students/studentAssignmentApi'
import { defineStore } from 'pinia'
import { ref } from 'vue'

// ── STUDENT STORE ─────────────────────────────────────────────────
export const useStudentAssignmentStore = defineStore('studentAssignment', () => {
  const assignments = ref([])
  const history = ref([])
  const loading = ref(false)
  const submitting = ref(false)
  const error = ref(null)

  async function fetchByClass(classId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await studentAssignmentApi.getByClass(classId)
      assignments.value = data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải danh sách'
    } finally {
      loading.value = false
    }
  }

  async function submitIndividual(assignmentId, file, note) {
    submitting.value = true
    error.value = null
    try {
      const { data } = await studentAssignmentApi.submitIndividual(assignmentId, file, note)
      // Cập nhật my_submission trong danh sách
      const idx = assignments.value.findIndex((a) => a.id === assignmentId)
      if (idx !== -1) assignments.value[idx].my_submission = data.submission
      return { success: true, data }
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi nộp bài'
      return { success: false, message: error.value }
    } finally {
      submitting.value = false
    }
  }

  async function submitGroup(assignmentId, file, note) {
    submitting.value = true
    error.value = null
    try {
      const { data } = await studentAssignmentApi.submitGroup(assignmentId, file, note)
      const idx = assignments.value.findIndex((a) => a.id === assignmentId)
      if (idx !== -1) assignments.value[idx].group_submission = data.submission
      return { success: true, data }
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi nộp bài nhóm'
      return { success: false, message: error.value }
    } finally {
      submitting.value = false
    }
  }

  async function fetchHistory(assignmentId) {
    try {
      const { data } = await studentAssignmentApi.getHistory(assignmentId)
      history.value = data.history
    } catch (e) {
      history.value = []
    }
  }

  return {
    assignments,
    history,
    loading,
    submitting,
    error,
    fetchByClass,
    submitIndividual,
    submitGroup,
    fetchHistory,
  }
})
